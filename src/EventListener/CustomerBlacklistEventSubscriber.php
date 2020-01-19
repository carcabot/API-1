<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Domain\Command\CustomerAccount\UpdateBlacklistNotes;
use App\Domain\Command\CustomerAccount\UpdateBlacklistStatus;
use App\Entity\CustomerAccount;
use App\Entity\CustomerBlacklist;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class CustomerBlacklistEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['updateCustomer', EventPriorities::POST_VALIDATE - 1],
            ],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function updateCustomer(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if (!$controllerResult instanceof CustomerBlacklist) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
        ], true)) {
            return;
        }

        /** @var CustomerBlacklist $customerBlacklist */
        $customerBlacklist = $controllerResult;

        $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customer');
        $expr = $qb->expr();

        $customerAccounts = $qb->leftJoin('customer.personDetails', 'person')
            ->leftJoin('customer.corporationDetails', 'corporation')
            ->leftJoin('person.identifiers', 'personIdentity')
            ->leftJoin('corporation.identifiers', 'corporationIdentity')
            ->where(
                $expr->orX(
                    $expr->andX(
                        $expr->eq('personIdentity.value', ':identity'),
                        $expr->eq('person.name', ':name')
                    ),
                    $expr->andX(
                        $expr->eq('corporationIdentity.value', ':identity'),
                        $expr->eq('corporation.name', ':name')
                    )
                )
            )
            ->setParameter('identity', $customerBlacklist->getIdentification())
            ->setParameter('name', $customerBlacklist->getName())
            ->getQuery()
            ->getResult();

        if (\count($customerAccounts) < 1) {
            throw new NotFoundHttpException('No customer found.');
        }

        /** @var CustomerAccount $customer */
        $customer = $customerAccounts[0];

        $validBlacklist = $this->commandBus->handle(new UpdateBlacklistStatus($customerBlacklist, $customer));

        if (false === $validBlacklist) {
            if (null === $customer->getDateBlacklisted()) {
                $errorMessage = 'Customer is not on the blacklist.';
            } else {
                $errorMessage = 'Customer is already on the blacklist.';
            }

            throw new BadRequestHttpException($errorMessage);
        }

        $this->commandBus->handle(new UpdateBlacklistNotes($customerBlacklist, $customer));
    }
}
