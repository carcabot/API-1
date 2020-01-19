<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\AffiliateCommissionStatus;
use App\Model\AffiliateProgramCommissionConversionCalculator;
use App\WebService\Affiliate\ClientFactory;
use App\WebService\Affiliate\DummyClient;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GetAffiliateConversionData extends Command
{
    /**
     * @var AffiliateProgramCommissionConversionCalculator
     */
    private $affiliateCommissionCalculator;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param AffiliateProgramCommissionConversionCalculator $affiliateCommissionCalculator
     * @param ClientFactory                                  $clientFactory
     * @param EntityManagerInterface                         $entityManager
     * @param LoggerInterface                                $logger
     * @param string                                         $timezone
     */
    public function __construct(AffiliateProgramCommissionConversionCalculator $affiliateCommissionCalculator, ClientFactory $clientFactory, EntityManagerInterface $entityManager, LoggerInterface $logger, string $timezone)
    {
        parent::__construct();

        $this->affiliateCommissionCalculator = $affiliateCommissionCalculator;
        $this->clientFactory = $clientFactory;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:get-conversion-data')
            ->setDescription('Gets conversion data from the past 24 hours.')
            ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Get conversion data for a specified date.', 'now')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'Get conversion data for a specified affiliate partner.', null)
            ->addOption('file-url', null, InputOption::VALUE_OPTIONAL, 'Get conversion data from external file.', null)
            ->setHelp(<<<'EOF'
The %command.name% command gets conversion data from the past 24 hours.
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

        $provider = (string) $input->getOption('provider');
        $dateString = $input->getOption('date');
        $fileUrl = $input->getOption('file-url');

        $affiliateClient = $this->clientFactory->getClient($provider);

        if (!$affiliateClient instanceof DummyClient) {
            $io->success(\sprintf('Affiliate Client class for %s, %s found.', $provider, \get_class($affiliateClient)));
            $io->comment('Getting conversion data...');
            $transactions = [];

            if (null !== $fileUrl) {
                $client = new GuzzleClient();
                $uploadResponse = $client->request('GET', $fileUrl);
                $transactions = \json_decode((string) $uploadResponse->getBody(), true);
            } else {
                $endDate = new \DateTime($dateString);
                $endDate->setTimezone($this->timezone);
                $startDate = clone $endDate;

                $endDate->setTime(23, 59, 59);
                $startDate->setTime(0, 0, 0);

                $result = $affiliateClient->getConversionDataByDate($startDate, $endDate);
                $transactions = $affiliateClient->normalizeConversionData($result);
            }

            foreach ($transactions as $key => $transaction) {
                if (!$transaction['commissionStatus'] instanceof AffiliateCommissionStatus) {
                    $transactions[$key]['commissionStatus'] = $this->mapCommissionStatus($transaction['commissionStatus']);
                }
            }

            $this->affiliateCommissionCalculator->processData($transactions, $affiliateClient->getProviderName());
            $io->text(\json_encode($transactions));
        }

        return 0;
    }

    private function mapCommissionStatus(string $approvalStatus)
    {
        switch ($approvalStatus) {
            case 'PENDING':
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::PENDING);
            case 'DECLINED':
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::DECLINED);
            default:
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::APPROVED);
        }
    }
}
