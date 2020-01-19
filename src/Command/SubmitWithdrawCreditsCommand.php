<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\WithdrawCreditsAction;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SubmitWithdrawCreditsCommand extends Command
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param WebServiceClient       $webServiceClient
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(WebServiceClient $webServiceClient, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct();

        $this->webServiceClient = $webServiceClient;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:submit-withdraw-credits')
            ->setDescription('Submits a specified withdraw credits action.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Submit which withdraw credits action (id)', null)
            ->addOption('fail', null, InputOption::VALUE_NONE, 'Force fail indicator')
            ->setHelp(<<<'EOF'
The %command.name% command submits an application request to web service.
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

        $id = $input->getOption('id');
        $fail = (bool) $input->getOption('fail');

        $withdrawCreditsAction = $this->entityManager->getRepository(WithdrawCreditsAction::class)->find($id);

        if (null !== $withdrawCreditsAction) {
            $io->success(\sprintf('Withdraw credits action #%s found.', $id));
            $io->comment('Submitting to web service.');

            $result = $this->webServiceClient->submitWithdrawCreditsAction($withdrawCreditsAction, $fail);

            $io->text($result);
        } else {
            $io->error('Withdraw credits action not found');
        }

        return 0;
    }
}
