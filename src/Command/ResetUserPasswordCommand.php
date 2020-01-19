<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Command\User\UpdatePassword;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ResetUserPasswordCommand extends Command
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:user:reset-password')
            ->setDescription('Resets a user\'s password.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'User Id', null)
            ->setHelp(<<<'EOF'
The %command.name% command resets a specified user's password.
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

        if (null !== $id) {
            $user = $this->entityManager->getRepository(User::class)->find($id);

            if (null !== $user) {
                $io->text(\sprintf('[%s] Resetting password for User #%s.', (new \DateTime())->format('r'), $id));

                $password = \uniqid(\md5((string) \microtime()), true);
                $user->setUsername(\str_replace(' ', '', $user->getUsername()));
                $user->setPlainPassword($password);
                $this->commandBus->handle(new UpdatePassword($user, $password));

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $io->text(\sprintf('[%s] Password reset.', (new \DateTime())->format('r')));

                $io->table([],
                    [
                        ['Email: ', $user->getEmail()],
                        ['Username: ', $user->getUsername()],
                        ['Password: ', $password],
                    ]
                );
            }
        }

        return 0;
    }
}
