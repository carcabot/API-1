<?php
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 9/1/19
 * Time: 11:29 AM.
 */

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\CustomerApi;
use App\Bridge\Services\UsersApi;
use App\Document\OldUsers;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateUser extends Command
{
    /**
     * @var CustomerApi
     */
    private $customerApi;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UsersApi
     */
    private $usersApi;

    /**
     * @param CustomerApi            $customerApi
     * @param EntityManagerInterface $entityManager
     * @param DocumentManager        $documentManager
     * @param LoggerInterface        $logger
     * @param UsersApi               $usersApi
     */
    public function __construct(CustomerApi $customerApi, EntityManagerInterface $entityManager, DocumentManager $documentManager, LoggerInterface $logger, UsersApi $usersApi)
    {
        parent::__construct();
        $this->customerApi = $customerApi;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->usersApi = $usersApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-user')
            ->setDescription('Migrate user details by userId')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The used id to be migrated', null)
            ->addOption('batch', 'b', InputOption::VALUE_OPTIONAL, 'The batch number to be migrated out of the 7 batches', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $id = (string) $input->getOption('id');
        $batch = (string) $input->getOption('batch');

        if (!empty($id)) {
            $user = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $id]);

            if (null !== $user) {
                $progressBar = new ProgressBar($output);

                $progressBar->advance();
                $this->usersApi->createUser([$user]);
                $this->logger->info(\sprintf('Migrated user %s ...', $user->getId()));

                $io->success('Migrated all details of the user '.$user->getId());
                $progressBar->finish();
            } else {
                $io->error('User not found');
            }
        } elseif (empty($id) && !empty($batch)) {
            $skip = 0;
            $letter = 'first';
            switch ($batch) {
                case 1:
                    $skip = 0;
                    break;
                case 2:
                    $skip = 2000;
                    $letter = 'second';
                    break;
                case 3:
                    $skip = 4000;
                    $letter = 'third';
                    break;
                case 4:
                    $skip = 6000;
                    $letter = 'fourth';
                    break;
                case 5:
                    $skip = 8000;
                    $letter = 'fifth';
                    break;
                case 6:
                    $skip = 10000;
                    $letter = 'sixth';
                    break;
                case 7:
                    $skip = 12000;
                    $letter = 'seventh';
                    break;
                default:
                    $io->error('Batch number exceeded the limit');
            }
            $userDocument = $this->documentManager->getRepository(OldUsers::class)->findBy([], null, 2000, $skip);

            if (\count($userDocument) > 0) {
                $this->logger->info(\sprintf('Migrating '.$letter.' batch of users ...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating '.$letter.' batch of users ..... ');
                $progressBar->advance();
                $this->usersApi->createUser($userDocument);
                $io->success('Migrated '.$letter.' batch of users .');
                $progressBar->finish();
            } else {
                $io->error('Users not found');
            }
        }

        return 0;
    }
}
