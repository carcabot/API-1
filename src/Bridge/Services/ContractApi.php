<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Disque\JobType;
use App\Document\Contract as OldContract;
use App\Document\OldCustomerAccount;
use App\Entity\ApplicationRequest;
use App\Entity\BridgeUser;
use App\Entity\Contract;
use App\Entity\ContractPostalAddress;
use App\Entity\CreateContractAction;
use App\Entity\CustomerAccount;
use App\Entity\DigitalDocument;
use App\Entity\Identification;
use App\Entity\MonetaryAmount;
use App\Entity\Person;
use App\Entity\PostalAddress;
use App\Entity\QuantitativeValue;
use App\Entity\TariffRate;
use App\Entity\UpdateContractAction;
use App\Entity\User;
use App\Enum\AccountCategory;
use App\Enum\AccountType;
use App\Enum\ActionStatus;
use App\Enum\ApplicationRequestType;
use App\Enum\BillSubscriptionType;
use App\Enum\ContractActionType;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Enum\CustomerAccountStatus;
use App\Enum\DwellingType;
use App\Enum\GenderType;
use App\Enum\IdentificationName;
use App\Enum\Industry;
use App\Enum\MaritalStatus;
use App\Enum\MeterType;
use App\Enum\PostalAddressType;
use App\Enum\ReferralSource;
use App\Enum\RefundType;
use App\Enum\TimeType;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Documents\Customer;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Components\Query as UriQuery;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Modifiers\MergeQuery;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ContractApi
{
    /**
     * @var string
     */
    private $bridgeApiUrl;

    /**
     * @var BridgeDataMapper
     */
    private $bridgeDataMapper;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DisqueQueue
     */
    private $webServicesQueue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @param string                 $bridgeApiUrl
     * @param BridgeDataMapper       $bridgeDataMapper
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param DisqueQueue            $webServicesQueue
     * @param LoggerInterface        $logger
     */
    public function __construct(string $bridgeApiUrl, BridgeDataMapper $bridgeDataMapper, DocumentManager $documentManager, EntityManagerInterface $entityManager, DisqueQueue $webServicesQueue, LoggerInterface $logger)
    {
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->bridgeDataMapper = $bridgeDataMapper;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->webServicesQueue = $webServicesQueue;
        $this->logger = $logger;
        $this->client = new GuzzleClient();
        $this->baseUri = HttpUri::createFromString($this->bridgeApiUrl);
    }

    /**
     * Creates an application request in the old version.
     *
     * @param ApplicationRequest $applicationRequest
     * @param BridgeUser         $creator
     *
     * @return string|null
     */
    public function createApplicationRequest(ApplicationRequest $applicationRequest, BridgeUser $creator)
    {
        $modifier = new AppendSegment('bridge/contracts');
        $uri = $modifier->process($this->baseUri);
        $applicationRequestId = $applicationRequest->getApplicationRequestNumber();
        $applicationRequest->setTemporaryNumber($applicationRequestId);

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'x-access-token' => $creator->getAuthToken(),
        ];

        $this->validateApplicationRequest($applicationRequest, $creator);
        // map application request data to old version
        $applicationRequestData = $this->getApplicationRequestData($applicationRequest, $creator);

        try {
            $this->logger->info('Sending POST to '.$uri);
            $this->logger->info(\json_encode($applicationRequestData, JSON_PRETTY_PRINT));

            $createApplicationRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($applicationRequestData));
            $createApplicationResponse = $this->client->send($createApplicationRequest, ['timeout' => 10]);
            $createApplicationResult = \json_decode((string) $createApplicationResponse->getBody(), true);

            $this->logger->info('Result from POST to '.$uri);
            $this->logger->info(\json_encode($createApplicationResult, JSON_PRETTY_PRINT));

            if (200 === $createApplicationResult['status'] && 1 === $createApplicationResult['flag']) {
                $applicationRequestId = $createApplicationResult['data']['_id'];
                $applicationRequest->setBridgeId($applicationRequestId);
                $applicationRequest->setApplicationRequestNumber($createApplicationResult['data']['_applicationId']);
            } else {
                throw new ClientException(ErrorResolver::getErrorMessage($createApplicationResult), $createApplicationRequest);
            }
        } catch (\Exception $e) {
            if ($e instanceof ClientException) {
                throw $e;
            }
        }

        $this->queueBridgeFixer($applicationRequest);

        return $applicationRequestId;
    }

    /**
     * Updates an application request in the old version.
     *
     * @param ApplicationRequest $applicationRequest
     * @param BridgeUser         $agent
     */
    public function updateApplicationRequest(ApplicationRequest $applicationRequest, BridgeUser $agent)
    {
        $modifier = new AppendSegment('bridge/contracts/'.$applicationRequest->getBridgeId());
        $uri = $modifier->process($this->baseUri);

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'x-access-token' => $agent->getAuthToken(),
        ];

        $this->validateApplicationRequest($applicationRequest, $agent);
        // map application request data to old version
        $applicationRequestData = $this->getApplicationRequestData($applicationRequest);

        $this->logger->info('Sending PUT to '.$uri);
        $this->logger->info(\json_encode($applicationRequestData, JSON_PRETTY_PRINT));

        $updateApplicationRequest = new GuzzlePsr7Request('PUT', $uri, $headers, \json_encode($applicationRequestData));
        $updateApplicationResponse = $this->client->send($updateApplicationRequest);
        $updateApplicationResult = \json_decode((string) $updateApplicationResponse->getBody(), true);

        $this->logger->info('Result from PUT to '.$uri);
        $this->logger->info(\json_encode($updateApplicationResult, JSON_PRETTY_PRINT));

        if (!(200 === $updateApplicationResult['status'] && 1 === $updateApplicationResult['flag'])) {
            throw new BadRequestHttpException(ErrorResolver::getErrorMessage($updateApplicationResult));
        }
    }

    public function createContractActions($contracts)
    {
        foreach ($contracts as $contract) {
            $actions = [];
            $qb = $this->documentManager->createQueryBuilder(OldContract::class);
            $expr = $qb->expr();

            $applicationRequestDocs = $qb->addAnd($expr->field('status')->equals('COMPLETED'))
                ->addAnd(
                    $expr->addOr($expr->field('_contractId')->equals($contract->getContractNumber()))
                        ->addOr($qb->expr()->field('customer_account')->equals($contract->getContractNumber()))
                )
                ->sort('_createdAt', 'asc')
                ->getQuery()
                ->execute();

            $contractNumber = $contract->getContractNumber();
            $oldContract = null;
            $resultContract = null;

            $arrayCount = \count($applicationRequestDocs);
            $loopPointer = 0;
            foreach ($applicationRequestDocs as $applicationRequestDoc) {
                ++$loopPointer;
                $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy([
                    'applicationRequestNumber' => $applicationRequestDoc->getApplicationRequestNumber(),
                ]);

                if (null !== $applicationRequest) {
                    $contractAction = null;
                    $contractStatus = null;
                    switch ($applicationRequest->getType()->getValue()) {
                        case ApplicationRequestType::ACCOUNT_CLOSURE:
                            $contractAction = new UpdateContractAction();
                            $contractAction->setType(new ContractActionType(ContractActionType::ACCOUNT_CLOSURE));
                            $contractStatus = new ContractStatus(ContractStatus::INACTIVE);
                            break;
                        case ApplicationRequestType::CONTRACT_APPLICATION:
                            $contractAction = new CreateContractAction();
                            $contractStatus = new ContractStatus(ContractStatus::ACTIVE);
                            break;
                        case ApplicationRequestType::CONTRACT_RENEWAL:
                            $contractAction = new UpdateContractAction();
                            $contractAction->setType(new ContractActionType(ContractActionType::CONTRACT_RENEWAL));
                            $contractStatus = new ContractStatus(ContractStatus::ACTIVE);
                            break;
                        case ApplicationRequestType::TRANSFER_OUT:
                            $contractAction = new UpdateContractAction();
                            $contractAction->setType(new ContractActionType(ContractActionType::TRANSFER_OUT));
                            $contractStatus = new ContractStatus(ContractStatus::INACTIVE);
                            break;
                        default:
                            break;
                    }

                    if (null !== $contractAction && null !== $contractStatus) {
                        if ($loopPointer !== $arrayCount) {
                            $resultContract = clone $contract;
                            $resultContract->setContractNumber(null);
                        } else {
                            $resultContract = $contract;
                        }

                        if (null === $oldContract) {
                            $oldContract = clone $contract;
                            $oldContract->setContractNumber(null);
                        }
                        $contractAction->setInstrument($applicationRequest);
                        $contractAction->setActionStatus(new ActionStatus(ActionStatus::COMPLETED));
                        $contractAction->setObject($oldContract);

                        $resultContract->setStatus($contractStatus);
                        if (null !== $applicationRequestDoc->getBillingPeriodId()) {
                            $resultContract->setBillingPeriodId($applicationRequestDoc->getBillingPeriodId());
                        }
                        if (null !== $applicationRequestDoc->getIsBillEdonPaper()) {
                            $type = true === $applicationRequestDoc->getIsBillEdonPaper() ? BillSubscriptionType::HARDCOPY : BillSubscriptionType::ELECTRONIC;
                            $resultContract->addBillSubscriptionType($type);
                        }
                        if (null !== $applicationRequestDoc->getContractClosureNoticeDay()) {
                            $resultContract->setClosureNoticePeriod(new QuantitativeValue((string) $applicationRequestDoc->getContractClosureNoticeDay(), null, null, TimeType::DAY));
                        }
                        if (null !== $applicationRequestDoc->getContractPeriod() && !empty($applicationRequestDoc->getContractPeriod())) {
                            $resultContract->setContractPeriod(new QuantitativeValue($this->mapClosureNoticePeriod($applicationRequestDoc->getContractPeriod()), null, null, TimeType::MONTH));
                        }
                        if (null !== $applicationRequestDoc->getContractCustomize()) {
                            $resultContract->setCustomized($applicationRequestDoc->getContractCustomize());
                        }
                        if (null !== $applicationRequestDoc->getDeposit() && !empty($applicationRequestDoc->getDeposit())) {
                            $resultContract->setDepositRefundType($this->mapRefundType($applicationRequestDoc->getDeposit()));
                        }
                        if (null !== $applicationRequestDoc->getDepositAmount()) {
                            $resultContract->setDepositAmount(new MonetaryAmount((string) $applicationRequestDoc->getDepositAmount(), (string) $applicationRequestDoc->getDepositCurrency()));
                        }
                        if (null !== $applicationRequestDoc->getEbsAccountNo()) {
                            $resultContract->setEbsAccountNumber($applicationRequestDoc->getEbsAccountNo());
                        }
                        if (null !== $applicationRequestDoc->getContractEndDate()) {
                            $resultContract->setEndDate($applicationRequestDoc->getContractEndDate());
                        }
                        if (null !== $applicationRequestDoc->getGiroOption()) {
                            $resultContract->setGiroOption($applicationRequestDoc->getGiroOption());
                        }
                        if (null !== $applicationRequestDoc->getLocationCode()) {
                            $resultContract->setLocation($applicationRequestDoc->getLocationCode());
                        }
                        if (null !== $applicationRequestDoc->getLockInPeriod()) {
                            $resultContract->setLockInDate($applicationRequestDoc->getLockInPeriod());
                        }
                        if (null !== $applicationRequestDoc->getMeterOption()) {
                            $resultContract->setMeterType($this->mapMeterType($applicationRequestDoc->getMeterOption()));
                        }
                        if (null !== $applicationRequestDoc->getMsslAccountNo()) {
                            $resultContract->setMsslAccountNumber($applicationRequestDoc->getMsslAccountNo());
                        }
                        if (null !== $applicationRequestDoc->getRemark()) {
                            $resultContract->setRemark($applicationRequestDoc->getRemark());
                        }

                        if ('CONTRACT_APP' === $applicationRequestDoc->getAppType() && 'RESIDENTIAL' === $applicationRequestDoc->getType() && null !== $applicationRequestDoc->getIsSpAccountHolder()) {
                            $resultContract->setSelfApplication($applicationRequestDoc->getIsSpAccountHolder());
                        } else {
                            $resultContract->setSelfApplication(null);
                        }
                        if (null !== $applicationRequestDoc->getSelfReadOption()) {
                            $resultContract->setSelfReadMeterOption($applicationRequestDoc->getSelfReadOption());
                        }
                        if (null !== $applicationRequestDoc->getContractStartDate()) {
                            $resultContract->setStartDate($applicationRequestDoc->getContractStartDate());
                        }
                        if (!empty($applicationRequestDoc->getSubType())) {
                            $resultContract->setSubtype($this->mapContractSubType($applicationRequestDoc->getSubType()));
                        }
                        if (!empty($applicationRequestDoc->getType())) {
                            $resultContract->setType($this->mapContractType($applicationRequestDoc->getType()));
                        }
                        if (null !== $applicationRequest->getTariffRate()) {
                            $resultContract->setTariffRate($applicationRequest->getTariffRate());
                        }

                        $contractAction->setResult($resultContract);
                        $oldContract = $resultContract;

                        $this->entityManager->persist($contractAction->getObject());
                        $this->entityManager->persist($resultContract);
                        $actions[] = $contractAction;
                    }
                }
            }

            foreach ($actions as $action) {
                $contract->addAction($action);
            }

            if (\count($actions) > 0) {
                $this->entityManager->persist($contract);
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function createContract($contracts)
    {
        $batchSize = 1000;
        $contractBatch = [];
        foreach ($contracts as $key => $contractData) {
            /**
             *@var Contract|null
             */
            $existingContract = null;
            if (null !== $contractData->getContractId()) {
                if (isset($contractBatch[$contractData->getContractId()])) {
                    $existingContract = $contractBatch[$contractData->getContractId()];
                } else {
                    $existingContract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData->getContractId()]);
                }
            } elseif (null !== $contractData->getCustomerAccount()) {
                if (isset($contractBatch[$contractData->getCustomerAccount()])) {
                    $existingContract = $contractBatch[$contractData->getCustomerAccount()];
                } else {
                    $existingContract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData->getCustomerAccount()]);
                }
            } else {
                //if no customer_account or _contractId defined
                continue;
            }
            /**
             * @var ApplicationRequest|null
             */
            $existingApplicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $contractData->getApplicationRequestNumber()]);
            /**
             * @var Contract|null
             */
            $oldContract = null;
            if (null !== $existingContract) {
                $contract = $existingContract;
            } else {
                $contract = new Contract();
            }

            if (null !== $contractData->getContractId()) {
                $contract->setContractNumber($contractData->getContractId());
            } elseif (null !== $contractData->getCustomerAccount()) {
                $contract->setContractNumber($contractData->getCustomerAccount());
            }

            if (null !== $contract->getContractNumber()) {
                if (null !== $existingApplicationRequest) {
                    $existingApplicationRequest->setContract($contract);
                }
                if (\iter\count($contractData->getCustomerId()) > 0) {
                    /**
                     * @var CustomerAccount|null
                     */
                    $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $contractData->getCustomerId()[0]['_customerId']]);
                    if (null !== $customer) {
                        $contract->setCustomer($customer);
                        $contract->setCustomerType($customer->getType());

                        if (\iter\count($contractData->getContactId()) > 0) {
                            /**
                             * @var CustomerAccount|null
                             */
                            $contactPerson = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $contractData->getContactId()[0]['_customerId']]);
                            if (null !== $contactPerson) {
                                $contract->setContactPerson($contactPerson);
                            } else {
                                $contract->setContactPerson($customer);
                            }
                        } else {
                            $contract->setContactPerson($customer);
                        }
                    }
                } else {
                    $this->logger->info($contractData->getContractId().' skipped'."\n\r");
                    continue;
                }

                if (null !== $contractData->getBillingPeriodId()) {
                    $contract->setBillingPeriodId($contractData->getBillingPeriodId());
                }
                if (null !== $contractData->getIsBillEdonPaper()) {
                    $type = true === $contractData->getIsBillEdonPaper() ? BillSubscriptionType::HARDCOPY : BillSubscriptionType::ELECTRONIC;
                    $contract->addBillSubscriptionType($type);
                }
                if (null !== $contractData->getContractClosureNoticeDay()) {
                    $contract->setClosureNoticePeriod(new QuantitativeValue((string) $contractData->getContractClosureNoticeDay(), null, null, TimeType::DAY));
                }
                if (null !== $contractData->getContractPeriod() && !empty($contractData->getContractPeriod())) {
                    $contract->setContractPeriod(new QuantitativeValue($this->mapClosureNoticePeriod($contractData->getContractPeriod()), null, null, TimeType::MONTH));
                }
                if (null !== $contractData->getContractCustomize()) {
                    $contract->setCustomized($contractData->getContractCustomize());
                }
                if (null !== $contractData->getDeposit() && !empty($contractData->getDeposit())) {
                    $contract->setDepositRefundType($this->mapRefundType($contractData->getDeposit()));
                }
                if (null !== $contractData->getDepositAmount()) {
                    $contract->setDepositAmount(new MonetaryAmount((string) $contractData->getDepositAmount(), (string) $contractData->getDepositCurrency()));
                }
                if (null !== $contractData->getEbsAccountNo()) {
                    $contract->setEbsAccountNumber($contractData->getEbsAccountNo());
                }
                if (null !== $contractData->getContractEndDate()) {
                    $contract->setEndDate($contractData->getContractEndDate());
                }
                if (null !== $contractData->getGiroOption()) {
                    $contract->setGiroOption($contractData->getGiroOption());
                }
                if (null !== $contractData->getLocationCode()) {
                    $contract->setLocation($contractData->getLocationCode());
                }
                if (null !== $contractData->getLockInPeriod()) {
                    $contract->setLockInDate($contractData->getLockInPeriod());
                }
                if (null !== $contractData->getMeterOption()) {
                    $contract->setMeterType($this->mapMeterType($contractData->getMeterOption()));
                }
                if (null !== $contractData->getMsslAccountNo()) {
                    $contract->setMsslAccountNumber($contractData->getMsslAccountNo());
                }
                if (null !== $contractData->getRemark()) {
                    $contract->setRemark($contractData->getRemark());
                }
                if ('CONTRACT_APP' === $contractData->getAppType() && 'RESIDENTIAL' === $contractData->getType() && null !== $contractData->getIsSpAccountHolder()) {
                    $contract->setSelfApplication($contractData->getIsSpAccountHolder());
                } else {
                    $contract->setSelfApplication(null);
                }
                if (null !== $contractData->getSelfReadOption()) {
                    $contract->setSelfReadMeterOption($contractData->getSelfReadOption());
                }
                if (null !== $contractData->getContractStartDate()) {
                    $contract->setStartDate($contractData->getContractStartDate());
                }
                $contract->setStatus(new ContractStatus(ContractStatus::ACTIVE));
                if (!empty($contractData->getSubType())) {
                    $contract->setSubtype($this->mapContractSubType($contractData->getSubType()));
                }
                if (!empty($contractData->getType())) {
                    $contract->setType($this->mapContractType($contractData->getType()));
                }

                $contractBatch[$contract->getContractNumber()] = $contract;
                $this->entityManager->persist($contract);
            }

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $contractBatch = [];
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function updateContractAddresses($contracts)
    {
        $batchSize = 1000;
        foreach ($contracts as $key => $contractData) {
            /**
             * @var Contract|null
             */
            $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData->getContractId()]);
            if (null !== $contract) {
                foreach ($contractData->getAddresses() as $addressData) {
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

                    $contractPostalAddress = new ContractPostalAddress();
                    $contractPostalAddress->setContract($contract);
                    $contractPostalAddress->setAddress($address);

                    $this->entityManager->persist($contractPostalAddress);
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

    public function updateContractAverageConsumptions($contracts)
    {
        $batchSize = 1000;
        foreach ($contracts as $key => $contractData) {
            /**
             * @var Contract|null
             */
            $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData->getContractId()]);
            if (null !== $contract) {
                $unitCode = null;
                $value = null;
                if (\iter\count($contractData->getAverageConsumption()) > 0) {
                    $unitCode = $contractData->getAverageConsumption()[0]['key'];
                }
                if (null !== $contractData->getConsumptionAmount()) {
                    $value = (string) $contractData->getConsumptionAmount();
                }
                $contract->setAverageConsumption(new QuantitativeValue($value, null, null, $unitCode));
            }

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function updateContractBlamable($contracts)
    {
        $batchSize = 1000;
        foreach ($contracts as $key => $contractData) {
            /**
             * @var Contract|null
             */
            $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData->getContractId()]);
            $expr = $this->entityManager->getExpressionBuilder();
            $userRepo = $this->entityManager->getRepository(User::class)->createQueryBuilder('user')->select('user')
                ->join('user.bridgeUser', 'bridgeUser');
            if (null !== $contract) {
                if (\iter\count($contractData->getCreatedBy()) > 0) {
                    /**
                     * @var User|null
                     */
                    $createdBy = $userRepo
                        ->where($expr->eq('bridgeUser.bridgeUserId', ':id'))
                        ->setParameters([
                            'id' => $contractData->getCreatedBy()[0]['_id'],
                        ])
                        ->getQuery()
                        ->getOneOrNullResult(Query::HYDRATE_OBJECT);
                    $contract->setCreator($createdBy);
                }
                if (\iter\count($contractData->getUpdatedBy()) > 0) {
                    /**
                     * @var User|null
                     */
                    $updatedBy = $userRepo
                        ->where($expr->eq('bridgeUser.bridgeUserId', ':id'))
                        ->setParameters([
                            'id' => $contractData->getUpdatedBy()[0]['_id'],
                        ])
                        ->getQuery()
                        ->getOneOrNullResult(Query::HYDRATE_OBJECT);
                    $contract->setAgent($updatedBy);
                }
                if (null !== $contractData->getCreatedAt()) {
                    $contract->setDateCreated($contractData->getCreatedAt());
                }
                if (null !== $contractData->getUpdatedAt()) {
                    $contract->setDateModified($contractData->getUpdatedAt());
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

    public function updateContractCustomerAccountDetails($contracts)
    {
        $batchSize = 1000;
        foreach ($contracts as $key => $contractData) {
            /**
             * @var Contract|null
             */
            $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData->getContractId()]);

            if (null !== $contract) {
                $customer = null;
                if (\iter\count($contractData->getCustomerId()) > 0) {
                    /**
                     * @var CustomerAccount|null
                     */
                    $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $contractData->getCustomerId()[0]['_customerId']]);
                    if (null !== $customer) {
                        $contract->setCustomer($customer);
                        $customer->addContract($contract);
                        if (AccountType::CORPORATE === $customer->getType()->getValue()) {
                            $contract->setCustomerType(new AccountType(AccountType::CORPORATE));
                            if (null === $contract->getCorporationDetails()) {
                                if (null !== $customer->getCorporationDetails()) {
                                    $corporation = clone $customer->getCorporationDetails();

                                    $this->entityManager->persist($corporation);
                                    $contract->setCorporationDetails($corporation);
                                }
                            }
                        } elseif (AccountType::INDIVIDUAL === $customer->getType()->getValue()) {
                            $contract->setCustomerType(new AccountType(AccountType::INDIVIDUAL));
                            if (null === $contract->getPersonDetails()) {
                                if (null !== $customer->getPersonDetails()) {
                                    $person = clone $customer->getPersonDetails();

                                    $this->entityManager->persist($person);
                                    $contract->setPersonDetails($person);
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

    public function updateContractDigitalDocument($contracts)
    {
        $batchSize = 1000;
        foreach ($contracts as $key => $contractData) {
            /**
             * @var Contract|null
             */
            $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData->getContractId()]);

            if (null !== $contract) {
                if (!empty($contractData->getAttachments())) {
                    if (\count($contractData->getAttachments()) > 0) {
                        foreach ($contractData->getAttachments() as $attachment) {
                            /**
                             * @var DigitalDocument
                             */
                            $digitalDocument = new DigitalDocument();
                            if (!empty($attachment['attached'])) {
                                $digitalDocument->setContentPath($attachment['attached']);
                            }
                            if (!empty($attachment['desc'])) {
                                $digitalDocument->setName($attachment['desc']);
                            }

                            $contract->addSupplementaryFile($digitalDocument);
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

    public function updateContractRefundee($contracts)
    {
        $batchSize = 1000;

        foreach ($contracts as $key => $contractData) {
            if (null !== $contractData->getRefundPayeeName() && null !== $contractData->getRefundPayeeNric()) {
                $isExisting = false;
                /**
                 * @var Contract|null
                 */
                $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData->getContractId()]);

                if (null !== $contract) {
                    $customerDocument = $this->documentManager->getRepository(OldCustomerAccount::class)->findOneBy(['id' => $contractData->getCustomerId()]);

                    if (null !== $customerDocument) {
                        $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerDocument->getCustomerId()]);

                        if (null !== $customer) {
                            $personDetails = $customer->getPersonDetails();

                            if (null !== $personDetails) {
                                if (\count($personDetails->getIdentifiers()) > 0) {
                                    foreach ($personDetails->getIdentifiers() as $identifier) {
                                        if ($contractData->getRefundPayeeName() === $personDetails->getName() && $contractData->getRefundPayeeNric() === $identifier->getValue()) {
                                            $isExisting = true;
                                            $person = clone $personDetails;
                                            $contract->setRefundee($customer);
                                            $contract->setRefundeeDetails($person);
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (!$isExisting) {
                        /**
                         * @var CustomerAccount
                         */
                        $customerAccount = new CustomerAccount();
                        /**
                         * @var Person
                         */
                        $person = new Person();
                        /**
                         * @var Identification
                         */
                        $identifier = new Identification();

                        if (!empty($contractData->getRefundPayeeName())) {
                            $person->setName($contractData->getRefundPayeeName());
                        }
                        if (!empty($contractData->getRefundPayeeNric())) {
                            $identifier->setName($this->mapIdentificationName('UNIQUE_ENTITY_NUMBER'));
                            $identifier->setValue($contractData->getRefundPayeeNric());
                            $person->addIdentifier($identifier);
                        }

                        $customerAccount->addCategory(AccountCategory::NONCUSTOMER);
                        $customerAccount->setType(new AccountType(AccountType::INDIVIDUAL));
                        $customerAccount->setStatus(new CustomerAccountStatus(CustomerAccountStatus::INACTIVE));
                        $customerAccount->setPersonDetails($person);

                        $this->entityManager->persist($customerAccount);
                        $contract->setRefundee($customerAccount);
                        $contract->setPersonDetails(clone $person);
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

    public function updateContractTariffRate($contracts)
    {
        $batchSize = 1000;
        foreach ($contracts as $key => $contractData) {
            /**
             * @var Contract|null
             */
            $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData->getContractId()]);

            if (null !== $contract) {
                /**
                 * @var TariffRate|null
                 */
                $tariffRate = $this->entityManager->getRepository(TariffRate::class)->findOneBy([
                    'tariffRateNumber' => $contractData->getPromotionCodeId()['promotion_code'],
                    'isBasedOn' => null,
                ]);

                if (null !== $tariffRate) {
                    $clonedTariffRate = clone $tariffRate;
                    $clonedTariffRate->setIsBasedOn($tariffRate);
                    $contract->setTariffRate($clonedTariffRate);

                    $this->entityManager->persist($clonedTariffRate);
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

    /**
     * Validates an application request in the old version.
     *
     * @param ApplicationRequest $applicationRequest
     * @param BridgeUser         $agent
     */
    public function validateApplicationRequest(ApplicationRequest $applicationRequest, BridgeUser $agent)
    {
        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'x-access-token' => $agent->getAuthToken(),
        ];

        if (null === $applicationRequest->getCustomer()) {
            throw new BadRequestHttpException('Customer not found!');
        }

        $error = $this->validateCustomer($headers, $applicationRequest->getCustomer());

        if (null !== $error) {
            throw new BadRequestHttpException($error);
        }

        $error = $this->validatePremiseAddress($headers, $applicationRequest);

        if (null !== $error) {
            throw new BadRequestHttpException($error);
        }
    }

    /**
     * Validates an application request customer in the old version.
     *
     * @param array           $headers
     * @param CustomerAccount $customerAccount
     *
     * @return string|null
     */
    private function validateCustomer(array $headers, CustomerAccount $customerAccount)
    {
        $modifier = new AppendSegment('contract/get/active');
        $uri = $modifier->process($this->baseUri);

        $identity = null;
        $name = null;

        if (AccountType::CORPORATE === $customerAccount->getType()->getValue()) {
            $corporationDetails = $customerAccount->getCorporationDetails();

            if (null !== $corporationDetails) {
                $identity = $this->bridgeDataMapper->mapIdentifierByKey($corporationDetails->getIdentifiers(), IdentificationName::UNIQUE_ENTITY_NUMBER);
                $name = $corporationDetails->getName();
            }
        } elseif (AccountType::INDIVIDUAL === $customerAccount->getType()->getValue()) {
            $personDetails = $customerAccount->getPersonDetails();

            if (null !== $personDetails) {
                $identity = $this->bridgeDataMapper->mapIdentifierByKey($personDetails->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
                $name = $personDetails->getName();
            }
        }

        if (null !== $name && null !== $identity) {
            $uri = (new MergeQuery((string) UriQuery::createFromPairs([
                'identity' => $identity,
                'name' => \strtoupper($name),
                'category' => $customerAccount->getType()->getValue(),
                'source' => 'PARTNER',
            ])))->process($uri);

            $this->logger->info('Sending GET to '.$uri);

            $validateCustomerRequest = new GuzzlePsr7Request('GET', $uri, $headers);
            $validateCustomerResponse = $this->client->send($validateCustomerRequest);
            $validateCustomerResult = \json_decode((string) $validateCustomerResponse->getBody(), true);

            $this->logger->info('Result from GET to '.$uri);
            $this->logger->info(\json_encode($validateCustomerResult, JSON_PRETTY_PRINT));

            if (!(200 === $validateCustomerResult['status'] && 1 === $validateCustomerResult['flag'])) {
                return ErrorResolver::getErrorMessage($validateCustomerResult);
            }
        }

        return null;
    }

    /**
     * Validates an application request premise address in the old version.
     *
     * @param array              $headers
     * @param ApplicationRequest $applicationRequest
     *
     * @return string|null
     */
    private function validatePremiseAddress(array $headers, ApplicationRequest $applicationRequest)
    {
        $modifier = new AppendSegment('contract/get/match');
        $uri = $modifier->process($this->baseUri);
        $queryParams = [];
        $customerAccount = $applicationRequest->getCustomer();

        if (null === $customerAccount) {
            throw new BadRequestHttpException('Customer not found!');
        }

        if (AccountType::CORPORATE === $customerAccount->getType()->getValue()) {
            $queryParams['category'] = 'CORPORATE';
            $corporationDetails = $customerAccount->getCorporationDetails();

            if (null !== $corporationDetails) {
                $identity = $this->bridgeDataMapper->mapIdentifierByKey($corporationDetails->getIdentifiers(), IdentificationName::UNIQUE_ENTITY_NUMBER);
                $queryParams['nric_fin_uen'] = $identity;
            }
        } elseif (AccountType::INDIVIDUAL === $customerAccount->getType()->getValue()) {
            $queryParams['category'] = 'INDIVIDUAL';
            $personDetails = $customerAccount->getPersonDetails();

            if (null !== $personDetails) {
                $identity = $this->bridgeDataMapper->mapIdentifierByKey($personDetails->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
                $name = $personDetails->getName();
                $queryParams['nric_fin_uen'] = $identity;
            }
        }

        foreach ($applicationRequest->getAddresses() as $address) {
            if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                $queryParams['building_name'] = $address->getBuildingName();
                $queryParams['city'] = $address->getAddressLocality();
                $queryParams['country'] = $address->getAddressCountry();
                $queryParams['floor'] = $address->getFloor();
                $queryParams['house_no'] = $address->getHouseNumber();
                $queryParams['postal_code'] = $address->getPostalCode();
                $queryParams['street'] = $address->getStreetAddress();
                $queryParams['unit_no'] = $address->getUnitNumber();
            }
        }

        if (!empty($queryParams)) {
            $uri = (new MergeQuery((string) UriQuery::createFromPairs($queryParams)))->process($uri);

            $this->logger->info('Sending GET to '.$uri);

            $validatePremiseAddressRequest = new GuzzlePsr7Request('GET', $uri, $headers);
            $validatePremiseAddressResponse = $this->client->send($validatePremiseAddressRequest);
            $validatePremiseAddressResult = \json_decode((string) $validatePremiseAddressResponse->getBody(), true);

            $this->logger->info('Result from GET to '.$uri);
            $this->logger->info(\json_encode($validatePremiseAddressResult, JSON_PRETTY_PRINT));

            if (!(200 === $validatePremiseAddressResult['status'] && 1 === $validatePremiseAddressResult['flag'])) {
                return ErrorResolver::getErrorMessage($validatePremiseAddressResult);
            }
        }

        return null;
    }

    /**
     * Map application request data for old version.
     *
     * @param ApplicationRequest $applicationRequest
     * @param BridgeUser|null    $bridgeUser
     *
     * @return array
     */
    private function getApplicationRequestData(ApplicationRequest $applicationRequest, ?BridgeUser $bridgeUser = null)
    {
        $now = new \DateTime();
        $applicationRequestData = [];

        $applicationRequestData['remark'] = $applicationRequest->getRemark();

        $applicationRequestData['consumption_amount'] = $applicationRequest->getAverageConsumption()->getValue();

        if (null !== $applicationRequest->getAverageConsumption()->getUnitCode()) {
            $consumptionUnit = $this->bridgeDataMapper->mapConsumptionUnit($applicationRequest->getAverageConsumption()->getUnitCode());

            if (null !== $consumptionUnit) {
                $applicationRequestData['average_consumption'] = $consumptionUnit;
            }
        }

        if (null !== $applicationRequest->getEbsAccountNumber()) {
            $applicationRequestData['ebs_account_no'] = $applicationRequest->getEbsAccountNumber();
        }

        if (null !== $applicationRequest->getMsslAccountNumber()) {
            $applicationRequestData['mssl_account_no'] = $applicationRequest->getMsslAccountNumber();
            $applicationRequestData['has_mssl'] = true;
        }

        if (null !== $applicationRequest->getMeterType()) {
            $applicationRequestData['meter_option'] = $applicationRequest->getMeterType()->getValue();
        }

        if (null !== $applicationRequest->isGIROOption()) {
            $applicationRequestData['giro_option'] = $applicationRequest->isGIROOption();
        }

        if (null !== $applicationRequest->isSelfReadMeterOption()) {
            $applicationRequestData['self_read_option'] = $applicationRequest->isSelfReadMeterOption();
        }

        if (null !== $applicationRequest->getTariffRate()) {
            if (null !== $applicationRequest->getTariffRate()->getIsBasedOn()) {
                $applicationRequestData['promotion_code_id'] = $applicationRequest->getTariffRate()->getIsBasedOn()->getBridgeId();
            } else {
                $applicationRequestData['promotion_code_id'] = $applicationRequest->getTariffRate()->getBridgeId();
            }
        }

        if (null !== $applicationRequest->getPreferredEndDate()) {
            $applicationRequestData['prefer_turn_off_date'] = $applicationRequest->getPreferredEndDate()->format('c');
        }

        if (null !== $applicationRequest->getPreferredStartDate()) {
            $applicationRequestData['prefer_turn_on_date'] = $applicationRequest->getPreferredStartDate()->format('c');
        }

        $applicationRequestData['prefer_contact_method'] = 'PHONE';

        $applicationRequestData['status'] = $applicationRequest->getStatus()->getValue();

        if (null !== $applicationRequest->getContractType()) {
            $applicationRequestData['type'] = $applicationRequest->getContractType()->getValue();
        }

        $applicationRequestData['sub_type'] = $this->bridgeDataMapper->mapContractSubType($applicationRequest->getContractSubtype());
        $applicationRequestData['app_type'] = $this->bridgeDataMapper->mapApplicationRequestType($applicationRequest->getType()->getValue());
        $applicationRequestData['is_owner'] = false;
        $applicationRequestData['source'] = 'PARTNER';

        $customer = $applicationRequest->getCustomer();
        if (null === $customer) {
            return [];
        }
        $contactPersonDetails = $applicationRequest->getPersonDetails();

        if (AccountType::CORPORATE === $customer->getType()->getValue()) {
            $corporationDetails = $customer->getCorporationDetails();

            if (null !== $corporationDetails && null !== $contactPersonDetails) {
                $contactData = [];
                $identity = [];

                $uen = $this->bridgeDataMapper->mapIdentifierByKey($corporationDetails->getIdentifiers(), IdentificationName::UNIQUE_ENTITY_NUMBER);

                if (null !== $uen) {
                    $identity = ['uen' => $uen];
                }

                $contactPoints = $this->bridgeDataMapper->mapContactPoints($corporationDetails->getContactPoints());

                if (empty($contactPoints['phone_number'])) {
                    $contactPoints['phone_number'] = [
                        'country_code' => '+65',
                        'number' => '',
                    ];
                }

                if (empty($contactPoints['mobile_number'])) {
                    $contactPoints['mobile_number'] = [
                        'country_code' => '+65',
                        'number' => '',
                    ];
                }

                if (empty($contactPoints['fax_number'])) {
                    $contactPoints['fax_number'] = [
                        'country_code' => '+65',
                        'number' => '',
                    ];
                }

                $contactData = \array_merge($contactData, $contactPoints);

                $applicationRequestData['customer'] = [
                    'category' => 'CORPORATE',
                    'contact_person' => [
                        'company_name' => $corporationDetails->getName(),
                        'company_name2' => $corporationDetails->getLegalName(),
                        'identity' => $identity,
                        'contact' => $contactData,
                    ],
                ];

                $honorificPrefix = $this->bridgeDataMapper->mapHonorificPrefix($contactPersonDetails->getHonorificPrefix());

                if (null !== $honorificPrefix) {
                    $applicationRequestData['contact_salutation'] = $honorificPrefix;
                }

                $applicationRequestData['contact_designation'] = $contactPersonDetails->getJobTitle();
                $applicationRequestData['contact_first_name'] = $contactPersonDetails->getGivenName();
                $applicationRequestData['contact_middle_name'] = $contactPersonDetails->getAdditionalName();
                $applicationRequestData['contact_last_name'] = $contactPersonDetails->getFamilyName();
                $applicationRequestData['contact_full_name'] = $contactPersonDetails->getName();

                $contactPersonContactPoints = $this->bridgeDataMapper->mapContactPoints($contactPersonDetails->getContactPoints());

                if (!empty($contactPersonContactPoints['mobile_number'])) {
                    $applicationRequestData['contact_mobile'] = $contactPersonContactPoints['mobile_number'];
                } else {
                    $applicationRequestData['contact_mobile'] = [
                        'country_code' => '+65',
                        'number' => '',
                    ];
                }

                if (!empty($contactPersonContactPoints['phone_number'])) {
                    $applicationRequestData['contact_phone'] = $contactPersonContactPoints['phone_number'];
                } else {
                    $applicationRequestData['contact_phone'] = [
                        'country_code' => '+65',
                        'number' => '',
                    ];
                }

                if (!empty($contactPersonContactPoints['fax_number'])) {
                    $applicationRequestData['contact_fax'] = $contactPersonContactPoints['fax_number'];
                } else {
                    $applicationRequestData['contact_fax'] = [
                        'country_code' => '+65',
                        'number' => '',
                    ];
                }

                if (!empty($contactPersonContactPoints['email'])) {
                    $applicationRequestData['contact_email'] = $contactPersonContactPoints['email'];
                }

                $nric = $this->bridgeDataMapper->mapIdentifierByKey($contactPersonDetails->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);

                if (null !== $nric) {
                    $applicationRequestData['nric_fin'] = $nric;
                }
            }
        } else {
            $contactData = [];
            $identity = [];
            if (null !== $contactPersonDetails) {
                $nric = $this->bridgeDataMapper->mapIdentifierByKey($contactPersonDetails->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);

                if (null !== $nric) {
                    $identity = ['nric_fin' => $nric];
                }

                $contactPoints = $this->bridgeDataMapper->mapContactPoints($contactPersonDetails->getContactPoints());

                if (empty($contactPoints['phone_number'])) {
                    $contactPoints['phone_number'] = [
                        'country_code' => '+65',
                        'number' => '',
                    ];
                }

                if (empty($contactPoints['mobile_number'])) {
                    $contactPoints['mobile_number'] = [
                        'country_code' => '+65',
                        'number' => '',
                    ];
                }

                if (empty($contactPoints['fax_number'])) {
                    $contactPoints['fax_number'] = [
                        'country_code' => '+65',
                        'number' => '',
                    ];
                }

                $contactData = \array_merge($contactData, $contactPoints);

                $applicationRequestData['customer'] = [
                    'category' => 'INDIVIDUAL',
                    'contact_person' => [
                        'full_name' => $contactPersonDetails->getName(),
                        'first_name' => $contactPersonDetails->getGivenName(),
                        'last_name' => $contactPersonDetails->getFamilyName(),
                        'middle_name' => $contactPersonDetails->getAdditionalName(),
                        'nick_name' => $contactPersonDetails->getAlternateName(),
                        'identity' => $identity,
                        'contact' => $contactData,
                    ],
                ];

                $honorificPrefix = $this->bridgeDataMapper->mapHonorificPrefix($contactPersonDetails->getHonorificPrefix());

                if (null !== $honorificPrefix) {
                    $applicationRequestData['customer']['contact_person']['salutation'] = $honorificPrefix;
                }
            }
        }

        $addressData = $this->bridgeDataMapper->mapContractAddresses($applicationRequest->getAddresses());

        $premiseAddress = $addressData[0];
        $mailingAddress = $addressData[1];

        if ($mailingAddress === $premiseAddress) {
            $applicationRequestData['as_premise_address'] = true;
        } else {
            $applicationRequestData['as_premise_address'] = false;
        }

        $mailingAddress['address_type'] = 'BILL_ADDRESS';
        $premiseAddress['address_type'] = 'PREMISE_ADDRESS';

        $applicationRequestData['address'] = [$premiseAddress, $mailingAddress];
        $applicationRequestData['attachments'] = [];

        if (\count($applicationRequest->getSupplementaryFiles()) > 0) {
            $attachments = $this->bridgeDataMapper->mapAttachments($applicationRequest->getSupplementaryFiles());

            if (\count($attachments) > 0) {
                $applicationRequestData['attachments'] = $attachments;
            }
        }

        $applicationRequestData['deposit_amount'] = $applicationRequest->getDepositAmount()->getValue();
        $applicationRequestData['deposit_currency'] = $applicationRequest->getDepositAmount()->getCurrency();

        if (null !== $applicationRequest->getReferralSource()) {
            switch ($applicationRequest->getReferralSource()->getValue()) {
                case ReferralSource::ONLINE_ADVERTISEMENT:
                    $applicationRequestData['ref_source'] = 'ONLINE_ADVERTISING';
                    break;
                case ReferralSource::SOCIAL_MEDIA:
                    $applicationRequestData['ref_source'] = 'SOCIAL_MEDIA';
                    break;
                case ReferralSource::ANOTHER_WEBSITE:
                    $applicationRequestData['ref_source'] = 'ANOTHER_WEBSITE';
                    break;
                case ReferralSource::EMAIL_NEWSLETTER:
                    $applicationRequestData['ref_source'] = 'EMAIL_NEWSLETTER';
                    break;
                case ReferralSource::REFERRAL:
                    $applicationRequestData['ref_source'] = 'REFERRAL';
                    break;
                case ReferralSource::EVENT_ROADSHOW:
                    $applicationRequestData['ref_source'] = 'EVENT_ROADSHOW';
                    break;
                case ReferralSource::NEWSPAPER_MAGAZINE:
                    $applicationRequestData['ref_source'] = 'NEWSPAPER_MAGAZINE';
                    break;
                case ReferralSource::POSTCARD_LETTER:
                    $applicationRequestData['ref_source'] = 'POSTCARD_LETTER';
                    break;
                case ReferralSource::RADIO_TV:
                    $applicationRequestData['ref_source'] = 'RADIO_TV';
                    break;
                case ReferralSource::MRT_BUS:
                    $applicationRequestData['ref_source'] = 'MRT_BUS';
                    break;
                case ReferralSource::OTHERS:
                    $applicationRequestData['ref_source'] = 'OTHERS';
                    $applicationRequestData['indicate'] = $applicationRequest->getSpecifiedReferralSource();
                    break;
                default:
                    break;
            }
        }

        if (null !== $applicationRequest->getAcquiredFrom()) {
            $customerAccount = $applicationRequest->getAcquiredFrom();
            $accountNumber = $customerAccount->getAccountNumber();
            $administrator = null;
            $name = null;
            $salesRepresentativeName = null;

            if (null !== $customerAccount->getPartnerDetails()) {
                $partner = $customerAccount->getPartnerDetails();
                if (null !== $partner->getAdministrator() && null !== $partner->getAdministrator()->getBridgeUser()) {
                    $administrator = $partner->getAdministrator()->getBridgeUser()->getBridgeUserId();
                }
            }

            if (AccountType::CORPORATE === $customerAccount->getType()->getValue() && null !== $customerAccount->getCorporationDetails()) {
                $name = $customerAccount->getCorporationDetails()->getName();

                if (null !== $bridgeUser && null !== $bridgeUser->getUser()->getCustomerAccount()->getPersonDetails()) {
                    $salesRepresentativeName = $bridgeUser->getUser()->getCustomerAccount()->getPersonDetails()->getName();
                }
            } elseif (AccountType::INDIVIDUAL === $customerAccount->getType()->getValue() && null !== $customerAccount->getPersonDetails()) {
                $name = $customerAccount->getPersonDetails()->getName();
            }

            $channel = null;
            $sourceUrl = $applicationRequest->getSourceUrl();

            if (null !== $sourceUrl) {
                $sourceUri = HttpUri::createFromString($sourceUrl);
                $query = new UriQuery($sourceUri->getQuery());

                if ($query->hasPair('channel')) {
                    $channel = $query->getPair('channel');
                }
            }

            $applicationRequestData['partner'] = [
                'id' => $accountNumber,
                'name' => $name,
                'sales_rep_name' => $salesRepresentativeName,
                'employee_respon_id' => $administrator,
                'reference_url' => $sourceUrl,
                'channel' => $channel,
            ];
        }
        $applicationRequestData['location_code'] = $applicationRequest->getLocation();
        $applicationRequestData['is_spaccount_holder'] = true === $applicationRequest->isSelfApplication() ? true : false;

        foreach ($applicationRequest->getBillSubscriptionTypes() as $billSubscriptionType) {
            if (BillSubscriptionType::HARDCOPY === $billSubscriptionType) {
                $applicationRequestData['is_billedon_paper'] = true;
            }
        }
        $applicationRequestData['_tempId'] = $applicationRequest->getTemporaryNumber();

        return $applicationRequestData;
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

    private function mapClosureNoticePeriod(string $period)
    {
        $periods = [
            'NA' => null,
            '12 months' => '12',
            '24 months' => '24',
            '6 months' => '6',
        ];

        return $periods[$period];
    }

    private function mapContractType(string $type)
    {
        $types = [
            'COMMERCIAL' => new ContractType(ContractType::COMMERCIAL),
            'RESIDENTIAL' => new ContractType(ContractType::RESIDENTIAL),
        ];

        return $types[$type];
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

    private function mapRefundType(string $type)
    {
        $types = [
            'OFFSET' => new RefundType(RefundType::BILL_OFFSET),
            'REFUND' => new RefundType(RefundType::FULL_REFUND),
        ];

        return $types[$type];
    }

    private function queueBridgeFixer(ApplicationRequest $applicationRequest)
    {
        $this->webServicesQueue->schedule(new DisqueJob([
            'data' => [
                'applicationRequestNumber' => $applicationRequest->getTemporaryNumber(),
            ],
            'type' => JobType::FIX_DAMN_BRIDGE,
        ]), (new \DateTime())->modify('+10 seconds'));
    }
}
