<?php

declare(strict_types=1);

namespace App\Command;

use App\WebService\Billing\ClientInterface as WebServiceClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UploadXML extends Command
{
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
     * @param WebServiceClient $webServiceClient
     * @param LoggerInterface  $logger
     * @param string           $timezone
     */
    public function __construct(WebServiceClient $webServiceClient, LoggerInterface $logger, string $timezone)
    {
        parent::__construct();

        $this->webServiceClient = $webServiceClient;
        $this->logger = $logger;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:upload-xml')
            ->setDescription('Uploads an XML file.')
            ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'For which specific date (YYYY-MM-DD)', null)
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Upload XML type', null)
            ->setHelp(<<<'EOF'
The %command.name% command processes a contract application xml file.
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
        $type = (string) $input->getOption('type');

        $date = new \DateTime('now', $this->timezone);
        if (null !== $dateString) {
            $date = new \DateTime($dateString, $this->timezone);
        }

        $errors = [];
        $supportedTypes = [
            'ACCOUNT_CLOSURE',
            'BILL_REDEMPTION',
            'CONTRACT_APPLICATION',
            'GIRO_TERMINATION',
            'TRANSFER_OUT',
            'EXISTING_CUSTOMER_REFUND',
            'NON_EXISTING_CUSTOMER_REFUND',
        ];
        $uploadingTypes = $supportedTypes;

        if (!\in_array($type, $supportedTypes, true)) {
            $io->text(\sprintf('Unknown xml type: %s', $type));
            $io->text(\sprintf('Known types are: %s', \implode(', ', $supportedTypes)));

            return 0;
        }

        $uploadingTypes = [$type];

        foreach ($uploadingTypes as $type) {
            $io->text(\sprintf('Uploading xml type: %s', $type));
            $error = $this->webServiceClient->uploadXML($date, $type);

            if (null !== $error) {
                $errors[] = $error;
            }
        }

        if (\count($errors) > 0) {
            $io->error($errors);
        }

        return 0;
    }
}
