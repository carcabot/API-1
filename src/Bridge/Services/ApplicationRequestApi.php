<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Document\OldCustomerAccount;
use App\Entity\ApplicationRequest;
use App\Entity\CustomerAccount;
use App\Entity\DigitalDocument;
use App\Entity\Identification;
use App\Entity\MonetaryAmount;
use App\Entity\Person;
use App\Entity\PostalAddress;
use App\Entity\QuantitativeValue;
use App\Entity\TariffRate;
use App\Entity\User;
use App\Enum\AccountType;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use App\Enum\BillSubscriptionType;
use App\Enum\ContractType;
use App\Enum\DwellingType;
use App\Enum\GenderType;
use App\Enum\IdentificationName;
use App\Enum\Industry;
use App\Enum\MaritalStatus;
use App\Enum\MeterType;
use App\Enum\PostalAddressType;
use App\Enum\ReferralSource;
use App\Enum\RefundType;
use App\Enum\Source;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use GuzzleHttp\Client as GuzzleClient;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;

final class ApplicationRequestApi
{
    /**
     * @var string
     */
    private $bridgeApiUrl;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(string $bridgeApiUrl, DocumentManager $documentManager, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->client = new GuzzleClient();
        $this->baseUri = HttpUri::createFromString($this->bridgeApiUrl);
        $this->logger = $logger;
    }

    public function createApplicationRequest(array $applicationRequests)
    {
        $batchSize = 1000;
        foreach ($applicationRequests as $key => $applicationRequestData) {
            /**
             * @var ApplicationRequest|null
             */
            $existingApplicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $applicationRequestData->getApplicationRequestNumber()]);

            if (null !== $existingApplicationRequest) {
                $applicationRequest = $existingApplicationRequest;
            } else {
                $applicationRequest = new ApplicationRequest();
            }
            if (null !== $applicationRequestData->getApplicationRequestNumber()) {
                $applicationRequest->setApplicationRequestNumber($applicationRequestData->getApplicationRequestNumber());
                if (!empty($applicationRequestData->getSubType())) {
                    $applicationRequest->setContractSubtype($this->mapContractSubType($applicationRequestData->getSubType()));
                }
                if (!empty($applicationRequestData->getType())) {
                    $applicationRequest->setContractType($this->mapContractType($applicationRequestData->getType()));
                }
                if (null !== $applicationRequestData->getContractCustomize()) {
                    $applicationRequest->setCustomized($applicationRequestData->getContractCustomize());
                }
                if (!empty($applicationRequestData->getDeposit())) {
                    $applicationRequest->setDepositRefundType($this->mapRefundType($applicationRequestData->getDeposit()));
                }
                if (null !== $applicationRequestData->getEbsAccountNo()) {
                    $applicationRequest->setEbsAccountNumber($applicationRequestData->getEbsAccountNo());
                }
                if (null !== $applicationRequestData->getMsslAccountNo()) {
                    $applicationRequest->setMsslAccountNumber($applicationRequestData->getMsslAccountNo());
                }
                if (null !== $applicationRequestData->getGiroOption()) {
                    $applicationRequest->setGIROOption($applicationRequestData->getGiroOption());
                }
                if (null !== $applicationRequestData->getMeterOption()) {
                    $applicationRequest->setMeterType($this->mapMeterType($applicationRequestData->getMeterOption()));
                }
                if (null !== $applicationRequestData->getPreferTurnOffDate()) {
                    $applicationRequest->setPreferredEndDate($applicationRequestData->getPreferTurnOffDate());
                }
                if (null !== $applicationRequestData->getPreferTurnOnDate()) {
                    $applicationRequest->setPreferredStartDate($applicationRequestData->getPreferTurnOnDate());
                }
                if (null !== $applicationRequestData->getRefSource()) {
                    $applicationRequest->setReferralSource($this->mapReferralSource($applicationRequestData->getRefSource()));
                }
                if (null !== $applicationRequestData->getReferralCode()) {
                    $applicationRequest->setReferralCode($applicationRequestData->getReferralCode());
                }
                if (null !== $applicationRequestData->getRemark()) {
                    $applicationRequest->setRemark($applicationRequestData->getRemark());
                }
                if ('CONTRACT_APP' === $applicationRequestData->getAppType() && 'RESIDENTIAL' === $applicationRequestData->getType() && null !== $applicationRequestData->getIsSpAccountHolder()) {
                    $applicationRequest->setSelfApplication($applicationRequestData->getIsSpAccountHolder());
                } else {
                    $applicationRequest->setSelfApplication(null);
                }
                if (null !== $applicationRequestData->getSelfReadOption()) {
                    $applicationRequest->setSelfReadMeterOption($applicationRequestData->getSelfReadOption());
                }
                if (null !== $applicationRequestData->getStatus()) {
                    $applicationRequest->setStatus($this->mapStatus($applicationRequestData->getStatus()));
                }
                if (null !== $applicationRequestData->getAppType()) {
                    $applicationRequest->setType($this->mapType($applicationRequestData->getAppType()));
                }
                if (null !== $applicationRequestData->getRenewalStartDate() && ApplicationRequestType::CONTRACT_RENEWAL === $applicationRequest->getType()->getValue()) {
                    $applicationRequest->setPreferredStartDate($applicationRequestData->getRenewalStartDate());
                }
                if (null !== $applicationRequestData->getIsBillEdonPaper()) {
                    $type = true === $applicationRequestData->getIsBillEdonPaper() ? BillSubscriptionType::HARDCOPY : BillSubscriptionType::ELECTRONIC;
                    $applicationRequest->addBillSubscriptionType($type);
                }
                if (null !== $applicationRequestData->getLocationCode()) {
                    $applicationRequest->setLocation($applicationRequestData->getLocationCode());
                }

                if (null !== $applicationRequestData->getTerminationReason() && !empty(\trim($applicationRequestData->getTerminationReason()))) {
                    $applicationRequest->setRemark($applicationRequestData->getTerminationReason());
                }

                if (null !== ($partnerData = $applicationRequestData->getPartner())) {
                    $applicationRequest->setAcquirerCode($partnerData->getAcquirerCode());
                    $applicationRequest->setAcquirerName($partnerData->getAcquirerName());
                    $applicationRequest->setSalesRepName($partnerData->getSalesRepName());
                }

                if (null !== $applicationRequestData->getDepositAmount()) {
                    $currency = null;
                    if (null !== $applicationRequestData->getDepositCurrency()) {
                        $currency = $applicationRequestData->getDepositCurrency();
                    }
                    $applicationRequest->setDepositAmount(new MonetaryAmount($applicationRequestData->getDepositAmount(), $currency));
                }

                if (null !== $applicationRequestData->getSource()) {
                    $applicationRequest->setSource($this->mapSource($applicationRequestData->getSource()));
                }

                $applicationRequest->setBridgeId($applicationRequestData->getId());
                $applicationRequest->setDateSubmitted($applicationRequestData->getCreatedAt());

                $this->entityManager->persist($applicationRequest);

                if (0 === ($key % $batchSize)) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            } else {
                continue;
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function updateApplicationRequestAddresses($applicationRequests)
    {
        $batchSize = 1000;
        foreach ($applicationRequests as $key => $applicationRequestData) {
            /**
             * @var ApplicationRequest|null
             */
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $applicationRequestData->getApplicationRequestNumber()]);
            if (null !== $applicationRequest) {
                foreach ($applicationRequestData->getAddresses() as $addressData) {
                    /**
                     * @var PostalAddress
                     */
                    $address = new PostalAddress();

                    if (!empty($addressData['country'])) {
                        if (2 === \strlen($addressData['country'])) {
                            $address->setAddressCountry((string) $addressData['country']);
                        } elseif (false !== \stripos($addressData['country'], 'singapore')) {
                            $address->setAddressCountry('SG');
                        }
                    }

                    if (!empty($addressData['region'])) {
                        $address->setAddressRegion((string) $addressData['region']);
                    }

                    if (!empty($addressData['city'])) {
                        $address->setAddressLocality((string) $addressData['city']);
                    }

                    if (!empty($addressData['building_name'])) {
                        $address->setBuildingName((string) $addressData['building_name']);
                    }

                    if (!empty($addressData['floor'])) {
                        $address->setFloor((string) $addressData['floor']);
                    }

                    if (!empty($addressData['house_no'])) {
                        $address->setHouseNumber((string) $addressData['house_no']);
                    }

                    if (!empty($addressData['postal_code'])) {
                        $address->setPostalCode((string) $addressData['postal_code']);
                    }

                    if (!empty($addressData['street'])) {
                        $address->setStreetAddress((string) $addressData['street']);
                    }

                    if (!empty($addressData['address_type'])) {
                        $address->setType($this->mapAddressType($addressData['address_type']));
                    }

                    if (!empty($addressData['unit_no'])) {
                        $address->setUnitNumber((string) $addressData['unit_no']);
                    }
                    $applicationRequest->addAddress($address);
                }
            }

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function updateApplicationRequestAverageConsumptions($applicationRequests)
    {
        $batchSize = 1000;
        foreach ($applicationRequests as $key => $applicationRequestData) {
            /**
             * @var ApplicationRequest|null
             */
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $applicationRequestData->getApplicationRequestNumber()]);
            if (null !== $applicationRequest) {
                $unitCode = null;
                $value = null;
                if (\iter\count($applicationRequestData->getAverageConsumption()) > 0) {
                    $unitCode = $this->mapUnitCodes($applicationRequestData->getAverageConsumption()[0]['key']);
                }
                if (null !== $applicationRequestData->getConsumptionAmount()) {
                    $value = (string) $applicationRequestData->getConsumptionAmount();
                }
                $applicationRequest->setAverageConsumption(new QuantitativeValue($value, null, null, $unitCode));
            }

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function updateApplicationRequestBlamable($applicationRequests)
    {
        $batchSize = 1000;
        foreach ($applicationRequests as $key => $applicationRequestData) {
            /**
             * @var ApplicationRequest|null
             */
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $applicationRequestData->getApplicationRequestNumber()]);
            $expr = $this->entityManager->getExpressionBuilder();
            $userRepo = $this->entityManager->getRepository(User::class)->createQueryBuilder('user')->select('user')
                ->join('user.bridgeUser', 'bridgeUser');
            if (null !== $applicationRequest) {
                if (\iter\count($applicationRequestData->getCreatedBy()) > 0) {
                    /**
                     * @var User|null
                     */
                    $createdBy = $userRepo
                        ->where($expr->eq('bridgeUser.bridgeUserId', ':id'))
                        ->setParameters([
                            'id' => $applicationRequestData->getCreatedBy()[0]['_id'],
                        ])
                        ->getQuery()
                        ->getOneOrNullResult(Query::HYDRATE_OBJECT);
                    $applicationRequest->setCreator($createdBy);
                    $applicationRequest->setSubmitter($createdBy);
                }
                if (\iter\count($applicationRequestData->getUpdatedBy()) > 0) {
                    /**
                     * @var User|null
                     */
                    $updatedBy = $userRepo
                        ->where($expr->eq('bridgeUser.bridgeUserId', ':id'))
                        ->setParameters([
                            'id' => $applicationRequestData->getUpdatedBy()[0]['_id'],
                        ])
                        ->getQuery()
                        ->getOneOrNullResult(Query::HYDRATE_OBJECT);
                    $applicationRequest->setAgent($updatedBy);
                }
                if (null !== $applicationRequestData->getCreatedAt()) {
                    $applicationRequest->setDateCreated($applicationRequestData->getCreatedAt());
                }
                if (null !== $applicationRequestData->getUpdatedAt()) {
                    $applicationRequest->setDateModified($applicationRequestData->getUpdatedAt());
                }
            }

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function updateApplicationRequestContactPerson($applicationRequests)
    {
        $batchSize = 1000;
        foreach ($applicationRequests as $key => $applicationRequestData) {
            /**
             * @var ApplicationRequest|null
             */
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $applicationRequestData->getApplicationRequestNumber()]);

            if (null !== $applicationRequest) {
                $customer = null;

                if (\iter\count($applicationRequestData->getContactId()) > 0) {
                    /**
                     * @var CustomerAccount|null
                     */
                    $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $applicationRequestData->getContactId()[0]['_customerId']]);
                    if (null !== $customer && ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequest->getType()->getValue()) {
                        $applicationRequest->setContactPerson($customer);

                        if (null !== $customer->getPersonDetails()) {
                            $person = clone $customer->getPersonDetails();
                            $applicationRequest->setPersonDetails($person);
                            $this->entityManager->persist($person);
                        }
                    }
                }
            }
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function updateApplicationRequestCustomerAccount($applicationRequests)
    {
        $batchSize = 1000;
        foreach ($applicationRequests as $key => $applicationRequestData) {
            /**
             * @var ApplicationRequest|null
             */
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $applicationRequestData->getApplicationRequestNumber()]);

            if (null !== $applicationRequest) {
                $customer = null;
                if (\iter\count($applicationRequestData->getCustomerId()) > 0) {
                    /**
                     * @var CustomerAccount|null
                     */
                    $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $applicationRequestData->getCustomerId()[0]['_customerId']]);
                    if (null !== $customer) {
                        $applicationRequest->setCustomer($customer);
                        $customer->addApplicationRequest($applicationRequest);
                        if (AccountType::CORPORATE === $customer->getType()->getValue()) {
                            $applicationRequest->setCustomerType(new AccountType(AccountType::CORPORATE));
                            if (null === $applicationRequest->getCorporationDetails()) {
                                if (null !== $customer->getCorporationDetails()) {
                                    $corporation = clone $customer->getCorporationDetails();

                                    $this->entityManager->persist($corporation);
                                    $applicationRequest->setCorporationDetails($corporation);
                                }
                            }
                        } elseif (AccountType::INDIVIDUAL === $customer->getType()->getValue()) {
                            $applicationRequest->setCustomerType(new AccountType(AccountType::INDIVIDUAL));
                            if (null === $applicationRequest->getPersonDetails()) {
                                if (null !== $customer->getPersonDetails()) {
                                    $person = clone $customer->getPersonDetails();

                                    $this->entityManager->persist($person);
                                    $applicationRequest->setPersonDetails($person);
                                }
                            }
                        }
                        $this->entityManager->persist($customer);
                    }
                }
            }

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function updateApplicationRequestDigitalDocument($applicationRequests)
    {
        $batchSize = 1000;
        foreach ($applicationRequests as $key => $applicationRequestData) {
            /**
             * @var ApplicationRequest|null
             */
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $applicationRequestData->getApplicationRequestNumber()]);

            if (null !== $applicationRequest && 0 === \count($applicationRequest->getSupplementaryFiles())) {
                if (\count($applicationRequestData->getAttachments()) > 0) {
                    foreach ($applicationRequestData->getAttachments() as $attachment) {
                        /**
                         * @var DigitalDocument
                         */
                        $digitalDocument = new DigitalDocument();
                        if (!empty($attachment['attached'])) {
                            $appendUri = \sprintf('%s/%s', 'quotation', $attachment['attached']);
                            $modifier = new AppendSegment($appendUri);
                            $attachmentUri = $modifier->process($this->baseUri);

                            $digitalDocument->setUrl((string) $attachmentUri);
                        }
                        if (!empty($attachment['desc'])) {
                            $digitalDocument->setName($attachment['desc']);
                        } else {
                            $digitalDocument->setName($attachment['attached']);
                        }

                        $applicationRequest->addSupplementaryFile($digitalDocument);
                    }
                }
            }

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function updateApplicationRequestRefundee($applicationRequests)
    {
        $batchSize = 1000;
        foreach ($applicationRequests as $key => $applicationRequestData) {
            if (null !== $applicationRequestData->getRefundPayeeName() && null !== $applicationRequestData->getRefundPayeeNric()) {
                $isExisting = false;
                /**
                 * @var ApplicationRequest|null
                 */
                $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $applicationRequestData->getApplicationRequestNumber()]);

                if (null !== $applicationRequest) {
                    $customerDocument = $this->documentManager->getRepository(OldCustomerAccount::class)->findOneBy(['id' => $applicationRequestData->getCustomerId()]);

                    if (null !== $customerDocument) {
                        $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerDocument->getCustomerId()]);

                        if (null !== $customer) {
                            $personDetails = $customer->getPersonDetails();

                            if (null !== $personDetails) {
                                if (\count($personDetails->getIdentifiers()) > 0) {
                                    foreach ($personDetails->getIdentifiers() as $identifier) {
                                        if ($applicationRequestData->getRefundPayeeName() === $personDetails->getName() && $applicationRequestData->getRefundPayeeNric() === $identifier->getValue()) {
                                            $isExisting = true;
                                            $person = clone $personDetails;
                                            $applicationRequest->setRefundee($customer);
                                            $applicationRequest->setRefundeeDetails($person);
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!$isExisting) {
                        /**
                         * @var Person
                         */
                        $person = new Person();
                        /**
                         * @var Identification
                         */
                        $identifier = new Identification();

                        if (null !== $applicationRequestData->getRefundPayeeName()) {
                            $person->setName($applicationRequestData->getRefundPayeeName());
                        }
                        if (null !== $applicationRequestData->getRefundPayeeNric()) {
                            $identifier->setName($this->mapIdentificationName('NATIONAL_REGISTRATION_IDENTITY_CARD'));
                            $identifier->setValue($applicationRequestData->getRefundPayeeNric());
                            $person->addIdentifier($identifier);
                        }

                        $applicationRequest->setRefundeeDetails(clone $person);
                    }
                }
            }

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function updateApplicationRequestTariffRate($applicationRequests)
    {
        $batchSize = 1000;
        foreach ($applicationRequests as $key => $applicationRequestData) {
            /**
             * @var ApplicationRequest|null
             */
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $applicationRequestData->getApplicationRequestNumber()]);

            if (null !== $applicationRequest) {
                /**
                 * @var TariffRate|null
                 */
                $tariffRate = $this->entityManager->getRepository(TariffRate::class)->findOneBy([
                    'tariffRateNumber' => $applicationRequestData->getPromotionCodeId()[0]['promotion_code'],
                    'isBasedOn' => null,
                ]);

                if (null !== $tariffRate) {
                    $clonedTariffRate = clone $tariffRate;
                    $clonedTariffRate->setIsBasedOn($tariffRate);
                    $applicationRequest->setTariffRate($clonedTariffRate);

                    $this->entityManager->persist($clonedTariffRate);
                    $this->entityManager->persist($applicationRequest);
                }
            }
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function mapAddressType(string $address)
    {
        $typesMap = [
            'CORRES_ADDRESS' => new PostalAddressType(PostalAddressType::CORRESPONDENCE_ADDRESS),
            'BILL_ADDRESS' => new PostalAddressType(PostalAddressType::MAILING_ADDRESS),
            'PREMISE_ADDRESS' => new PostalAddressType(PostalAddressType::PREMISE_ADDRESS),
            'REFUND_ADDRESS' => new PostalAddressType(PostalAddressType::REFUND_ADDRESS),
            'SECONDARY_BILL_ADDRESS' => new PostalAddressType(PostalAddressType::SECONDARY_BILL_ADDRESS),
        ];

        return $typesMap[$address];
    }

    private function mapContractSubType(string $type)
    {
        $types = [
            'CHARITABLE_ORGANISATIONS' => Industry::CHARITABLE_ORGANISATIONS,
            'CONSTRUCTION' => Industry::CONSTRUCTION,
            'DORMITORIES' => Industry::DORMITORIES,
            'EDUCATIONAL_INSTITUTIONS' => Industry::EDUCATIONAL_INSTITUTIONS,
            'ELECTRONICS_SEMICONDUCTORS' => Industry::ELECTRONICS_SEMICONDUCTORS,
            'F_B_OUTLETS' => Industry::F_B_OUTLETS,
            'HOTELS' => Industry::HOTELS,
            'LOGISTICS' => Industry::LOGISTICS,
            'MCST_CONDOS' => Industry::MCST_CONDOS,
            'OFFICE_REAL_ESTATE' => Industry::OFFICE_REAL_ESTATE,
            'OTHER_HEAVY_MANUFACTURING' => Industry::OTHER_HEAVY_MANUFACTURING,
            'OTHER_LIGHT_MANUFACTURING' => Industry::OTHER_LIGHT_MANUFACTURING,
            'OTHERS' => Industry::OTHERS,
            'PHARMACEUTICALS' => Industry::PHARMACEUTICALS,
            'PORTS' => Industry::PORTS,
            'PRECISION_INDUSTRIES' => Industry::PRECISION_INDUSTRIES,
            'REFINERIES_PETROCHEMICALS' => Industry::REFINERIES_PETROCHEMICALS,
            'RETAIL_OUTLETS' => Industry::RETAIL_OUTLETS,
            'SHOPPING_MALLS' => Industry::SHOPPING_MALLS,
            'TRANSPORTATION' => Industry::TRANSPORTATION,
            'ROOM1' => DwellingType::ONE_ROOM_FLAT_HDB,
            'ROOM2' => DwellingType::TWO_ROOM_FLAT_HDB,
            'ROOM3' => DwellingType::THREE_ROOM_FLAT_HDB,
            'ROOM4' => DwellingType::FOUR_ROOM_FLAT_HDB,
            'ROOM5' => DwellingType::FIVE_ROOM_FLAT_HDB,
            'CONDO' => DwellingType::CONDOMINIUM,
            'EXECUTIVE_FLAT_HDB' => DwellingType::EXECUTIVE_FLAT_HDB,
            'LANDED' => DwellingType::LANDED,
        ];

        return $types[$type];
    }

    private function mapContractType(string $type)
    {
        $types = [
            'COMMERCIAL' => new ContractType(ContractType::COMMERCIAL),
            'RESIDENTIAL' => new ContractType(ContractType::RESIDENTIAL),
        ];

        return $types[$type];
    }

    private function mapCorporateIndustry(string $industry)
    {
        $typesMap = [
            'CHARITABLE_ORGANISATIONS' => new Industry(Industry::CHARITABLE_ORGANISATIONS),
            'CONSTRUCTION' => new Industry(Industry::CONSTRUCTION),
            'DORMITORIES' => new Industry(Industry::DORMITORIES),
            'EDUCATIONAL_INSTITUTIONS' => new Industry(Industry::EDUCATIONAL_INSTITUTIONS),
            'ELECTRONICS_SEMICONDUCTORS' => new Industry(Industry::ELECTRONICS_SEMICONDUCTORS),
            'F_B_OUTLETS' => new Industry(Industry::F_B_OUTLETS),
            'HOTELS' => new Industry(Industry::HOTELS),
            'LOGISTICS' => new Industry(Industry::LOGISTICS),
            'MCST_CONDOS' => new Industry(Industry::MCST_CONDOS),
            'OFFICE_REAL_ESTATE' => new Industry(Industry::OFFICE_REAL_ESTATE),
            'OTHER_HEAVY_MANUFACTURING' => new Industry(Industry::OTHER_HEAVY_MANUFACTURING),
            'OTHER_LIGHT_MANUFACTURING' => new Industry(Industry::OTHER_LIGHT_MANUFACTURING),
            'OTHERS' => new Industry(Industry::OTHERS),
            'PHARMACEUTICALS' => new Industry(Industry::PHARMACEUTICALS),
            'PORTS' => new Industry(Industry::PORTS),
            'PRECISION_INDUSTRIES' => new Industry(Industry::PRECISION_INDUSTRIES),
            'REFINERIES_PETROCHEMICALS' => new Industry(Industry::REFINERIES_PETROCHEMICALS),
            'RETAIL_OUTLETS' => new Industry(Industry::RETAIL_OUTLETS),
            'SHOPPING_MALLS' => new Industry(Industry::SHOPPING_MALLS),
            'TRANSPORTATION' => new Industry(Industry::TRANSPORTATION),
        ];

        return $typesMap[$industry];
    }

    private function mapGender(string $gender)
    {
        $typesMap = [
            'MALE' => new GenderType(GenderType::MALE),
            'FEMALE' => new GenderType(GenderType::FEMALE),
        ];

        return $typesMap[$gender];
    }

    private function mapIdentificationName(string $identity)
    {
        $typesMap = [
            'COMPANY_REGISTRATION_NUMBER' => new IdentificationName(IdentificationName::COMPANY_REGISTRATION_NUMBER),
            'MALAYSIA_IDENTITY_CARD' => new IdentificationName(IdentificationName::MALAYSIA_IDENTITY_CARD),
            'NATIONAL_REGISTRATION_IDENTITY_CARD' => new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD),
            'UNIQUE_ENTITY_NUMBER' => new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER),
        ];

        return $typesMap[$identity];
    }

    private function mapMaritalStatus(string $marital)
    {
        $typesMap = [
            'DIVORCED' => new MaritalStatus(MaritalStatus::DIVORCED),
            'MARRIED' => new MaritalStatus(MaritalStatus::MARRIED),
            'NOT_REPORTED' => new MaritalStatus(MaritalStatus::NOT_REPORTED),
            'SEPARATED' => new MaritalStatus(MaritalStatus::SEPARATED),
            'SINGLE' => new MaritalStatus(MaritalStatus::SINGLE),
            'WIDOWED' => new MaritalStatus(MaritalStatus::WIDOWED),
        ];

        return $typesMap[$marital];
    }

    private function mapMeterType(string $type)
    {
        $types = [
            'AMI' => new MeterType(MeterType::AMI),
            'SRLP' => new MeterType(MeterType::SRLP),
        ];

        return $types[$type];
    }

    private function mapReferralSource(string $source)
    {
        $sources = [
            'ONLINE_ADVERTISING' => new ReferralSource(ReferralSource::ONLINE_ADVERTISEMENT),
            'SOCIAL_MEDIA' => new ReferralSource(ReferralSource::SOCIAL_MEDIA),
            'ANOTHER_WEBSITE' => new ReferralSource(ReferralSource::ANOTHER_WEBSITE),
            'EMAIL_NEWSLETTER' => new ReferralSource(ReferralSource::EMAIL_NEWSLETTER),
            'REFERRAL' => new ReferralSource(ReferralSource::REFERRAL),
            'EVENT_ROADSHOW' => new ReferralSource(ReferralSource::EVENT_ROADSHOW),
            'NEWSPAPER_MAGAZINE' => new ReferralSource(ReferralSource::NEWSPAPER_MAGAZINE),
            'POSTCARD_LETTER' => new ReferralSource(ReferralSource::POSTCARD_LETTER),
            'RADIO_TV' => new ReferralSource(ReferralSource::RADIO_TV),
            'MRT_BUS' => new ReferralSource(ReferralSource::MRT_BUS),
            'OTHERS' => new ReferralSource(ReferralSource::OTHERS),
        ];

        return $sources[$source];
    }

    private function mapRefundType(string $type)
    {
        $types = [
            'OFFSET' => new RefundType(RefundType::BILL_OFFSET),
            'REFUND' => new RefundType(RefundType::FULL_REFUND),
        ];

        return $types[$type];
    }

    private function mapSource(string $status)
    {
        $sources = [
            'HOMEPAGE' => Source::CLIENT_HOMEPAGE,
            'UCRM' => Source::MANUAL_ENTRY,
            'PARTNER' => Source::PARTNERSHIP_PORTAL,
            'SSP' => Source::SELF_SERVICE_PORTAL,
            'MIGRATED' => Source::MIGRATED,
            'DBSPDDA' => Source::DBSPDDA,
            'SIM' => Source::BILLING_PORTAL,
            'QUOTATION' => Source::QUOTATION,
            'RCCS' => Source::RCCS,
            'DBSRCCS' => Source::DBSRCCS,
        ];

        return $sources[$status];
    }

    private function mapStatus(string $status)
    {
        $statuses = [
            'CANCELLED' => new ApplicationRequestStatus(ApplicationRequestStatus::CANCELLED),
            'COMPLETED' => new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED),
            'DRAFT' => new ApplicationRequestStatus(ApplicationRequestStatus::DRAFT),
            'AUTHORIZATION_URL_EXPIRED' => new ApplicationRequestStatus(ApplicationRequestStatus::AUTHORIZATION_URL_EXPIRED),
            'IN_PROGRESS' => new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS),
            'PARTNER_DRAFT' => new ApplicationRequestStatus(ApplicationRequestStatus::PARTNER_DRAFT),
            'PENDING' => new ApplicationRequestStatus(ApplicationRequestStatus::PENDING),
            'PENDING_BILLING_STATUS' => new ApplicationRequestStatus(ApplicationRequestStatus::PENDING_BILLING_STATUS),
            'REJECTED' => new ApplicationRequestStatus(ApplicationRequestStatus::REJECTED),
            'REJECTED_BY_OWNER' => new ApplicationRequestStatus(ApplicationRequestStatus::REJECTED_BY_OWNER),
            'VOIDED' => new ApplicationRequestStatus(ApplicationRequestStatus::VOIDED),
        ];

        return $statuses[$status];
    }

    private function mapType(string $type)
    {
        $types = [
            'ACCOUNT_CLOSURE' => new ApplicationRequestType(ApplicationRequestType::ACCOUNT_CLOSURE),
            'CONTRACT_APP' => new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION),
            'CONTRACT_RENEWAL' => new ApplicationRequestType(ApplicationRequestType::CONTRACT_RENEWAL),
            'GIRO_TERMINATION' => new ApplicationRequestType(ApplicationRequestType::GIRO_TERMINATION),
            'TRANSFER_OUT' => new ApplicationRequestType(ApplicationRequestType::TRANSFER_OUT),
        ];

        return $types[$type];
    }

    private function mapUnitCodes(?string $unitCode)
    {
        switch ($unitCode) {
            case 'kWh':
                return 'KWH';
            case 'MWh':
                return 'MWH';
            default:
                return null;
        }
    }
}
