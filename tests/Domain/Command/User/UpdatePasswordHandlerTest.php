<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\User;

use App\Domain\Command\User\UpdatePassword;
use App\Domain\Command\User\UpdatePasswordHandler;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UpdatePasswordHandlerTest extends TestCase
{
    public function testUpdatePassword()
    {
        $plainPassword = 'iLoveUcentric';
        $encodedPassword = 'iReallyLoveUcentric';

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->setPassword($encodedPassword)->shouldBeCalled();
        $user = $userProphecy->reveal();

        $userPasswordEncoderProphecy = $this->prophesize(UserPasswordEncoderInterface::class);
        $userPasswordEncoderProphecy->encodePassword($user, $plainPassword)->shouldBeCalled()->willReturn($encodedPassword);

        $userPasswordEncoder = $userPasswordEncoderProphecy->reveal();
        $updatePasswordHandler = new UpdatePasswordHandler($userPasswordEncoder);
        $updatePasswordHandler->handle(new UpdatePassword($user, $plainPassword));
    }

    public function testUpdatePasswordShouldSetPasswordToNull()
    {
        $plainPassword = null;

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->setPassword(null)->shouldBeCalled();
        $user = $userProphecy->reveal();

        $userPasswordEncoderProphecy = $this->prophesize(UserPasswordEncoderInterface::class);
        $userPasswordEncoderProphecy->encodePassword(Argument::any())->shouldNotBeCalled();
        $userPasswordEncoder = $userPasswordEncoderProphecy->reveal();

        $updatePasswordHandler = new UpdatePasswordHandler($userPasswordEncoder);
        $updatePasswordHandler->handle(new UpdatePassword($user, $plainPassword));
    }
}
