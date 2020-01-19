<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Enum\AuthorizationRole;
use App\Model\ContractRenewalApplicationRequestStatusUpdater;
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

class ProcessContractRenewalApplicationRequestXML extends Command
{
    /**
     * @var BlameableListener
     */
    private $blameableListener;

    /**
     * @var ContractRenewalApplicationRequestStatusUpdater
     */
    private $contractRenewalApplicationRequestStatusUpdater;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $emergencyWebhookUrl;

    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var string
     */
    private $documentConverterHost;

    /**
     * @param BlameableListener                              $blameableListener
     * @param ContractRenewalApplicationRequestStatusUpdater $contractRenewalApplicationRequestStatusUpdater
     * @param EntityManagerInterface                         $entityManager
     * @param WebServiceClient                               $webServiceClient
     * @param LoggerInterface                                $logger
     * @param string                                         $timezone
     * @param string                                         $documentConverterHost
     * @param string                                         $emergencyWebhookUrl
     */
    public function __construct(BlameableListener $blameableListener, ContractRenewalApplicationRequestStatusUpdater $contractRenewalApplicationRequestStatusUpdater, EntityManagerInterface $entityManager, WebServiceClient $webServiceClient, LoggerInterface $logger, string $timezone, string $documentConverterHost, string $emergencyWebhookUrl)
    {
        parent::__construct();

        $this->blameableListener = $blameableListener;
        $this->contractRenewalApplicationRequestStatusUpdater = $contractRenewalApplicationRequestStatusUpdater;
        $this->entityManager = $entityManager;
        $this->webServiceClient = $webServiceClient;
        $this->logger = $logger;
        $this->timezone = new \DateTimeZone($timezone);
        $this->documentConverterHost = $documentConverterHost;
        $this->emergencyWebhookUrl = $emergencyWebhookUrl;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:process-contract-renewal-application-xml')
            ->setDescription('Processes a contract renewal application xml file.')
            ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'For which specific date (YYYY-MM-DD)', null)
            ->addOption('file-url', null, InputOption::VALUE_OPTIONAL, 'Url of the file', null)
            ->setHelp(<<<'EOF'
The %command.name% command processes a contract renewal application xml file.
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
            $tempFile = \tmpfile();
            $curl = \curl_init();
            \curl_setopt($curl, CURLOPT_URL, $fileUrl);
            \curl_setopt($curl, CURLOPT_FILE, $tempFile);
            \curl_exec($curl);
            \curl_close($curl);
        } else {
            $tempFile = $this->webServiceClient->getContractRenewalApplicationXMLFile($date);
        }

        $baseUri = HttpUri::createFromString($this->documentConverterHost);
        $modifier = new AppendSegment('application_requests/xml/contract_renewal_application');
        $uri = $modifier->process($baseUri);

        $client = new GuzzleClient();
        $multipartContent = [
            'headers' => [
                'User-Agent' => 'U-Centric API',
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'filename' => \uniqid().'.xml',
                    'contents' => $tempFile,
                ],
            ],
        ];

        if (\filesize(\stream_get_meta_data($tempFile)['uri']) > 0) {
            $io->text('Uploading to document converter...');
            $uploadResponse = $client->request('POST', $uri, $multipartContent);
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

                $failedApplicationRequests = $this->contractRenewalApplicationRequestStatusUpdater->processArrayData($uploadResult);

                if (\count($failedApplicationRequests) > 0) {
                    $url = $this->webServiceClient->uploadFailedApplicationRequestStatusUpdate($failedApplicationRequests, 'CreateFailedContractRenewalApplicationUpdateStatus');

                    $botData = [
                        'command' => 'ProcessContractRenewalApplicationRequestXML',
                        'task' => 'Update the status of contract renewal application requests.',
                        'url' => $url,
                    ];

                    $this->sendToBot($botData);
                }
            }
        } else {
            $io->text('No file found.');
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
