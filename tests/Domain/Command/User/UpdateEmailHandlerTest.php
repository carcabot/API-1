<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\User;

use App\Domain\Command\User\UpdateEmail;
use App\Domain\Command\User\UpdateEmailHandler;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UpdateEmailHandlerTest extends TestCase
{
    public function testUpdateEmail()
    {
        $email = 'DEV@UCENTRIC.SISGROUP.SG';

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->setEmail('dev@ucentric.sisgroup.sg')->shouldBeCalled();
        $user = $userProphecy->reveal();

        $updateEmailHandler = new UpdateEmailHandler();
        $updateEmailHandler->handle(new UpdateEmail($user, $email));
    }
}
