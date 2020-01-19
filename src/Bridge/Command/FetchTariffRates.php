<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\TariffRateApi;
use App\Entity\BridgeUser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FetchTariffRates extends Command
{
    /**
     * @var string
     */
    private $bridgeApiUrl;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TariffRateApi
     */
    private $tariffRateApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string                 $bridgeApiUrl
     * @param EntityManagerInterface $entityManager
     * @param TariffRateApi          $tariffRateApi
     * @param LoggerInterface        $logger
     */
    public function __construct(string $bridgeApiUrl, EntityManagerInterface $entityManager, TariffRateApi $tariffRateApi, LoggerInterface $logger)
    {
        parent::__construct();

        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->entityManager = $entityManager;
        $this->tariffRateApi = $tariffRateApi;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:fetch-tariff-rates')
            ->setDescription('Fetches all tariff rates from bridge API.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        if (empty($this->bridgeApiUrl)) {
            $io->error('No bridge to cross...');

            return 0;
        }

        $qb = $this->entityManager->getRepository(BridgeUser::class)->createQueryBuilder('bridgeUser');

        $bridgeUsers = $qb->orderBy('bridgeUser.dateModified', 'DESC')
            ->getQuery()
            ->getResult();

        if (\count($bridgeUsers) > 0) {
            $tariffRates = $this->tariffRateApi->getTariffRates($bridgeUsers[0]);
            $progressBar = new ProgressBar($output, \count($tariffRates));

            foreach ($tariffRates as $tariffRate) {
                $this->logger->info(\sprintf('Fetched %s ...', $tariffRate['promotion_code']));
                $this->tariffRateApi->updateTariffRates([$tariffRate]);
                $progressBar->advance();
            }

            $progressBar->finish();

            $io->success('Fetched all tariff rates.');
        } else {
            $io->error('No bridge user exists.');
        }

        return 0;
    }
}
