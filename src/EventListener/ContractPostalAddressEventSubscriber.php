<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\ContractPostalAddress;
use App\Entity\CustomerAccountPostalAddress;
use App\Entity\PostalAddress;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ContractPostalAddressEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['cloneContractPostalAddress', EventPriorities::PRE_WRITE + 1],
            ],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function cloneContractPostalAddress(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if (!$controllerResult instanceof ContractPostalAddress) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var ContractPostalAddress $contractPostalAddress */
        $contractPostalAddress = $controllerResult;
        $postalAddress = $contractPostalAddress->getAddress();
        $customer = $contractPostalAddress->getContract()->getCustomer();

        $qb = $this->entityManager->getRepository(CustomerAccountPostalAddress::class)->createQueryBuilder('customerAddress');
        $expr = $qb->expr();

        $existingCustomerAddresses = $qb->leftJoin('customerAddress.address', 'address')
            ->where($expr->eq('address.text', ':text'))
            ->andWhere($expr->eq('customerAddress.customerAccount', ':customer'))
            ->setParameter('text', $postalAddress->__toString())
            ->setParameter('customer', $customer->getId())
            ->getQuery()
            ->getResult();

        $existingCustomerAddress = \reset($existingCustomerAddresses);

        if (false === $existingCustomerAddress) {
            $customerPostalAddress = new CustomerAccountPostalAddress();
            $customerPostalAddress->setCustomerAccount($customer);
            $copiedPostalAddress = new PostalAddress();
            $customerPostalAddress->setAddress($copiedPostalAddress);

            // scuffed object copy
            $classMethods = \get_class_methods(PostalAddress::class);
            foreach ($classMethods as $classMethod) {
                $position = \strpos($classMethod, 'set');
                if (false === $position) {
                    continue;
                }

                $getterMethod = \sprintf('%s%s', 'get', \substr($classMethod, 3));
                if (false === \method_exists($postalAddress, $getterMethod)) {
                    continue;
                }

                $copiedPostalAddress->$classMethod($postalAddress->$getterMethod());
            }

            $this->entityManager->persist($customerPostalAddress);
        }
    }
}
