<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\ApplicationRequestAccountClosureCreator;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use App\WebService\Billing\Enum\UploadFileType;
use GuzzleHttp\Client as GuzzleClient;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProcessMassAccountClosureRequestXML extends Command
{
    /**
     * @var ApplicationRequestAccountClosureCreator
     */
    private $applicationRequestAccountClosureCreator;

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
     * @param ApplicationRequestAccountClosureCreator $applicationRequestAccountClosureCreator
     * @param WebServiceClient                        $webServiceClient
     * @param LoggerInterface                         $logger
     * @param string                                  $timezone
     * @param string                                  $documentConverterHost
     */
    public function __construct(ApplicationRequestAccountClosureCreator $applicationRequestAccountClosureCreator, WebServiceClient $webServiceClient, LoggerInterface $logger, string $timezone, string $documentConverterHost)
    {
        parent::__construct();

        $this->applicationRequestAccountClosureCreator = $applicationRequestAccountClosureCreator;
        $this->webServiceClient = $webServiceClient;
        $this->logger = $logger;
        $this->timezone = new \DateTimeZone($timezone);
        $this->documentConverterHost = $documentConverterHost;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:process-mass-account-closure-xml')
            ->setDescription('Processes a mass account closure application request xml file.')
            ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'For which specific date (YYYY-MM-DD)', null)
            ->addOption('file-url', null, InputOption::VALUE_OPTIONAL, 'Url of the file', null)
            ->addOption('upload', null, InputOption::VALUE_NONE, 'Upload directly to ftp')
            ->setHelp(<<<'EOF'
The %command.name% command processes a mass account closure application xml file.
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
        $upload = (bool) $input->getOption('upload');

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
            $tempFile = $this->webServiceClient->getMassContractClosureApplicationRequestXMLFile($date);
        }

        $baseUri = HttpUri::createFromString($this->documentConverterHost);
        $modifier = new AppendSegment('application_requests/xml/mass_account_closure');
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

        if (null !== $tempFile && \filesize(\stream_get_meta_data($tempFile)['uri']) > 0) {
            $io->text('Uploading to document converter...');
            $uploadResponse = $client->request('POST', $uri, $multipartContent);
            $uploadResult = \json_decode((string) $uploadResponse->getBody(), true);

            if (200 === $uploadResponse->getStatusCode()) {
                $resultData = $this->applicationRequestAccountClosureCreator->processArrayData($uploadResult);
                if (\count($resultData) > 0) {
                    $this->webServiceClient->uploadReturnFile($resultData, new \DateTime(), UploadFileType::ACCOUNT_CLOSURE_RETURN, $upload);
                } else {
                    $this->logger->info('No records to process.');
                }
            }
        } else {
            $io->text('No file found.');
        }

        return 0;
    }
}
