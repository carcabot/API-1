<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Enum\AccountCategory;
use App\Enum\AuthorizationRole;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SwitchUserSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::SWITCH_USER => [
                ['checkSwitchAuthorisation', 0],
            ],
        ];
    }

    public function checkSwitchAuthorisation(SwitchUserEvent $switchEvent)
    {
        $impersonatorUser = null;

        if (null !== $switchEvent->getToken()) {
            foreach ($switchEvent->getToken()->getRoles() as $role) {
                if ($role instanceof SwitchUserRole) {
                    $impersonatorUser = $role->getSource()->getUser();
                    break;
                }
            }
        }

        // ROLE_HOMEPAGE can only impersonate sales representatives/partners
        if (null !== $impersonatorUser &&
            $impersonatorUser instanceof User &&
            \in_array(AuthorizationRole::ROLE_HOMEPAGE, $impersonatorUser->getRoles(), true) &&
            $switchEvent->getTargetUser() instanceof User
        ) {
            foreach ($switchEvent->getTargetUser()->getCustomerAccount()->getCategories() as $category) {
                if (\in_array($category, [
                    AccountCategory::PARTNER,
                    AccountCategory::PARTNER_CONTACT_PERSON,
                    AccountCategory::SALES_REPRESENTATIVE,
                ], true)) {
                    return;
                }
            }

            throw new AccessDeniedHttpException('You have no power here.');
        }
    }
}
