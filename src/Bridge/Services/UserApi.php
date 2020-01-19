<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Entity\BridgeUser;
use App\Entity\CustomerAccount;
use App\Entity\Person;
use App\Entity\User;
use App\Enum\AccountType;
use App\Enum\AuthorizationRole;
use App\Enum\CustomerAccountStatus;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Tactician\CommandBus;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserApi
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
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @param string                 $bridgeApiUrl
     * @param EntityManagerInterface $entityManager
     * @param CommandBus             $commandBus
     */
    public function __construct(string $bridgeApiUrl, EntityManagerInterface $entityManager, CommandBus $commandBus)
    {
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->entityManager = $entityManager;
        $this->commandBus = $commandBus;
        $this->client = new GuzzleClient();
        $this->baseUri = HttpUri::createFromString($this->bridgeApiUrl);
    }

    /**
     * Verifies authentication token, returns user _id if successful.
     *
     * @param string $authToken
     *
     * @return string|null
     */
    public function verifyAuthToken(string $authToken)
    {
        $modifier = new AppendSegment('bridge/verify-token');
        $verifyUri = $modifier->process($this->baseUri);

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
        ];

        $verifyRequest = new GuzzlePsr7Request('POST', $verifyUri, $headers, \json_encode(['auth_token' => $authToken]));
        $verifyResponse = $this->client->send($verifyRequest);
        $verifyResult = \json_decode((string) $verifyResponse->getBody(), true);

        if (200 === $verifyResult['status'] && 1 === $verifyResult['flag']) {
            $userId = $verifyResult['data']['user_id'];
        } else {
            throw new BadRequestHttpException('Unable to authenticate user.');
        }

        return $userId;
    }

    /**
     * Updates user token from credential given by BridgeApiAuthenticator.
     *
     * @param array $credentials
     */
    public function updateBridgeUserToken(array $credentials)
    {
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('user');

        $qb->select('user')
            ->innerJoin('user.bridgeUser', 'bridgeUser')
            ->where($qb->expr()->eq('bridgeUser.bridgeUserId', $qb->expr()->literal($credentials['user_id'])));

        $user = $qb->getQuery()->getOneOrNullResult();

        if (null !== $user && null !== $user->getBridgeUser() && !empty($credentials['auth_token'])) {
            $user->getBridgeUser()->setAuthToken($credentials['auth_token']);

            $this->entityManager->persist($user->getBridgeUser());
            $this->entityManager->flush();
        }
    }

    /**
     * Creates user and bridgeUser from credential given by BridgeApiAuthenticator.
     *
     * @param array $credentials
     * @param bool  $doFlush
     *
     * @return User
     */
    public function createBridgeUser(array $credentials, bool $doFlush = false)
    {
        $user = null;

        if (false === empty($credentials['user_id']) && false === empty($credentials['auth_token'])) {
            $modifier = new AppendSegment('user/getbyid');
            $uri = $modifier->process($this->baseUri);

            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
                'x-access-token' => $credentials['auth_token'],
            ];

            $userRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode(['id' => $credentials['user_id']]));
            $userResponse = $this->client->send($userRequest);
            $userResult = \json_decode((string) $userResponse->getBody(), true);

            if (200 === $userResult['status'] && 1 === $userResult['flag']) {
                // create user here
                $userData = $userResult['data'];

                // mapping fields to params
                $userId = $userData['_id'];
                $givenName = $userData['first_name'];
                $familyName = $userData['last_name'];
                $email = $userData['email'];

                if (false === empty($credentials['user'])) {
                    $user = $credentials['user'];
                } else {
                    $person = new Person();
                    $person->setGivenName($givenName);
                    $person->setFamilyName($familyName);

                    $user = new User();
                    $user->setEmail(\strtolower($email));
                    $user->addRole(AuthorizationRole::ROLE_ADMIN);

                    $customerAccount = new CustomerAccount();
                    $customerAccount->setType(new AccountType(AccountType::INDIVIDUAL));
                    $customerAccount->setPersonDetails($person);
                    $customerAccount->setStatus(new CustomerAccountStatus(CustomerAccountStatus::INACTIVE));

                    $user->setCustomerAccount($customerAccount);

                    $this->entityManager->persist($person);
                    $this->entityManager->persist($customerAccount);
                    $this->entityManager->persist($user);
                }

                $bridgeUser = new BridgeUser();
                $bridgeUser->setUser($user);
                $bridgeUser->setBridgeUserId($userId);
                $bridgeUser->setAuthToken($credentials['auth_token']);

                $this->entityManager->persist($bridgeUser);

                if (true === $doFlush) {
                    $this->entityManager->flush();
                }
            } else {
                throw new BadRequestHttpException('Unable to find user.');
            }
        }

        return $user;
    }

    /**
     * Creates a user in the old version.
     *
     * @param User       $user
     * @param BridgeUser $creator
     *
     * @return string|null
     */
    public function createUser(User $user, BridgeUser $creator)
    {
        $userId = null;
        $person = $user->getCustomerAccount()->getPersonDetails();
        $corporation = $user->getCustomerAccount()->getCorporationDetails();

        $userData = [
            'email' => $user->getEmail(),
            'password' => $user->getPlainPassword(),
            'role_id' => [],
            'login_type' => 'partner',
        ];

        if (null !== $person) {
            if (null !== $person->getGivenName()) {
                $userData['first_name'] = $person->getGivenName();
            }

            if (null !== $person->getFamilyName()) {
                $userData['last_name'] = $person->getFamilyName();
            }

            if (false === isset($userData['first_name']) && false === isset($userData['last_name'])) {
                $userData['first_name'] = $person->getName();
            }
        } elseif (null !== $corporation) {
            $userData['first_name'] = $corporation->getName();
        } else {
            return $userId;
        }

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'x-access-token' => $creator->getAuthToken(),
        ];

        $modifier = new AppendSegment('bridge/user');
        $uri = $modifier->process($this->baseUri);

        $createUserRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($userData));
        $createUserResponse = $this->client->send($createUserRequest);
        $createUserResult = \json_decode((string) $createUserResponse->getBody(), true);

        if (200 === $createUserResult['status'] && 1 === $createUserResult['flag']) {
            $userId = $createUserResult['data']['_id'];
        } else {
            throw new BadRequestHttpException($createUserResult['data']['message']);
        }

        return $userId;
    }

    /**
     * Updates a user in the old version.
     *
     * @param User       $user
     * @param BridgeUser $agent
     */
    public function updateUser(User $user, BridgeUser $agent)
    {
        $person = $user->getCustomerAccount()->getPersonDetails();
        $corporation = $user->getCustomerAccount()->getCorporationDetails();
        $bridgeUser = $user->getBridgeUser();

        if (null === $bridgeUser) {
            return;
        }

        $userData = [
            'id' => $bridgeUser->getBridgeUserId(),
            'email' => $user->getEmail(),
            'role_id' => [],
            'login_type' => 'partner',
        ];

        if (null !== $person) {
            if (null !== $person->getGivenName()) {
                $userData['first_name'] = $person->getGivenName();
            }

            if (null !== $person->getFamilyName()) {
                $userData['last_name'] = $person->getFamilyName();
            }

            if (false === isset($userData['first_name']) && false === isset($userData['last_name'])) {
                $userData['first_name'] = $person->getName();
            }
        } elseif (null !== $corporation) {
            $userData['first_name'] = $corporation->getName();
        } else {
            return;
        }

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'x-access-token' => $agent->getAuthToken(),
        ];

        $modifier = new AppendSegment('user/updatebyid');
        $uri = $modifier->process($this->baseUri);

        $createUserRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($userData));
        $createUserResponse = $this->client->send($createUserRequest);
        $createUserResult = \json_decode((string) $createUserResponse->getBody(), true);

        if (!(200 === $createUserResult['status'] && 1 === $createUserResult['flag'])) {
            throw new BadRequestHttpException($createUserResult['data']['message']);
        }
    }

    /**
     * Authenticates user and returns an auth token if successful.
     *
     * @param string|null $email
     * @param string|null $password
     *
     * @return string|null
     */
    public function getAuthToken(string $email = null, string $password = null)
    {
        $authToken = null;

        if (null !== $this->bridgeApiUrl && null !== $email && null !== $password) {
            $modifier = new AppendSegment('user/partnership/login');
            $loginUri = $modifier->process($this->baseUri);

            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
            ];

            $loginCredentials = [
                'email' => $email,
                'password' => $password,
            ];

            $loginRequest = new GuzzlePsr7Request('POST', $loginUri, $headers, \json_encode($loginCredentials));
            $loginResponse = $this->client->send($loginRequest);
            $loginResult = \json_decode((string) $loginResponse->getBody(), true);

            if (200 === $loginResult['status'] && 1 === $loginResult['flag']) {
                $authToken = $loginResult['data']['authtoken'];
            } else {
                throw new BadRequestHttpException('Unable to authenticate user.');
            }
        }

        return $authToken;
    }
}
