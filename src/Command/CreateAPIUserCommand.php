<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Command\User\UpdatePassword;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\Person;
use App\Entity\User;
use App\Enum\AccountType;
use App\Enum\AuthorizationRole;
use App\Enum\CustomerAccountStatus;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateAPIUserCommand extends Command
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
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * @var JWTManagerInterface
     */
    private $jwtManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface     $validator
     * @param WebServiceClient       $webServiceClient
     * @param JWTManagerInterface    $jwtManager
     * @param TokenStorageInterface  $tokenStorage
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, ValidatorInterface $validator, WebServiceClient $webServiceClient, JWTManagerInterface $jwtManager, TokenStorageInterface $tokenStorage)
    {
        parent::__construct();

        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->webServiceClient = $webServiceClient;
        $this->jwtManager = $jwtManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:user:create')
            ->setDescription('Creates a user.')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Create which user type', null)
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Enter email', null)
            ->setHelp(<<<'EOF'
The %command.name% command starts the worker.
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

        $userType = $input->getOption('type');
        $email = (string) $input->getOption('email');

        $supportedUserTypes = [
            'billing' => 'Billing API',
            'internal' => 'Internal API',
            'super' => 'Super Admin',
            'homepage' => 'Web API',
        ];

        if (null !== $userType) {
            foreach ($supportedUserTypes as $key => $supportedUserType) {
                if (0 === \strpos($userType, $key)) {
                    $io->text(\sprintf('[%s] Creating %s user.', (new \DateTime())->format('r'), $supportedUserType));

                    $user = new User();
                    $userAccount = new CustomerAccount();
                    $userAccount->setStatus(new CustomerAccountStatus(CustomerAccountStatus::INACTIVE));

                    if ('billing' === $key) {
                        $userAccount->setType(new AccountType(AccountType::CORPORATE));
                        $name = $this->webServiceClient->getProviderName();

                        $corporation = new Corporation();
                        $corporation->setName($name);
                        $this->entityManager->persist($corporation);

                        $userAccount->setCorporationDetails($corporation);
                    } else {
                        $userAccount->setType(new AccountType(AccountType::INDIVIDUAL));
                        $name = $supportedUserType;

                        $person = new Person();
                        $person->setName($name);

                        $this->entityManager->persist($person);
                        $userAccount->setPersonDetails($person);
                    }
                    $this->entityManager->persist($userAccount);

                    $password = \uniqid($key, true);

                    $user->setUsername(\str_replace(' ', '', $name));
                    $user->setEmail($email);
                    $user->setCustomerAccount($userAccount);
                    $user->setPlainPassword($password);
                    $user->setDateActivated(new \DateTime());

                    $this->commandBus->handle(new UpdatePassword($user, $password));

                    if ('super' === $key) {
                        $user->addRole(AuthorizationRole::ROLE_SUPER_ADMIN);
                    } elseif ('homepage' === $key) {
                        $user->addRole(AuthorizationRole::ROLE_HOMEPAGE);
                    } else {
                        $user->addRole(AuthorizationRole::ROLE_API_USER);
                    }

                    $errors = $this->validator->validate($user);

                    if (\count($errors) > 0) {
                        /*
                         * Uses a __toString method on the $errors variable which is a
                         * ConstraintViolationList object. This gives us a nice string
                         * for debugging.
                         */
                        $io->error((string) $errors);

                        return 0;
                    }

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    $io->text(\sprintf('[%s] Created %s user.', (new \DateTime())->format('r'), $supportedUserType));
                    $token = $this->jwtManager->create($user);

                    $jwtToken = new JWTUserToken($user->getRoles(), $user, $token, 'authentication_token');
                    $this->tokenStorage->setToken($jwtToken);

                    $token = $this->jwtManager->create($user);

                    $io->table([],
                        [
                            ['Username: ', $user->getUsername()],
                            ['Password: ', $password],
                        ]
                    );

                    $io->section('JWT Token :');
                    $io->text($token);
                }
            }
        } else {
            $io->error('Please specify a supported user type with option --type');
        }

        return 0;
    }
}
