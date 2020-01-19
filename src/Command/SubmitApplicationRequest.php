<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ApplicationRequest;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SubmitApplicationRequest extends Command
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
            ->setName('app:web-service:submit-application-request')
            ->setDescription('Submits a specified application request.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Submit which application request (id)', null)
            ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'For which specific date (YYYYMMDD)', null)
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
        $date = !empty($input->getOption('date')) ? (string) $input->getOption('date') : null;
        $fail = (bool) $input->getOption('fail');

        $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $id]);

        if (null === $applicationRequest) {
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->find($id);
        }

        if (null !== $applicationRequest) {
            $io->success(\sprintf('Application Request #%s found.', $id));
            $io->comment('Submitting to web service.');

            $result = $this->webServiceClient->submitApplicationRequest($applicationRequest, $date, $fail);

            $io->text($result);
        } else {
            $io->error('Application request not found');
        }

        return 0;
    }
}
