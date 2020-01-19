<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\User\UpdatePassword;
use CoopTilleuls\ForgotPasswordBundle\Event\ForgotPasswordEvent;
use League\Tactician\CommandBus;

class UserResetPasswordUpdaterListener
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @param CommandBus $commandBus
     */
    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @param ForgotPasswordEvent $event
     */
    public function onUpdatePassword(ForgotPasswordEvent $event)
    {
        $user = $event->getPasswordToken()->getUser();
        $plainPassword = $event->getPassword();

        $this->commandBus->handle(new UpdatePassword($user, $plainPassword));
    }
}
