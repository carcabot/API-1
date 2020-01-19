<?php

declare(strict_types=1);

namespace App\Security\TwoFactorAuthentication;

use App\Disque\JobType;
use App\Entity\User;
use App\Enum\TwoFactorAuthenticationStatus;
use App\Enum\TwoFactorAuthenticationType;
use App\Model\SmsUpdater;
use App\WebService\SMS\ClientInterface as SMSClient;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Zend\Diactoros\Response\EmptyResponse;

class TwoFactorAuthenticationHelper
{
    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SMSClient
     */
    private $smsClient;

    /**
     * @var SmsUpdater
     */
    private $smsUpdater;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var PhoneNumberUtil
     */
    private $phoneNumberUtil;

    /**
     * @var JWTTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @param EntityManagerInterface   $em
     * @param DisqueQueue              $emailsQueue
     * @param PhoneNumberUtil          $phoneNumberUtil
     * @param SMSClient                $smsClient
     * @param SmsUpdater               $smsUpdater
     * @param JWTTokenManagerInterface $tokenManager
     * @param TokenStorageInterface    $tokenStorage
     */
    public function __construct(EntityManagerInterface $em, DisqueQueue $emailsQueue, PhoneNumberUtil $phoneNumberUtil, SMSClient $smsClient, SmsUpdater $smsUpdater, JWTTokenManagerInterface $tokenManager, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $em;
        $this->emailsQueue = $emailsQueue;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->smsClient = $smsClient;
        $this->smsUpdater = $smsUpdater;
        $this->tokenStorage = $tokenStorage;
        $this->tokenManager = $tokenManager;
    }

    public function generateAndSend(User $user)
    {
        $code = \mt_rand(100000, 999999);
        $user->setTwoFactorAuthenticationCode((string) $code);
        $user->setTwoFactorAuthenticationStatus(new TwoFactorAuthenticationStatus(TwoFactorAuthenticationStatus::PENDING));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->sendCode($user);
    }

    private function sendCode(User $user)
    {
        if (null !== $user->getTwoFactorAuthenticationType()
            && TwoFactorAuthenticationType::EMAIL === $user->getTwoFactorAuthenticationType()->getValue()) {
            $job = new DisqueJob([
                'data' => [
                    'code' => $user->getTwoFactorAuthenticationCode(),
                ],
                'type' => JobType::USER_GENERATED_TWO_FACTOR_AUTHENTICATION_CODE,
                'recipient' => $user->getEmail(),
            ]);

            $this->emailsQueue->push($job);
        } elseif (null !== $user->getTwoFactorAuthenticationType()
            && TwoFactorAuthenticationType::SMS === $user->getTwoFactorAuthenticationType()->getValue()) {
            $userNumber = null;
            $token = $user->getTwoFactorAuthenticationCode();
            $message = "You have requested for a Login Token to Ucentric. \n Kindly enter $token in the next screen when prompted to complete your login.";
            if (null !== $user->getTwoFactorAuthenticationRecipient()
                && TwoFactorAuthenticationType::SMS === $user->getTwoFactorAuthenticationType()->getValue()) {
                $userNumber = $user->getTwoFactorAuthenticationRecipient();
            }

            if (!empty($userNumber) && !empty($message)) {
                // $recipient = $this->phoneNumberUtil->format($userNumber, PhoneNumberFormat::E164);
                $result = $this->smsClient->send($userNumber, $message);

                $this->smsUpdater->create($result);
            }
        }
    }

    /**
     * @param User   $user
     * @param string $code
     *
     * @return bool
     */
    public function checkCode(User $user, $code)
    {
        return $user->getTwoFactorAuthenticationCode() === $code;
    }

    public function verify(string $authCode)
    {
        $user = null;
        if (null !== $this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();
        }

        if (!$user instanceof User) {
            return new EmptyResponse(400);
        }

        if (!$user->hasTwoFactorAuthentication()) {
            return new JsonResponse(['message' => 'Two Factor Authentication not enabled for this User'], 400);
        } elseif (empty($user->getTwoFactorAuthenticationCode())) {
            return new JsonResponse(['message' => 'User already verified'], 400);
        }
        if ($user->getTwoFactorAuthenticationCode() === $authCode) {
            $user->setTwoFactorAuthenticationCode(null);
            $user->setTwoFactorAuthenticationStatus(new TwoFactorAuthenticationStatus(TwoFactorAuthenticationStatus::COMPLETE));
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Successfully Authenticated', 'token' => $this->tokenManager->create($user)]);
        }

        return new JsonResponse(['message' => 'Authentication Code does not match'], 400);
    }
}
