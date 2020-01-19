<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Document\AuthToken;
use App\Entity\BridgeUser;
use App\Entity\UserLoginHistory;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateUserLoginHistory extends Command
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(DocumentManager $documentManager, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-user-login-history')
            ->setDescription('Migrate user login history');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $authTokens = $this->documentManager->getRepository(AuthToken::class)->findBy([]);

        $progressBar = new ProgressBar($output);
        $count = 0;
        $batchSize = 2000;

        foreach ($authTokens as $authToken) {
            $loginHistory = new UserLoginHistory();
            $loginHistory->setDate($authToken->getLoginDate());
            $loginHistory->setDevice($authToken->getDeviceName());
            if (null !== $authToken->getUserId()) {
                $bridgeUser = $this->entityManager->getRepository(BridgeUser::class)->findOneBy([
                    'bridgeUserId' => $authToken->getUserId(),
                ]);

                if (null !== $bridgeUser) {
                    $loginHistory->setUser($bridgeUser->getUser());
                } else {
                    continue;
                }
            }
            $this->entityManager->persist($loginHistory);
            ++$count;

            if (0 === ($count % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear(); // Detaches all objects from doctrine
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
        $progressBar->finish();
        $io->success('Migrated '.$count.' records.');

        return 0;
    }
}
