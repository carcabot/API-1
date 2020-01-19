<?php

declare(strict_types=1);

namespace App\Model;

use App\Domain\Command\CustomerAccount\UpdateBlacklistNotes;
use App\Domain\Command\CustomerAccount\UpdateBlacklistStatus;
use App\Entity\CustomerAccount;
use App\Entity\CustomerBlacklist;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerBlacklistUpdater
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     * @param ValidatorInterface     $validator
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    public function processArrayData(array $data): array
    {
        $results = [];
        foreach ($data as $datum) {
            $results[] = $this->updateCustomerBlacklist($datum);
        }

        return $results;
    }

    private function updateCustomerBlacklist(array $data)
    {
        $customer = null;
        $customerBlacklist = $this->serializer->deserialize(\json_encode($data), CustomerBlacklist::class, 'jsonld', ['groups' => ['customer_blacklist_write']]);

        if ($customerBlacklist instanceof CustomerBlacklist) {
            $errors = $this->validator->validate($customerBlacklist);

            if (\count($errors) > 0) {
                $errorMessages = [];
                $data['status'] = '0';

                foreach ($errors as $error) {
                    $errorMessages[] = ['error' => $error->getPropertyPath().': '.$error->getMessage()];
                }
                $data['errors'] = $errorMessages;

                return $data;
            }

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
                $data['status'] = '0';
                $data['errors'] = ['error' => 'No customer found.'];
            } else {
                /** @var CustomerAccount $customer */
                $customer = $customerAccounts[0];

                $validBlacklist = $this->commandBus->handle(new UpdateBlacklistStatus($customerBlacklist, $customer));

                if (false === $validBlacklist) {
                    $data['status'] = '0';
                    if (null === $customer->getDateBlacklisted()) {
                        $data['errors'] = ['error' => 'Customer is not on the blacklist.'];
                    } else {
                        $data['errors'] = ['error' => 'Customer is already on the blacklist.'];
                    }
                } else {
                    $this->commandBus->handle(new UpdateBlacklistNotes($customerBlacklist, $customer));
                }
            }

            if (!isset($data['errors'])) {
                $this->entityManager->persist($customerBlacklist);

                if (null !== $customer) {
                    $this->entityManager->persist($customer);
                }

                $this->entityManager->flush();

                $data['status'] = '1';
            }
        }

        return $data;
    }
}
