<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\User;
use App\EventListener\UserPasswordResetRequestedUserEmailSendingListener;
use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use PHPUnit\Framework\TestCase;

class UserPasswordResetRequestedUserEmailSendingListenerTest extends TestCase
{
    public function testOnCreateToken()
    {
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getEmail()->willReturn('email@test.com');
        $userProphecy->getUsername()->willReturn('testUsername');
        $user = $userProphecy->reveal();

        $passwordTokenProphecy = $this->prophesize(AbstractPasswordToken::class);
        $passwordTokenProphecy->getToken()->willReturn('testToken');
        $passwordTokenProphecy->getUser()->willReturn($user);
        $passwordToken = $passwordTokenProphecy->reveal();

        $eventProphecy = $this->prophesize(ForgotPasswordEvent::class);
        $eventProphecy->getPasswordToken()->willReturn($passwordToken);
        $event = $eventProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($user)->willReturn('testIri');
        $iriConverter = $iriConverterProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueueProphecy->push(new Job([
            'data' => [
                'token' => 'testToken',
                'user' => 'testIri',
            ],
            'type' => JobType::USER_PASSWORD_RESET_REQUESTED,
            'user' => [
                '@id' => 'testIri',
                'email' => 'email@test.com',
                'username' => 'testUsername',
            ],
        ]))->shouldBeCalled();
        $disqueQueue = $disqueQueueProphecy->reveal();

        $userPasswordResetRequestedUserEmailSendingListener = new UserPasswordResetRequestedUserEmailSendingListener($disqueQueue, $iriConverter);
        $userPasswordResetRequestedUserEmailSendingListener->onCreateToken($event);
    }
}
