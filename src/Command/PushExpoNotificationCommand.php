<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Solvecrew\ExpoNotificationsBundle\Manager\NotificationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PushExpoNotificationCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var NotificationManager
     */
    private $notificationManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param NotificationManager    $notificationManager
     */
    public function __construct(EntityManagerInterface $entityManager, NotificationManager $notificationManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:push-notification')
            ->setDescription('Push Expo notification.')
            ->addOption('body', null, InputOption::VALUE_REQUIRED, 'Notification message body.', null)
            ->addOption('title', null, InputOption::VALUE_OPTIONAL, 'Title of notification.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $body = null !== $input->getOption('body') ? (string) $input->getOption('body') : null;
        $title = null !== $input->getOption('title') ? (string) $input->getOption('title') : '';

        if (null === $body || '' === \trim($body)) {
            $io->error('No message to push');

            return 0;
        }

        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('user');
        $users = $qb->where($qb->expr()->gt(\sprintf(<<<'SQL'
            jsonb_array_length(CAST(%s.%s AS jsonb))
SQL
            , 'user', 'expoPushNotificationTokens'), $qb->expr()->literal(0)))
            ->getQuery()
            ->getResult();

        $tokens = [];

        foreach ($users as $user) {
            foreach ($user->getExpoPushNotificationTokens() as $token) {
                if (1 === \preg_match('/^ExponentPushToken\[.*\]$/', $token)) {
                    $token = \preg_replace('/^ExponentPushToken\[/', '', $token);
                    $token = \rtrim($token, ']');
                    $tokens[] = $token;
                }
            }
        }

        $tokens = \array_unique($tokens);
        $tokens = \array_values($tokens);
        $totalNotifications = \count($tokens);

        if ($totalNotifications > 0) {
            foreach ($tokens as $token) {
                $this->notificationManager->sendNotification($body, $token, $title);
            }
        }

        return 0;
    }
}
