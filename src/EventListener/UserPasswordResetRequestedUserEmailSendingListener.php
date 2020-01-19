<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;

class UserPasswordResetRequestedUserEmailSendingListener
{
    /**
     * @var DisqueQueue
     */
    private $disqueQueue;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @param DisqueQueue           $disqueQueue
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(DisqueQueue $disqueQueue, IriConverterInterface $iriConverter)
    {
        $this->disqueQueue = $disqueQueue;
        $this->iriConverter = $iriConverter;
    }

    /**
     * @param ForgotPasswordEvent $event
     */
    public function onCreateToken(ForgotPasswordEvent $event)
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();

        $job = new DisqueJob([
            'data' => [
                'token' => $passwordToken->getToken(),
                'user' => $this->iriConverter->getIriFromItem($user),
            ],
            'type' => JobType::USER_PASSWORD_RESET_REQUESTED,
            'user' => [
                '@id' => $this->iriConverter->getIriFromItem($user),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
            ],
        ]);
        $this->disqueQueue->push($job);
    }
}
