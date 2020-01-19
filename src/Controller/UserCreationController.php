<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Command\User\UpdatePassword;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\CustomerBlacklistConfiguration;
use App\Entity\Person;
use App\Entity\User;
use App\Enum\AccountType;
use App\Enum\BlacklistConfigurationAction;
use App\Enum\ContractStatus;
use App\Enum\CustomerAccountStatus;
use App\Enum\CustomerRelationshipType;
use App\Service\UserCreationHelper;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Diactoros\Response\JsonResponse;

class UserCreationController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var UserCreationHelper
     */
    private $userCreationHelper;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param CommandBus             $commandBus
     * @param UserCreationHelper     $userCreationHelper
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CommandBus $commandBus, UserCreationHelper $userCreationHelper, EntityManagerInterface $entityManager)
    {
        $this->commandBus = $commandBus;
        $this->userCreationHelper = $userCreationHelper;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/create/user", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function create(ServerRequestInterface $request): HttpResponseInterface
    {
        $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customerAccount');
        $params = \json_decode($request->getBody()->getContents(), true);

        $username = \array_key_exists('username', $params) && \strlen(\trim($params['username'])) > 1 ? \trim($params['username']) : null;
        $nric = \array_key_exists('nric', $params) ? $params['nric'] : null;
        $uen = \array_key_exists('uen', $params) ? $params['uen'] : null;
        $email = \array_key_exists('email', $params) && '' !== \trim($params['email']) ? \trim($params['email']) : null;
        $contractNumber = \array_key_exists('contractNumber', $params) ? $params['contractNumber'] : null;
        $password = \array_key_exists('password', $params) && \strlen($params['password']) >= 8 ? $params['password'] : null;
        $name = \array_key_exists('name', $params) ? $params['name'] : null;

        if (null !== $contractNumber) {
            $signupCustomer = null;
            $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractNumber]);

            if (null === $contract) {
                // no contract
                throw new NotFoundHttpException('No contract found.');
            } elseif (ContractStatus::ACTIVE !== $contract->getStatus()->getValue()) {
                // check active
                throw new BadRequestHttpException('Contract has not been activated.');
            }

            $customer = $contract->getCustomer();

            $blacklistConfiguration = $this->entityManager->getRepository(CustomerBlacklistConfiguration::class)->findOneBy(['action' => new BlacklistConfigurationAction(BlacklistConfigurationAction::CREATE_USER)]);

            if (null !== $blacklistConfiguration) {
                if (true === $blacklistConfiguration->isEnabled()) {
                    if (null !== $customer->getDateBlacklisted() && $customer->getDateBlacklisted() <= new \DateTime('now', new \DateTimeZone('UTC'))) {
                        // check customer status
                        throw new BadRequestHttpException('This Customer has been Blacklisted');
                    }
                }
            }
            //check type
            if (AccountType::INDIVIDUAL === $customer->getType()->getValue() && null !== $nric) {
                if (true === $this->userCreationHelper->isNRICMatch($customer, $nric)) {
                    $signupCustomer = $customer;
                }

                if (null === $signupCustomer) {
                    foreach ($customer->getRelationships() as $relationship) {
                        if (CustomerRelationshipType::CONTACT_PERSON === $relationship->getType()->getValue() &&
                            true === $this->userCreationHelper->isNRICMatch($relationship->getFrom(), $nric) &&
                            true === $this->userCreationHelper->relationshipHasContract($relationship, $contract)
                        ) {
                            $signupCustomer = $relationship->getFrom();
                        }
                    }
                }
            } elseif (AccountType::CORPORATE === $customer->getType()->getValue() && null !== $uen) {
                if (false === $this->userCreationHelper->isUENMatch($customer, $uen)) {
                    throw new BadRequestHttpException('UEN does not match.');
                }

                foreach ($customer->getRelationships() as $relationship) {
                    if (CustomerRelationshipType::CONTACT_PERSON === $relationship->getType()->getValue() &&
                        true === $this->userCreationHelper->isEmailMatch($relationship->getFrom(), $email) &&
                        true === $this->userCreationHelper->relationshipHasContract($relationship, $contract)
                    ) {
                        $signupCustomer = $relationship->getFrom();
                    }
                }
            } else {
                throw new BadRequestHttpException('Identification type does not match.');
            }

            // if at this point $signupCustomer is still null, fail signup!
            if (null === $signupCustomer) {
                throw new BadRequestHttpException('Information provided does not match any combination.');
            }

            // check if previously signed up
            if (null !== $signupCustomer->getUser()) {
                throw new BadRequestHttpException(\sprintf('You have already registered with %s. Did you forget your password?', $signupCustomer->getUser()->getEmail()));
            }

            // check if email matches the customer email
            if (false === $this->userCreationHelper->isEmailMatch($signupCustomer, $email)) {
                throw new BadRequestHttpException('Email does not match.');
            }

            $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('user');
            $existingUser = $qb->select('user')
                ->where($qb->expr()->eq('lower(user.email)', $qb->expr()->literal(\strtolower($email))))
                ->getQuery()
                ->getOneOrNullResult();

            if (null !== $existingUser) {
                throw new BadRequestHttpException('Email is already registered as a user.');
            }
            // we gucci, create user
            $user = $this->userCreationHelper->createUser(null, $email, $signupCustomer);
            $this->commandBus->handle(new UpdatePassword($user, $password));
            $this->entityManager->flush();

            $this->userCreationHelper->queueWelcomeEmailJob($user, $password);

            return new JsonResponse([
                'message' => 'User created successfully.',
            ], 200);
        } elseif (null !== $name && null !== $email && null !== $password) {
            $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('user');

            $user = $qb->select('user')
                ->where($qb->expr()->eq('lower(user.email)', $qb->expr()->literal(\strtolower($email))))
                ->getQuery()
                ->getOneOrNullResult();

            if (null !== $user) {
                throw new BadRequestHttpException(\sprintf('You have already signed up with %s. Did you forget your password?', $user->getEmail()));
            }

            $customerAccount = new CustomerAccount();
            $customerAccount->setType(new AccountType(AccountType::INDIVIDUAL));
            $customerAccount->setStatus(new CustomerAccountStatus(CustomerAccountStatus::INACTIVE));

            $person = new Person();
            $person->setName($name);

            $this->entityManager->persist($person);
            $customerAccount->setPersonDetails($person);

            $user = $this->userCreationHelper->createUser(null, $email, $customerAccount);
            $this->commandBus->handle(new UpdatePassword($user, $password));
            $this->entityManager->flush();

            $this->userCreationHelper->queueWelcomeEmailJob($user, $password);

            return new JsonResponse([
                'message' => 'User created successfully.',
            ], 200);
        }

        throw new BadRequestHttpException('Invalid signup credentials.');
    }
}
