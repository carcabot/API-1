<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Domain\Command\Contract\UpdatePointCreditsActions as UpdateContractPointCreditsActions;
use App\Domain\Command\CustomerAccount\UpdateMoneyCreditsActions as UpdateCustomerAccountMoneyCreditsActions;
use App\Domain\Command\CustomerAccount\UpdatePointCreditsActions as UpdateCustomerAccountPointCreditsActions;
use App\Domain\Command\UpdateCreditsAction\CreateReciprocalAction;
use App\Entity\Contract;
use App\Entity\CreditsAdditionInterface;
use App\Entity\CreditsSubtractionInterface;
use App\Entity\CustomerAccount;
use App\Entity\MoneyCreditsTransaction;
use App\Entity\PointCreditsTransaction;
use App\Entity\UpdateCreditsAction;
use App\Service\AuthenticationHelper;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class UpdateCreditsActionEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuthenticationHelper
     */
    private $authHelper;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param AuthenticationHelper   $authHelper
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(AuthenticationHelper $authHelper, CommandBus $commandBus, EntityManagerInterface $entityManager)
    {
        $this->authHelper = $authHelper;
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['updateCreditsAddition', EventPriorities::POST_VALIDATE - 1],
                ['updateCreditsSubtraction', EventPriorities::POST_VALIDATE - 1],
            ],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function updateCreditsAddition(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if (!$controllerResult instanceof UpdateCreditsAction) {
            return;
        }

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        $user = $this->authHelper->getAuthenticatedUser();

        if (null === $user) {
            throw new AccessDeniedHttpException('You have no power here.');
        }

        $customerAccount = $user->getCustomerAccount();

        /** @var UpdateCreditsAction $updateCreditsAction */
        $updateCreditsAction = $controllerResult;

        if ($updateCreditsAction instanceof CreditsAdditionInterface) {
            list($commandClass, $errorMessage) = $this->processCreditsAction($updateCreditsAction);

            if (null !== $commandClass && null !== $updateCreditsAction->getObject()) {
                $this->commandBus->handle(new $commandClass($updateCreditsAction->getObject(), $updateCreditsAction));
            } else {
                throw new BadRequestHttpException($errorMessage);
            }
        }
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function updateCreditsSubtraction(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if (!$controllerResult instanceof UpdateCreditsAction) {
            return;
        }

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        $user = $this->authHelper->getAuthenticatedUser();

        if (null === $user) {
            throw new AccessDeniedHttpException('You have no power here.');
        }

        $customerAccount = $user->getCustomerAccount();

        /** @var UpdateCreditsAction $updateCreditsAction */
        $updateCreditsAction = $controllerResult;
        $errorMessage = null;
        $objectType = null;
        $objectTypeKey = null;
        $object = null;

        $commands = [
            'money' => [
                'customerAccount' => UpdateCustomerAccountMoneyCreditsActions::class,
            ],
            'points' => [
                'contract' => UpdateContractPointCreditsActions::class,
                'customerAccount' => UpdateCustomerAccountPointCreditsActions::class,
            ],
        ];

        if ($updateCreditsAction instanceof CreditsSubtractionInterface) {
            $sufficientCredits = false;
            $object = $updateCreditsAction->getObject();
            $transaction = $updateCreditsAction->getCreditsTransaction();

            if ($transaction instanceof PointCreditsTransaction) {
                $currentBalance = $object->getPointCreditsBalance()->getValue();

                if ($currentBalance >= $transaction->getAmount()->getValue()) {
                    $sufficientCredits = true;
                    $objectType = 'points';
                }
            } elseif ($transaction instanceof MoneyCreditsTransaction) {
                $balance = $object->getMoneyCreditsBalance()->getValue();

                if ($balance >= $transaction->getAmount()->getValue()) {
                    $sufficientCredits = true;
                    $objectType = 'money';
                }
            }

            if (true === $sufficientCredits) {
                if ($object instanceof CustomerAccount) {
                    $objectTypeKey = 'customerAccount';
                } elseif ($object instanceof Contract) {
                    $objectTypeKey = 'contract';
                } else {
                    $errorMessage = 'Error! Credits are applicable only to Contract and CustomerAccount.';
                }
            } else {
                $errorMessage = 'Insufficient credits.';
            }
        }

        if (null !== $errorMessage) {
            throw new BadRequestHttpException($errorMessage);
        }

        if (null !== $object && null !== $objectType && null !== $objectTypeKey && !empty($commands[$objectType][$objectTypeKey])) {
            $this->commandBus->handle(new $commands[$objectType][$objectTypeKey]($object, $updateCreditsAction));
            $this->updateReciprocalCreditsAction($updateCreditsAction);
        }
    }

    private function updateReciprocalCreditsAction(UpdateCreditsAction $updateCreditsAction)
    {
        $reciprocalAction = $this->commandBus->handle(new CreateReciprocalAction($updateCreditsAction));

        if ($reciprocalAction instanceof UpdateCreditsAction && ($reciprocalAction instanceof CreditsAdditionInterface || $reciprocalAction instanceof CreditsSubtractionInterface)) {
            $this->entityManager->persist($reciprocalAction);

            list($commandClass, $errorMessage) = $this->processCreditsAction($reciprocalAction);

            if (null !== $commandClass && null !== $reciprocalAction->getObject()) {
                $this->commandBus->handle(new $commandClass($reciprocalAction->getObject(), $reciprocalAction));
            } else {
                throw new BadRequestHttpException($errorMessage);
            }
        }
    }

    private function processCreditsAction(UpdateCreditsAction $updateCreditsAction)
    {
        $commandClass = null;
        $errorMessage = null;
        $objectType = null;
        $objectTypeKey = null;
        $object = null;

        $commands = [
            'money' => [
                'customerAccount' => UpdateCustomerAccountMoneyCreditsActions::class,
            ],
            'points' => [
                'contract' => UpdateContractPointCreditsActions::class,
                'customerAccount' => UpdateCustomerAccountPointCreditsActions::class,
            ],
        ];

        if ($updateCreditsAction instanceof CreditsAdditionInterface) {
            $object = $updateCreditsAction->getObject();
            $transaction = $updateCreditsAction->getCreditsTransaction();

            if ($transaction instanceof PointCreditsTransaction) {
                $objectType = 'points';
            } elseif ($transaction instanceof MoneyCreditsTransaction) {
                $objectType = 'money';
            }

            if ($object instanceof CustomerAccount) {
                $objectTypeKey = 'customerAccount';
            } elseif ($object instanceof Contract) {
                $objectTypeKey = 'contract';
            } else {
                $errorMessage = 'Error! Credits are applicable only to Contract and CustomerAccount.';
            }
        }

        if (null !== $object && null !== $objectType && null !== $objectTypeKey && !empty($commands[$objectType][$objectTypeKey])) {
            $commandClass = $commands[$objectType][$objectTypeKey];
        }

        return [
            $commandClass,
            $errorMessage,
        ];
    }
}
