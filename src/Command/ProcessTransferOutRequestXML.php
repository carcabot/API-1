<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Enum\AuthorizationRole;
use App\Model\ApplicationRequestTransferOutStatusUpdater;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Blameable\BlameableListener;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProcessTransferOutRequestXML extends Command
{
    /**
     * @var BlameableListener
     */
    private $blameableListener;

    /**
     * @var string
     */
    private $emergencyWebhookUrl;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var string
     */
    private $documentConverterHost;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var ApplicationRequestTransferOutStatusUpdater
     */
    private $applicationRequestTransferOutStatusUpdater;

    /**
     * @param BlameableListener                          $blameableListener
     * @param WebServiceClient                           $webServiceClient
     * @param EntityManagerInterface                     $entityManager
     * @param LoggerInterface                            $logger
     * @param ApplicationRequestTransferOutStatusUpdater $applicationRequestTransferOutStatusUpdater
     * @param string                                     $timezone
     * @param string                                     $documentConverterHost
     * @param string                                     $emergencyWebhookUrl
     */
    public function __construct(BlameableListener $blameableListener, WebServiceClient $webServiceClient, EntityManagerInterface $entityManager, LoggerInterface $logger, ApplicationRequestTransferOutStatusUpdater $applicationRequestTransferOutStatusUpdater, string $timezone, string $documentConverterHost, string $emergencyWebhookUrl)
    {
        parent::__construct();
        $this->blameableListener = $blameableListener;
        $this->webServiceClient = $webServiceClient;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->documentConverterHost = $documentConverterHost;
        $this->client = new GuzzleClient();
        $this->timezone = new \DateTimeZone($timezone);
        $this->baseUri = HttpUri::createFromString($this->documentConverterHost);
        $this->applicationRequestTransferOutStatusUpdater = $applicationRequestTransferOutStatusUpdater;
        $this->emergencyWebhookUrl = $emergencyWebhookUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:process-transfer-out-application-xml')
            ->setDescription('get xml and update data to applicaton request')
            ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'For which specific date (Y-m-d)', null)
            ->addOption('file-url', null, InputOption::VALUE_OPTIONAL, 'Url of the file', null)
            ->setHelp(<<<'EOF'
The %command.name% command process transfer out application xml to web service.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $dateString = $input->getOption('date');
        $fileUrl = $input->getOption('file-url');

        $date = new \DateTime('now', $this->timezone);
        if (null !== $dateString) {
            $date = new \DateTime($dateString, $this->timezone);
        }

        if (null !== $fileUrl) {
            $file = \tmpfile();
            $curl = \curl_init();
            \curl_setopt($curl, CURLOPT_URL, $fileUrl);
            \curl_setopt($curl, CURLOPT_FILE, $file);
            \curl_exec($curl);
            \curl_close($curl);
        } else {
            $file = $this->webServiceClient->getTransferOutApplicationXMLFile($date);
        }

        $io->text('Uploading to document converter...');

        if (null !== $file) {
            $modifier = new AppendSegment('/application_requests/xml/transfer_out');
            $uri = $modifier->process($this->baseUri);
            $multipartContent = [
                'headers' => [
                    'User-Agent' => 'U-Centric API',
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'filename' => \uniqid().'.xml',
                        'contents' => $file,
                    ],
                ],
            ];
            $uploadResponse = $this->client->request('POST', $uri, $multipartContent);
            $uploadResult = \json_decode((string) $uploadResponse->getBody(), true);

            if (200 === $uploadResponse->getStatusCode()) {
                $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('user');
                $expr = $qb->expr();

                $agent = $qb
                    ->where($expr->eq('user.username', ':name'))
                    ->andWhere($expr->andX(
                        $expr->eq(\sprintf(<<<'SQL'
                            jsonb_contains(CAST(%s.%s AS jsonb), :%s)
SQL
                    , 'user', 'roles', 'apiRole'), $expr->literal(true))
                    ))
                    ->setParameter('name', $this->webServiceClient->getProviderName())
                    ->setParameter('apiRole', \json_encode(AuthorizationRole::ROLE_API_USER))
                    ->getQuery()
                    ->getOneOrNullResult();

                if (null !== $agent) {
                    $this->blameableListener->setUserValue($agent);
                }

                $failedApplicationRequests = $this->applicationRequestTransferOutStatusUpdater->processArrayData($uploadResult);

                if (\count($failedApplicationRequests) > 0) {
                    $url = $this->webServiceClient->uploadFailedApplicationRequestStatusUpdate($failedApplicationRequests, 'CreateFailedTransferOutApplicationUpdateStatus');

                    $botData = [
                        'command' => 'ProcessTransferOutRequestXML',
                        'task' => 'Update the status of transfer out application requests.',
                        'url' => $url,
                    ];

                    $this->sendToBot($botData);
                }
            }
        } else {
            $io->error('Process TransferOut not found');
        }

        return 0;
    }

    private function sendToBot(array $data)
    {
        $command = $data['command'];

        $text = $data['task'].', file url: '.$data['url'];
        $attachment = [
            [
                'text' => $text,
            ],
        ];

        if (!empty($this->emergencyWebhookUrl)) {
            $client = new GuzzleClient();

            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
            ];

            $payload = [
                'text' => 'Failed Command: '.$command,
                'attachments' => $attachment,
            ];

            $submitRequest = new GuzzlePsr7Request('POST', $this->emergencyWebhookUrl, $headers, \json_encode($payload));

            $client->send($submitRequest);
        }
    }
}
