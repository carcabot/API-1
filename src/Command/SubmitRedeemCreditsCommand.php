<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\RedeemCreditsAction;
use App\Enum\ActionStatus;
use App\Enum\OfferType;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SubmitRedeemCreditsCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param EntityManagerInterface $entityManager
     * @param WebServiceClient       $webServiceClient
     * @param string                 $timezone
     */
    public function __construct(EntityManagerInterface $entityManager, WebServiceClient $webServiceClient, string $timezone)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->webServiceClient = $webServiceClient;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:submit-redeem-credits')
            ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'For which specific date (YYYY-MM-DD HH:MM:SS)', null)
            ->addOption('upload', null, InputOption::VALUE_NONE, 'Upload directly to ftp')
            ->setDescription('Generate order bill rebate XML file.')
            ->setHelp(<<<'EOF'
The %command.name% command generates bill rebate xml.
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
        $upload = (bool) $input->getOption('upload');

        $endDate = new \DateTime('now', $this->timezone);
        if (null !== $dateString) {
            $endDate = new \DateTime($dateString, $this->timezone);
        }

        $io->text('Start submitting order bill rebate ending '.$endDate->format('c'));

        $startDate = clone $endDate;
        $startDate->modify('-1 day');

        $qb = $this->entityManager->getRepository(RedeemCreditsAction::class)->createQueryBuilder('redeem');
        $expr = $qb->expr();

        $startDate->setTimezone(new \DateTimeZone('UTC'));
        $endDate->setTimezone(new \DateTimeZone('UTC'));
        $redeemedCreditsActions = $qb->select('redeem')
            ->leftJoin('redeem.instrument', 'order')
            ->leftJoin('order.items', 'orderItem')
            ->leftJoin('orderItem.offerListItem', 'offerListItem')
            ->leftJoin('offerListItem.item', 'offer')
            ->where($expr->eq('redeem.status', $expr->literal(ActionStatus::COMPLETED)))
            ->andWhere($expr->gt('redeem.startTime', $expr->literal($startDate->format('c'))))
            ->andWhere($expr->lte('redeem.startTime', $expr->literal($endDate->format('c'))))
            ->andWhere($expr->eq('offer.type', ':offType'))
            ->setParameter('offType', OfferType::BILL_REBATE)
            ->getQuery()
            ->getResult();

        if (\count($redeemedCreditsActions) > 0) {
            $this->webServiceClient->submitRedeemCreditsActions($redeemedCreditsActions, $upload);
        } else {
            $io->text('No redemptions made.');
        }

        return 0;
    }
}
