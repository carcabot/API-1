<?php

declare(strict_types=1);

namespace App\Model;

use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestNumber;
use App\Domain\Command\CustomerAccount\UpdateAccountNumber;
use App\Domain\Command\CustomerAccount\UpdateReferralCode;
use App\Entity\ApplicationRequest;
use App\Entity\CustomerAccount;
use App\Entity\TariffRate;
use App\Enum\AccountCategory;
use App\Enum\AccountType;
use App\Enum\CustomerAccountStatus;
use App\WebService\Billing\Services\DataMapper;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\Serializer\SerializerInterface;

class ContractApplicationRequestCreator
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var DataMapper
     */
    private $dataMapper;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param CommandBus             $commandBus
     * @param DataMapper             $dataMapper
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     * @param string                 $tempDir
     * @param string                 $timezone
     */
    public function __construct(string $tempDir, string $timezone, CommandBus $commandBus, EntityManagerInterface $entityManager, SerializerInterface $serializer, DataMapper $dataMapper)
    {
        $this->commandBus = $commandBus;
        $this->dataMapper = $dataMapper;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->tempDir = $tempDir;
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
        $existingTariffRate = null;
        $tariffRate = null;
        $partnerCode = null;
        $agency = null;
        $salesRepName = null;
        $customer = null;
        $applicationRequestPersonDetails = null;
        $applicationRequestCorporationDetails = null;
        $contactPerson = null;
        $acquiredFrom = null;

        $applicationRequest = null;
        try {
            if (isset($data['applicationRequest'])) {
                $applicationRequestData = $data['applicationRequest'];

                if (isset($applicationRequestData['tariffRate']) && isset($applicationRequestData['tariffRate']['tariffRateNumber'])) {
                    $tariffRateNumber = $applicationRequestData['tariffRate']['tariffRateNumber'];
                    $existingTariffRate = $this->entityManager->getRepository(TariffRate::class)->findOneBy(['tariffRateNumber' => $tariffRateNumber, 'isBasedOn' => null]);

                    if (null !== $existingTariffRate) {
                        $tariffRate = clone $existingTariffRate;
                        $tariffRate->setIsBasedOn($existingTariffRate);
                        $this->entityManager->persist($tariffRate);
                    }
                }

                unset($applicationRequestData['tariffRate']);

                if (isset($applicationRequestData['acquirerCode'])) {
                    $acquiredFrom = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $applicationRequestData['acquirerCode']]);
                }

                if (isset($applicationRequestData['supplementaryFiles'])) {
                    $supplementaryFiles = $applicationRequestData['supplementaryFiles'];
                    foreach ($supplementaryFiles as $index => $supplementaryFile) {
                        $tempFileName = \uniqid();
                        $tempFilePath = $this->tempDir.'/'.$tempFileName;
                        $tempFile = new \SplFileObject('php://filter/convert.base64-decode/resource='.$tempFilePath, 'w');
                        $tempFile->fwrite($supplementaryFile['contentFile']);

                        $applicationRequestData['supplementaryFiles'][$index]['contentFile'] = $tempFilePath;
                    }
                }

                if (isset($applicationRequestData['customer'])) {
                    $identificationName = null;
                    $identificationValue = null;
                    $name = null;
                    $isIndividual = false;
                    if (isset($applicationRequestData['customer']['personDetails'])) {
                        $personDetailsData = $applicationRequestData['customer']['personDetails'];
                        $identificationName = $personDetailsData['identifiers'][0]['name'];
                        $identificationValue = $personDetailsData['identifiers'][0]['value'];
                        $name = $personDetailsData['name'];
                        $isIndividual = true;
                    } elseif (isset($applicationRequestData['customer']['corporationDetails'])) {
                        $corporationDetailsData = $applicationRequestData['customer']['corporationDetails'];
                        $identificationName = $corporationDetailsData['identifiers'][0]['name'];
                        $identificationValue = $corporationDetailsData['identifiers'][0]['value'];
                        $name = $corporationDetailsData['name'];
                    }

                    $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customer');
                    $expr = $qb->expr();

                    $existingCustomersQuery = null;
                    if ($isIndividual) {
                        $existingCustomersQuery = $qb->leftJoin('customer.personDetails', 'details')
                            ->leftJoin('details.identifiers', 'identifiers');
                    } else {
                        $existingCustomersQuery = $qb->leftJoin('customer.corporationDetails', 'details')
                            ->leftJoin('details.identifiers', 'identifiers');
                    }

                    $existingCustomersQuery->where($expr->eq('details.name', ':name'))
                        ->andWhere($expr->eq('identifiers.name', ':identityName'))
                        ->andWhere($expr->eq('identifiers.value', ':value'))
                        ->setParameter('name', $name)
                        ->setParameter('identityName', $identificationName)
                        ->setParameter('value', $identificationValue);

                    $existingCustomers = $existingCustomersQuery->getQuery()->getResult();

                    if (\count($existingCustomers) > 0) {
                        $customer = $existingCustomers[0];
                        if ($isIndividual) {
                            $applicationRequestPersonDetails = clone $customer->getPersonDetails();
                            $this->entityManager->persist($applicationRequestPersonDetails);
                        } else {
                            $applicationRequestCorporationDetails = clone $customer->getCorporationDetails();
                            $this->entityManager->persist($applicationRequestCorporationDetails);
                        }
                    } else {
                        $customerData = $applicationRequestData['customer'];
                        $customer = $this->serializer->deserialize(\json_encode($customerData), CustomerAccount::class, 'json', ['groups' => [
                            'customer_account_write',
                            'person_write',
                            'corporation_write',
                            'identification_write',
                            'contact_point_write',
                        ]]);

                        if ($customer instanceof CustomerAccount) {
                            $customer->setStatus(new CustomerAccountStatus(CustomerAccountStatus::INACTIVE));
                            $customer->addCategory(AccountCategory::CUSTOMER);
                            $this->entityManager->getConnection()->beginTransaction();
                            $this->entityManager->getConnection()->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;');
                            $this->commandBus->handle(new UpdateAccountNumber($customer));
                            $this->commandBus->handle(new UpdateReferralCode($customer));
                            $this->entityManager->persist($customer);
                            $this->entityManager->flush();
                            $this->entityManager->getConnection()->commit();

                            if ($isIndividual) {
                                if (null !== $customer->getPersonDetails()) {
                                    $applicationRequestPersonDetails = clone $customer->getPersonDetails();
                                    $this->entityManager->persist($applicationRequestPersonDetails);
                                }
                            } else {
                                if (null !== $customer->getCorporationDetails()) {
                                    $applicationRequestCorporationDetails = clone $customer->getCorporationDetails();
                                    $this->entityManager->persist($applicationRequestCorporationDetails);
                                }
                            }
                        } else {
                            throw new \Exception('Cannot create new customer.');
                        }
                    }

                    if (null !== $customer) {
                        if ($isIndividual) {
                            $contactPerson = $customer;
                        } else {
                            if (isset($applicationRequestData['contactPerson'])) {
                                $contactPersonData = $applicationRequestData['contactPerson'];

                                $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customerAccount');
                                $expr = $qb->expr();

                                $qb->leftJoin('customerAccount.personDetails', 'person')
                                    ->leftJoin('person.contactPoints', 'contactPoint')
                                    ->where($expr->andX(
                                        $expr->eq(\sprintf(<<<'SQL'
                        jsonb_contains(CAST(lower(CAST(%s.%s AS text)) AS jsonb), :%s)
SQL
                                            , 'contactPoint', 'emails', 'email'),
                                            $expr->literal(true)),
                                        $expr->eq('person.name', ':contactPersonName')))
                                    ->setParameter('email', \json_encode(\strtolower($contactPersonData['personDetails']['contactPoints']['0']['emails'][0])))
                                    ->setParameter('contactPersonName', $contactPersonData['personDetails']['name']);

                                $existingContactPersons = $qb->getQuery()->getResult();

                                if (\count($existingContactPersons) > 0) {
                                    $contactPerson = $existingContactPersons[0];
                                    $applicationRequestPersonDetails = clone $existingContactPersons[0]->getPersonDetails();
                                } else {
                                    $contactPerson = $this->serializer->deserialize(\json_encode($contactPersonData), CustomerAccount::class, 'json', ['groups' => [
                                        'customer_account_write',
                                        'person_write',
                                        'identification_write',
                                        'contact_point_write',
                                    ]]);

                                    if ($contactPerson instanceof CustomerAccount) {
                                        $contactPerson->addCategory(AccountCategory::CONTACT_PERSON);
                                        $contactPerson->setType(new AccountType(AccountType::INDIVIDUAL));
                                        $contactPerson->setStatus(new CustomerAccountStatus(CustomerAccountStatus::INACTIVE));
                                        $this->entityManager->getConnection()->beginTransaction();
                                        $this->entityManager->getConnection()->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;');
                                        $this->commandBus->handle(new UpdateAccountNumber($contactPerson));
                                        $this->commandBus->handle(new UpdateReferralCode($contactPerson));
                                        $this->entityManager->persist($contactPerson);
                                        $this->entityManager->flush();
                                        $this->entityManager->getConnection()->commit();

                                        if (null !== $contactPerson->getPersonDetails()) {
                                            $applicationRequestPersonDetails = clone $contactPerson->getPersonDetails();
                                            $this->entityManager->persist($applicationRequestPersonDetails);
                                        }
                                    } else {
                                        throw new \Exception('Cannot create contact person.');
                                    }
                                }
                            } else {
                                throw new \Exception('Contact person data is required.');
                            }
                        }
                    } else {
                        throw new \Exception('No customer found or created.');
                    }
                }

                unset($applicationRequestData['contactPerson']);
                unset($applicationRequestData['customer']);

                $applicationRequest = $this->serializer->deserialize(\json_encode($applicationRequestData), ApplicationRequest::class, 'json', ['groups' => [
                    'application_request_write',
                    'postal_address_write',
                    'digital_document_write',
                    'person_write',
                    'identification_write',
                    'contact_point_write',
                ]]);

                if ($applicationRequest instanceof ApplicationRequest) {
                    if (null !== $customer) {
                        $applicationRequest->setCustomer($customer);
                        $applicationRequest->setCustomerType($customer->getType());

                        if (null !== $applicationRequestCorporationDetails) {
                            $applicationRequest->setCorporationDetails($applicationRequestCorporationDetails);
                        }

                        $applicationRequest->setPersonDetails($applicationRequestPersonDetails);
                        $applicationRequest->setContactPerson($contactPerson);
                    } else {
                        throw new \Exception('No customer found or created.');
                    }

                    if (isset($applicationRequestData['preferredStartDate'])) {
                        $applicationRequest->setPreferredStartDate(new \DateTime($applicationRequestData['preferredStartDate'], $this->timezone));
                    }

                    if (isset($applicationRequestData['preferredEndDate'])) {
                        $applicationRequest->setPreferredEndDate(new \DateTime($applicationRequestData['preferredEndDate'], $this->timezone));
                    }

                    if (null !== $acquiredFrom) {
                        $applicationRequest->setAcquiredFrom($acquiredFrom);
                    }

                    foreach ($applicationRequest->getAddresses() as $postalAddress) {
                        if (null === $postalAddress->getAddressCountry()) {
                            $countryCode = $this->timezone->getLocation()['country_code'];

                            $postalAddress->setAddressCountry($countryCode);

                            if (null === $postalAddress->getAddressLocality() && 'SG' === $countryCode) {
                                $postalAddress->setAddressLocality('SINGAPORE');
                            }
                        }
                    }

                    $applicationRequest->setContractSubtype($this->dataMapper->mapContractSubtype($applicationRequest->getContractSubtype()));
                    $this->entityManager->persist($applicationRequest);

                    if (null !== $tariffRate) {
                        $applicationRequest->setTariffRate($tariffRate);
                    }

                    $this->entityManager->getConnection()->beginTransaction();
                    $this->entityManager->getConnection()->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;');
                    $this->commandBus->handle(new UpdateApplicationRequestNumber($applicationRequest));
                    $this->entityManager->persist($applicationRequest);
                    $this->entityManager->flush();
                    $this->entityManager->getConnection()->commit();

                    return [
                        'FRCContractApplicationNumber' => $applicationRequest->getExternalApplicationRequestNumber(),
                        'CRMContractApplicationNumber' => $applicationRequest->getApplicationRequestNumber(),
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
                'FRCContractApplicationNumber' => 'No data.' === $ex->getMessage() ? '' : $data['applicationRequest']['externalApplicationRequestNumber'],
                'CRMContractApplicationNumber' => '',
                'ProcessStatus' => 0,
                'Message' => $ex->getMessage(),
            ];
        }
    }
}
