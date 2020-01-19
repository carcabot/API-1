<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Domain\Command\CustomerAccount\UpdateSalesRepresentativeAccountNumber;
use App\Entity\CustomerAccount;
use App\Enum\AccountCategory;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CustomerAccountEventSubscriber implements EventSubscriberInterface
{
    use Traits\RunningNumberLockTrait;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param string                 $timezone
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, string $timezone)
    {
        $this->commandBus = $commandBus;
        $this->setEntityManager($entityManager);
        $this->setLocked(false);
        $this->timezone = new \DateTimeZone($timezone);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['generateSalesRepresentativeAccountNumber', EventPriorities::PRE_WRITE + 1],
            ],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function generateSalesRepresentativeAccountNumber(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if (!$controllerResult instanceof CustomerAccount) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var CustomerAccount $customerAccount */
        $customerAccount = $controllerResult;

        if (!\in_array(AccountCategory::PARTNER, $customerAccount->getCategories(), true)) {
            return;
        }

        if (null === $customerAccount->getCorporationDetails()) {
            return;
        }

        // @todo find better way to prevent entity update before transactions
        foreach ($customerAccount->getCorporationDetails()->getEmployees() as $employeeRole) {
            if (null === $employeeRole->getEmployee()->getAccountNumber()) {
                $this->startLockTransaction();
                $this->commandBus->handle(new UpdateSalesRepresentativeAccountNumber($customerAccount, $employeeRole->getEmployee()));
                $this->entityManager->persist($employeeRole->getEmployee());
                $this->entityManager->flush();
                $this->endLockTransaction();
            }
        }
    }
}
