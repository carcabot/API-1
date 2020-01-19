<?php

declare(strict_types=1);

namespace App\Model;

use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestNumber;
use App\Domain\Command\CustomerAccount\UpdateAccountNumber;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountPostalAddress;
use App\Entity\Identification;
use App\Entity\Person;
use App\Entity\PostalAddress;
use App\Enum\AccountType;
use App\Enum\CustomerAccountStatus;
use App\Enum\IdentificationName;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\Serializer\SerializerInterface;

class ApplicationRequestAccountClosureCreator
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
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     * @param string                 $timezone
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, SerializerInterface $serializer, string $timezone)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->timezone = new \DateTimeZone($timezone);
    }

    public function processArrayData(array $data): array
    {
        $results = [];

        foreach ($data as $dataum) {
            $results[] = $this->createApplicationRequest($dataum);
        }

        return $results;
    }

    public function createApplicationRequest(array $data): array
    {
        $contract = null;
        $refundee = null;
        $refundeeDetails = null;
        $personDetails = null;
        $corporationDetails = null;

        try {
            if (isset($data['applicationRequest'])) {
                $applicationRequestData = $data['applicationRequest'];

                if (isset($applicationRequestData['contractNumber'])) {
                    $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $applicationRequestData['contractNumber']]);
                    unset($applicationRequestData['contractNumber']);
                } else {
                    throw new \Exception('ContractNumber is required.');
                }

                if (isset($applicationRequestData['differentPayeeIndicator'])) {
                    if (true === $applicationRequestData['differentPayeeIndicator']) {
                        $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customer');
                        $expr = $qb->expr();

                        $customers = $qb->leftJoin('customer.personDetails', 'person')
                            ->leftJoin('person.identifiers', 'identity')
                            ->where($expr->eq('identity.value', ':nric'))
                            ->andWhere($expr->eq('identity.name', ':name'))
                            ->setParameter('nric', $applicationRequestData['refundeeDetails']['identifiers'][0]['value'])
                            ->setParameter('name', $expr->literal(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))
                            ->getQuery()
                            ->getResult();

                        if (\count($customers) > 0) {
                            $refundee = $customers[0];
                            if (AccountType::INDIVIDUAL === $customers[0]->getType()->getValue()) {
                                $refundeeDetails = clone $customers[0]->getPersonDetails();
                                $personDetails = clone $customers[0]->getPersonDetails();

                                $this->entityManager->persist($refundeeDetails);
                                $this->entityManager->persist($personDetails);
                            } elseif (AccountType::CORPORATE === $customers[0]->getType()->getValue()) {
                                $corporationDetails = clone $customers[0]->getCorporationDetails();
                                $this->entityManager->persist($corporationDetails);
                            }
                        } else {
                            $identifier = new Identification();
                            $identifier->setName(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
                            $identifier->setValue($applicationRequestData['refundeeDetails']['identifiers'][0]['value']);
                            $this->entityManager->persist($identifier);

                            $refundeePersonDetails = new Person();
                            $refundeePersonDetails->addIdentifier($identifier);
                            $refundeePersonDetails->setName($applicationRequestData['refundeeDetails']['name']);
                            $this->entityManager->persist($refundeePersonDetails);
                            $this->entityManager->flush();

                            $address = $this->serializer->deserialize(\json_encode($applicationRequestData['addresses'][0]), PostalAddress::class, 'json', ['groups' => ['postal_address_write']]);
                            $customerAccountPostalAddress = new CustomerAccountPostalAddress();

                            if ($address instanceof PostalAddress) {
                                $this->entityManager->persist($address);
                                $customerAccountPostalAddress->setAddress($address);
                            }

                            $refundee = new CustomerAccount();
                            $refundee->setPersonDetails($refundeePersonDetails);
                            $refundee->addAddress($customerAccountPostalAddress);
                            $refundee->setStatus(new CustomerAccountStatus(CustomerAccountStatus::ACTIVE));
                            $refundee->setType(new AccountType(AccountType::INDIVIDUAL));

                            $customerAccountPostalAddress->setCustomerAccount($refundee);

                            $refundeeDetails = clone $refundeePersonDetails;
                            $this->entityManager->persist($refundeeDetails);

                            $personDetails = clone $refundeePersonDetails;
                            $this->entityManager->persist($personDetails);

                            $this->entityManager->getConnection()->beginTransaction();
                            $this->entityManager->getConnection()->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;');
                            $this->commandBus->handle(new UpdateAccountNumber($refundee));
                            $this->entityManager->persist($refundee);
                            $this->entityManager->flush();
                            $this->entityManager->getConnection()->commit();
                        }
                    } else {
                        if (null !== $contract) {
                            $refundee = $contract->getCustomer();

                            if (AccountType::INDIVIDUAL === $contract->getCustomer()->getType()->getValue() && null !== $contract->getCustomer()->getPersonDetails()) {
                                $refundeeDetails = clone $contract->getCustomer()->getPersonDetails();
                                $personDetails = clone $contract->getCustomer()->getPersonDetails();

                                $this->entityManager->persist($refundeeDetails);
                                $this->entityManager->persist($personDetails);
                            } elseif (AccountType::CORPORATE === $contract->getCustomer()->getType()->getValue() && null !== $contract->getCustomer()->getCorporationDetails()) {
                                $corporationDetails = clone $contract->getCustomer()->getCorporationDetails();
                                $this->entityManager->persist($corporationDetails);
                            }
                        }
                    }

                    unset($applicationRequestData['differentPayeeIndicator']);
                    unset($applicationRequestData['refundeeDetails']);
                }

                $applicationRequest = $this->serializer->deserialize(\json_encode($applicationRequestData), ApplicationRequest::class, 'json', ['groups' => [
                    'application_request_write',
                    'postal_address_write',
                ]]);

                if ($applicationRequest instanceof ApplicationRequest) {
                    if (isset($applicationRequestData['preferredEndDate'])) {
                        $applicationRequest->setPreferredEndDate(new \DateTime($applicationRequestData['preferredEndDate'], $this->timezone));
                    }

                    $applicationRequest->setContract($contract);
                    if (null !== $contract) {
                        $applicationRequest->setCustomer($contract->getCustomer());
                        $applicationRequest->setCustomerType($contract->getCustomer()->getType());

                        foreach ($contract->getAddresses() as $address) {
                            $postalAddress = clone $address->getAddress();
                            $this->entityManager->persist($postalAddress);
                            $applicationRequest->addAddress($postalAddress);
                        }
                    }

                    $applicationRequest->setRefundee($refundee);
                    $applicationRequest->setRefundeeDetails($refundeeDetails);
                    $applicationRequest->setPersonDetails($personDetails);
                    $applicationRequest->setCorporationDetails($corporationDetails);

                    $this->entityManager->getConnection()->beginTransaction();
                    $this->entityManager->getConnection()->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;');
                    $this->commandBus->handle(new UpdateApplicationRequestNumber($applicationRequest));
                    $this->entityManager->persist($applicationRequest);
                    $this->entityManager->flush();
                    $this->entityManager->getConnection()->commit();

                    return [
                        'FRCContractClosureNumber' => $applicationRequest->getExternalApplicationRequestNumber(),
                        'CRMContractClosureNumber' => $applicationRequest->getApplicationRequestNumber(),
                        'ProcessStatus' => 1,
                        'Message' => 'New Application Create Successful.',
                    ];
                }

                throw new \Exception('Cannot create application request.');
            }

            throw new \Exception('No data.');
        } catch (\Exception $ex) {
            $this->entityManager->clear();

            return [
                'FRCContractClosureNumber' => 'No data.' !== $ex->getMessage() ? $data['applicationRequest']['externalApplicationRequestNumber'] : '',
                'CRMContractClosureNumber' => '',
                'ProcessStatus' => 0,
                'Message' => $ex->getMessage(),
            ];
        }
    }
}
