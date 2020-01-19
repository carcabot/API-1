<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Domain\Command\Contract\UpdatePointCreditsActions;
use App\Domain\Command\CustomerAccount\UpdateCategories;
use App\Domain\Command\CustomerAccount\UpdateRelationships;
use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\ContractPostalAddress;
use App\Entity\CreateContractAction;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountPostalAddress;
use App\Entity\CustomerAccountRelationship;
use App\Entity\EarnContractCreditsAction;
use App\Entity\ExpireContractCreditsAction;
use App\Entity\InternalDocument;
use App\Entity\PostalAddress;
use App\Entity\ReferralCreditsScheme;
use App\Enum\AccountType;
use App\Enum\ActionStatus;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Enum\CustomerAccountStatus;
use App\Enum\CustomerRelationshipType;
use App\Enum\DocumentType;
use App\Enum\PaymentMode;
use App\Enum\PostalAddressType;
use App\WebService\Billing\ClientInterface as BillingClient;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Tactician\CommandBus;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\SerializerInterface;

class ContractApplicationRequestStatusUpdater
{
    /**
     * @var string
     */
    private $appProfile;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var string
     */
    private $documentConverterHost;

    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var DisqueQueue
     */
    private $webServicesQueue;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var BillingClient
     */
    private $billingClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param string                 $appProfile
     * @param CommandBus             $commandBus
     * @param string                 $documentConverterHost
     * @param DisqueQueue            $emailsQueue
     * @param DisqueQueue            $webServicesQueue
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     * @param SerializerInterface    $serializer
     * @param BillingClient          $billingClient
     * @param LoggerInterface        $logger
     * @param string                 $timezone
     */
    public function __construct(string $appProfile, CommandBus $commandBus, string $documentConverterHost, DisqueQueue $emailsQueue, DisqueQueue $webServicesQueue, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, SerializerInterface $serializer, BillingClient $billingClient, LoggerInterface $logger, string $timezone)
    {
        $this->appProfile = $appProfile;
        $this->commandBus = $commandBus;
        $this->documentConverterHost = $documentConverterHost;
        $this->emailsQueue = $emailsQueue;
        $this->webServicesQueue = $webServicesQueue;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->serializer = $serializer;
        $this->billingClient = $billingClient;
        $this->logger = $logger;
        $this->timezone = new \DateTimeZone($timezone);
    }

    public function processArrayData(array $data, bool $forceUpdate = false)
    {
        $failedApplicationRequest = [];

        foreach ($data as $datum) {
            if (isset($datum['applicationRequest'])) {
                try {
                    $applicationRequest = $this->updateApplicationRequest($datum, $forceUpdate);
                } catch (\Exception $ex) {
                    $failedApplicationRequest[] = $this->rebuildXMLFile($datum);
                }
            }
        }

        return $failedApplicationRequest;
    }

    public function getContractForm(Contract $contract, ?\DateTime $dateSubmitted = null)
    {
        $this->entityManager->refresh($contract);
        $contractFormProfiles = [
            'Enworkz' => 'unionpower',
        ];
        $path = 'pdf/contract_form';
        $internalDocument = null;

        if (isset($contractFormProfiles[$this->billingClient->getProviderName()]) && ContractType::RESIDENTIAL === $contract->getType()->getValue()) {
            $contractData = $this->serializer->serialize($contract, 'jsonld', ['groups' => ['contract_form_read']]);
            $filename = \sprintf('%s.%s', $contract->getContractNumber(), 'pdf');
            $applicationRequestData = [];

            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['contract' => $contract->getId()]);
            if (null !== $applicationRequest) {
                if (null !== $applicationRequest->getDateSubmitted()) {
                    $applicationRequestData['dateSubmitted'] = $applicationRequest->getDateSubmitted()->format('c');
                } elseif (null !== $dateSubmitted) {
                    $applicationRequestData['dateSubmitted'] = $dateSubmitted->format('c');
                }
            }

            if (!empty($contractData)) {
                $baseUri = HttpUri::createFromString($this->documentConverterHost);
                $modifier = new AppendSegment($path);
                $uri = $modifier->process($baseUri);

                $client = new GuzzleClient();
                $headers = [
                    'User-Agent' => 'U-Centric API',
                    'Content-Type' => 'application/json',
                ];
                try {
                    $request = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode([
                        'applicationRequest' => $applicationRequestData,
                        'contract' => \json_decode($contractData),
                        'profile' => $contractFormProfiles[$this->billingClient->getProviderName()],
                    ]));
                    $filePath = \sprintf('%s/%s', \sys_get_temp_dir(), $filename);
                    $resource = \fopen($filePath, 'wb+');
                    $response = $client->send($request, ['save_to' => $filePath]);

                    $fileType = MimeTypeGuesser::getInstance()->guess($filePath);
                    $contentFile = new UploadedFile(
                        $filePath,
                        $filename,
                        $fileType,
                        null,
                        true
                    );

                    $internalDocument = new InternalDocument();
                    $internalDocument->setContentFile($contentFile);
                    $internalDocument->setType(new DocumentType(DocumentType::CUSTOMER_CONTRACT_FORM));
                    $internalDocument->setName($filename);
                    $internalDocument->setOwner($contract->getCustomer());
                    $this->entityManager->persist($internalDocument);
                    $this->entityManager->flush();
                    \fclose($resource);
                } catch (\Exception $e) {
                    // something wrong??
                    $this->logger->error($e->getMessage());
                }
            }
        }

        return $internalDocument;
    }

    private function createContract(array $contractData, CustomerAccount $customer, ?bool $selfApplication, ?CustomerAccount $contactPerson = null)
    {
        $contract = null;
        $now = new \DateTime();
        $addresses = $contractData['addresses'];
        $contractData['addresses'] = [];
        $contractData['customer'] = $this->iriConverter->getIriFromItem($customer);

        if (isset($contractData['contractNumber'])) {
            $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData['contractNumber']]);
        }

        if (null === $contract) {
            $contract = $this->serializer->deserialize(\json_encode($contractData), Contract::class, 'jsonld', ['contract_activation_create']);
        }

        if ($contract instanceof Contract) {
            // need to handle shit dates from webservice
            $utcTimezone = new \DateTimeZone('UTC');
            if (null !== $contract->getLockInDate()) {
                $lockInDate = clone $contract->getLockInDate();
                $lockInDate = new \DateTime($lockInDate->format('Y-m-d'), $this->timezone);
                $contract->setLockInDate($lockInDate->setTimezone($utcTimezone));
            }

            if (null !== $contract->getEndDate()) {
                $endDate = clone $contract->getEndDate();
                $endDate = new \DateTime($endDate->format('Y-m-d'), $this->timezone);
                $contract->setEndDate($endDate->setTimezone($utcTimezone));
            }

            if (null !== $contract->getStartDate()) {
                $startDate = clone $contract->getStartDate();
                $startDate = new \DateTime($startDate->format('Y-m-d'), $this->timezone);
                $contract->setStartDate($startDate->setTimezone($utcTimezone));
            }

            if (null === $contactPerson) {
                $contactPerson = $customer;
            }

            $contract->setCustomer($customer);
            $contract->setCustomerType($customer->getType());
            $contract->setContactPerson($contactPerson);
            $contract->setStatus(new ContractStatus(ContractStatus::ACTIVE));

            if (null !== $selfApplication) {
                $contract->setSelfApplication($selfApplication);
            }

            foreach ($addresses as $address) {
                $exists = false;
                $postalAddress = $this->serializer->deserialize(\json_encode($address), PostalAddress::class, 'jsonld', ['contract_activation_create']);

                if ($postalAddress instanceof PostalAddress) {
                    if (null === $postalAddress->getAddressCountry()) {
                        $countryCode = $this->timezone->getLocation()['country_code'];

                        $postalAddress->setAddressCountry($countryCode);

                        if (null === $postalAddress->getAddressLocality() && 'SG' === $countryCode) {
                            $postalAddress->setAddressLocality('SINGAPORE');
                        }
                    }

                    foreach ($contract->getAddresses() as $existingAddress) {
                        if ($existingAddress->getAddress()->__toString() === $postalAddress->__toString()) {
                            $exists = true;
                            break;
                        }
                    }

                    if (false === $exists) {
                        $contractAddress = new ContractPostalAddress();
                        $contractAddress->setAddress($postalAddress);
                        $contractAddress->setContract($contract);
                        $contractAddress->setValidFrom($now);

                        $this->entityManager->persist($contractAddress);

                        if (PostalAddressType::MAILING_ADDRESS === $postalAddress->getType()->getValue()) {
                            foreach ($customer->getAddresses() as $existingAddress) {
                                if ($existingAddress->getAddress()->__toString() === $postalAddress->__toString()) {
                                    $exists = true;
                                    break;
                                }
                            }

                            if (false === $exists) {
                                $customerAddress = new CustomerAccountPostalAddress();
                                $customerAddress->setAddress(clone $postalAddress);
                                $customerAddress->setCustomerAccount($customer);
                                $customerAddress->setValidFrom($now);

                                $this->entityManager->persist($customerAddress);
                            }
                        }
                    }
                }
            }

            // relationship
            if (AccountType::CORPORATE === $customer->getType()->getValue() ||
                (AccountType::INDIVIDUAL === $customer->getType()->getValue() && (null !== $contract->isSelfApplication() && false === $contract->isSelfApplication()) && $contactPerson !== $customer)
            ) {
                $relationship = $this->entityManager->getRepository(CustomerAccountRelationship::class)->findOneBy([
                    'from' => $contactPerson->getId(),
                    'to' => $customer->getId(),
                    'type' => new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON),
                ]);

                if (null === $relationship || false === $relationship->isValid()) {
                    $relationship = new CustomerAccountRelationship();
                    $relationship->setFrom($contactPerson);
                    $relationship->setTo($customer);
                    $relationship->setType(new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON));
                    $relationship->setValidFrom($now);
                }

                $contractRelated = false;
                foreach ($relationship->getContracts() as $relationshipContract) {
                    if ($contract->getId() === $relationshipContract->getId()) {
                        $contractRelated = true;
                    }
                }

                if (false === $contractRelated) {
                    $relationship->addContract($contract);
                }
                $this->entityManager->persist($relationship);

                $this->commandBus->handle(new UpdateRelationships($relationship));
                $this->commandBus->handle(new UpdateCategories($relationship));
            }

            $customer->setStatus(new CustomerAccountStatus(CustomerAccountStatus::ACTIVE));
        }

        return $contract;
    }

    private function updateApplicationRequest(array $data, bool $forceUpdate)
    {
        $applicationRequest = null;
        $contract = null;
        $dateSubmitted = null;

        if (isset($data['applicationRequest'])) {
            $applicationRequestData = $data['applicationRequest'];
            if (!isset($applicationRequestData['applicationRequestNumber'])) {
                throw new \Exception('Application Request Number is required');
            }
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $applicationRequestData['applicationRequestNumber']]);

            if (null !== $applicationRequest && null !== $applicationRequest->getCustomer()) {
                if (false === $forceUpdate && $applicationRequestData['status'] === $applicationRequest->getStatus()->getValue()) {
                    return $applicationRequest;
                }

                $isRepeat = false;

                if (null !== $applicationRequest->getDateSubmitted()) {
                    $dateSubmitted = clone $applicationRequest->getDateSubmitted();
                } elseif (null !== $applicationRequest->getDateModified()) {
                    $dateSubmitted = clone $applicationRequest->getDateModified();
                }

                $applicationRequest->setStatus(new ApplicationRequestStatus($applicationRequestData['status']));

                if (isset($data['contract'])) {
                    if (!isset($data['contract']['subtype'])) {
                        $data['contract']['subtype'] = $applicationRequest->getContractSubtype();
                    }

                    if (!isset($data['contract']['type']) && null !== $applicationRequest->getContractType()) {
                        $data['contract']['type'] = $applicationRequest->getContractType()->getValue();
                    }

                    if (!isset($data['contract']['ebsAccountNumber'])) {
                        $data['contract']['ebsAccountNumber'] = $applicationRequest->getEbsAccountNumber();
                    }

                    if (!isset($data['contract']['msslAccountNumber'])) {
                        $data['contract']['msslAccountNumber'] = $applicationRequest->getMsslAccountNumber();
                    }

                    $contract = $this->createContract($data['contract'], $applicationRequest->getCustomer(), $applicationRequest->isSelfApplication(), $applicationRequest->getContactPerson());

                    if ('iswitch' === $this->appProfile) {
                        $contract->setPaymentMode(new PaymentMode(PaymentMode::MANUAL));
                    } else {
                        $contract->setPaymentMode($applicationRequest->getPaymentMode());
                    }

                    $contract->setTariffRate($applicationRequest->getTariffRate());

                    // sourceable trait copy
                    $contract->setAcquiredFrom($applicationRequest->getAcquiredFrom());
                    $contract->setSource($applicationRequest->getSource());
                    $contract->setSourceUrl($applicationRequest->getSourceUrl());
                    // sourceable trait copy

                    $customizable = true;
                    if (null !== $applicationRequest->getTariffRate() && true !== $applicationRequest->getTariffRate()->isCustomizable()) {
                        $customizable = false;
                    }

                    if (null !== $applicationRequest->getCorporationDetails()) {
                        $contract->setCorporationDetails(clone $applicationRequest->getCorporationDetails());
                    }

                    if (null !== $applicationRequest->getPersonDetails()) {
                        $contract->setPersonDetails(clone $applicationRequest->getPersonDetails());
                    }

                    foreach ($applicationRequest->getBillSubscriptionTypes() as $billSubscriptionType) {
                        $contract->addBillSubscriptionType($billSubscriptionType);
                    }

                    foreach ($applicationRequest->getAddonServices() as $addonService) {
                        $contract->addAddonService($addonService);
                    }

                    $contract->setRecurringOption($applicationRequest->isRecurringOption());
                    $contract->setSelfReadMeterOption($applicationRequest->isSelfReadMeterOption());

                    $contract->setAverageConsumption($applicationRequest->getAverageConsumption());
                    $contract->setCustomized($customizable);

                    $customer = $contract->getCustomer();
                    $customer->setCustomerPortalEnabled(true);

                    $contactPerson = $contract->getContactPerson();
                    $contactPerson->setCustomerPortalEnabled(true);

                    if (null === $customer->getDefaultCreditsContract()) {
                        $customer->setDefaultCreditsContract($contract);
                    }
                    $this->entityManager->persist($customer);

                    // action
                    foreach ($contract->getActions() as $action) {
                        if ($action instanceof CreateContractAction) {
                            $isRepeat = true;
                            break;
                        }
                    }

                    if (false === $isRepeat) {
                        $contractAction = new CreateContractAction();
                        $contractAction->setActionStatus(new ActionStatus(ActionStatus::COMPLETED));
                        $contractAction->setObject($contract);
                        $contractAction->setResult($contract);
                        $contractAction->setInstrument($applicationRequest);
                        $this->entityManager->persist($contractAction);

                        $contract->addAction($contractAction);
                    }
                    $this->entityManager->persist($contract);
                }

                if (null !== $contract) {
                    $applicationRequest->setContract($contract);

                    if (false === $isRepeat && null !== $applicationRequest->getReferralCode()) {
                        $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['referralCode' => $applicationRequest->getReferralCode()]);

                        if (null !== $customer && $customer !== $contract->getCustomer()) {
                            $qb = $this->entityManager->getRepository(ReferralCreditsScheme::class)->createQueryBuilder('credit');
                            $expr = $qb->expr();
                            $startOfDay = new \DateTime('now', $this->timezone);
                            $startOfDay->setTime(0, 0, 0);

                            /**
                             * @var ReferralCreditsScheme[]
                             */
                            $referralSchemes = $qb->where($expr->lte('credit.validFrom', ':now'))
                                ->andWhere($expr->gte('credit.validThrough', ':now'))
                                ->andWhere($expr->isNull('credit.isBasedOn'))
                                ->setParameter('now', $startOfDay->setTimezone(new \DateTimeZone('UTC')))
                                ->getQuery()
                                ->getResult();

                            // @todo Not safe checking, codes need to be more foolproof
                            if (1 === \count($referralSchemes)) {
                                /**
                                 * @var ReferralCreditsScheme
                                 */
                                $referralScheme = clone $referralSchemes[0];
                                $referralScheme->setIsBasedOn($referralSchemes[0]);

                                $this->entityManager->persist($referralScheme);

                                $referralAmount = $referralScheme->getReferralAmount()->getValue();
                                $refereeAmount = $referralScheme->getRefereeAmount()->getValue();

                                $refereeDefaultCreditsContract = $customer->getDefaultCreditsContract();

                                if (null === $refereeDefaultCreditsContract) {
                                    /**
                                     * @var Contract[]
                                     */
                                    $contracts = $customer->getContracts();

                                    foreach ($contracts as $customerContract) {
                                        if (ContractStatus::ACTIVE === $customerContract->getStatus()->getValue()) {
                                            $refereeDefaultCreditsContract = $customerContract;
                                            break;
                                        }
                                    }
                                }

                                if ($referralAmount > 0 && null !== $refereeDefaultCreditsContract) {
                                    $earnCreditsAction = new EarnContractCreditsAction();
                                    $earnCreditsAction->setAmount($referralAmount);
                                    $earnCreditsAction->setEndTime(new \DateTime());
                                    $earnCreditsAction->setStartTime(new \DateTime());
                                    $earnCreditsAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

                                    $earnCreditsAction->setObject($refereeDefaultCreditsContract);
                                    $earnCreditsAction->setScheme($referralScheme);
                                    $earnCreditsAction->setInstrument($contract);

                                    $this->commandBus->handle(new UpdateTransaction($earnCreditsAction));
                                    $this->commandBus->handle(new UpdatePointCreditsActions($refereeDefaultCreditsContract, $earnCreditsAction));

                                    $this->entityManager->flush();

                                    /*if (null !== $referralScheme->getValidPeriod()->getValue() && null !== $referralScheme->getValidPeriod()->getUnitCode()) {
                                        $startDate = new \DateTime();
                                        if ('HUR' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                            $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' hour');
                                        } elseif ('DAY' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                            $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' day');
                                        } elseif ('MON' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                            $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' month');
                                        } elseif ('ANN' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                            $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' year');
                                        }

                                        $expireReferralContractCreditAction = new ExpireContractCreditsAction();
                                        $expireReferralContractCreditAction->setObject($refereeDefaultCreditsContract);
                                        $expireReferralContractCreditAction->setScheme($referralScheme);
                                        $expireReferralContractCreditAction->setStartTime($startDate);
                                        $expireReferralContractCreditAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));
                                        $expireReferralContractCreditAction->setAmountUsed('0');

                                        $this->commandBus->handle(new UpdateTransaction($expireReferralContractCreditAction));
                                        $this->commandBus->handle(new UpdatePointCreditsActions($refereeDefaultCreditsContract, $expireReferralContractCreditAction));

                                        $this->entityManager->persist($expireReferralContractCreditAction);
                                    }*/

                                    $this->entityManager->persist($earnCreditsAction);
                                    $this->entityManager->persist($refereeDefaultCreditsContract);

                                    $this->entityManager->flush();
                                }

                                if ($refereeAmount > 0) {
                                    $earnCreditsAction = new EarnContractCreditsAction();
                                    $earnCreditsAction->setAmount($refereeAmount);
                                    $earnCreditsAction->setEndTime(new \DateTime());
                                    $earnCreditsAction->setStartTime(new \DateTime());
                                    $earnCreditsAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

                                    $earnCreditsAction->setObject($contract);
                                    $earnCreditsAction->setScheme($referralScheme);
                                    $earnCreditsAction->setInstrument($contract);

                                    $this->commandBus->handle(new UpdateTransaction($earnCreditsAction));
                                    $this->commandBus->handle(new UpdatePointCreditsActions($contract, $earnCreditsAction));

                                    $this->entityManager->flush();

                                    /*if (null !== $referralScheme->getValidPeriod()->getValue() && null !== $referralScheme->getValidPeriod()->getUnitCode()) {
                                        $startDate = new \DateTime();
                                        if ('HUR' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                            $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' hour');
                                        } elseif ('DAY' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                            $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' day');
                                        } elseif ('MON' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                            $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' month');
                                        } elseif ('ANN' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                            $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' year');
                                        }

                                        $expireRefereeContractCreditAction = new ExpireContractCreditsAction();
                                        $expireRefereeContractCreditAction->setObject($contract);
                                        $expireRefereeContractCreditAction->setScheme($referralScheme);
                                        $expireRefereeContractCreditAction->setStartTime($startDate);
                                        $expireRefereeContractCreditAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));
                                        $expireRefereeContractCreditAction->setAmountUsed('0');

                                        $this->commandBus->handle(new UpdateTransaction($expireRefereeContractCreditAction));
                                        $this->commandBus->handle(new UpdatePointCreditsActions($contract, $expireRefereeContractCreditAction));

                                        $this->entityManager->persist($expireRefereeContractCreditAction);
                                    }*/

                                    $this->entityManager->persist($earnCreditsAction);
                                    $this->entityManager->persist($contract);

                                    $this->entityManager->flush();
                                }
                            }
                        }
                    }
                }

                $this->entityManager->persist($applicationRequest);
                $this->entityManager->flush();
            }
        }

        if (null !== $applicationRequest) {
            if (null !== $contract) {
                $contractForm = $this->getContractForm($contract, $dateSubmitted);

                if (null !== $contractForm) {
                    foreach ($contract->getFiles() as $file) {
                        if (DocumentType::CUSTOMER_CONTRACT_FORM === $file->getType()->getValue()) {
                            $contract->removeFile($file);
                        }
                    }
                    $contract->addFile($contractForm);

                    $this->entityManager->persist($contract);
                    $this->entityManager->flush();
                }
            }
            $this->queueEmailJob($applicationRequest);
        }

        return $applicationRequest;
    }

    private function queueEmailJob(ApplicationRequest $applicationRequest)
    {
        $jobType = null;

        switch ($applicationRequest->getStatus()->getValue()) {
            case ApplicationRequestStatus::CANCELLED:
                $jobType = JobType::APPLICATION_REQUEST_CANCELLED;
                break;
            case ApplicationRequestStatus::COMPLETED:
                $jobType = JobType::APPLICATION_REQUEST_COMPLETED;
                break;
            case ApplicationRequestStatus::REJECTED:
                $jobType = JobType::APPLICATION_REQUEST_REJECTED;
                break;
            default:
                return;
        }

        $job = new DisqueJob([
            'data' => [
                'id' => $applicationRequest->getId(),
                'applicationRequest' => $this->iriConverter->getIriFromItem($applicationRequest),
            ],
            'type' => $jobType,
        ]);

        $this->emailsQueue->push($job);
        $this->webServicesQueue->push($job);
    }

    private function rebuildXMLFile(array $data)
    {
        $xml = [];

        if (isset($data['applicationRequest']['meterType'])) {
            $xml['MeterOption'] = $data['applicationRequest']['meterType'];
        }

        if (isset($data['applicationRequest']['preferredStartDate'])) {
            $xml['ContractStartDate'] = $data['applicationRequest']['preferredStartDate'];
        }

        if (isset($data['applicationRequest']['preferredEndDate'])) {
            $xml['ContractLockInDate'] = $data['applicationRequest']['preferredEndDate'];
        }

        if (isset($data['applicationRequest']['Status'])) {
            $xml['Status'] = $data['applicationRequest']['Status'];
        }

        if (isset($data['applicationRequest']['customer']['personDetails']['contactPoints'][0]['emails'][0])) {
            $xml['CorrespondenceEmailAddress'] = $data['applicationRequest']['customer']['personDetails']['contactPoints'][0]['emails'][0];
        }

        if (isset($data['applicationRequest']['customer']['personDetails']['contactPoints'][0]['mobilePhoneNumbers'][0])) {
            $xml['CorrespondenceMobileNumber'] = $data['applicationRequest']['customer']['personDetails']['contactPoints'][0]['mobilePhoneNumbers'][0];
        }

        if (isset($data['applicationRequest']['customer']['personDetails']['identifiers'][0]['value'])) {
            $xml['CustomerNRIC'] = $data['applicationRequest']['customer']['personDetails']['identifiers'][0]['value'];
        }

        if (isset($data['applicationRequest']['addresses'][0]['streetAddress'])) {
            $xml['PremisesAddress'] = $data['applicationRequest']['addresses'][0]['streetAddress'];
        }

        if (isset($data['applicationRequest']['addresses'][0]['unitNumber'])) {
            $xml['PremisesUnitNumber'] = $data['applicationRequest']['addresses'][0]['unitNumber'];
        }

        if (isset($data['applicationRequest']['addresses'][0]['postalCode'])) {
            $xml['PremisesPostalCode'] = $data['applicationRequest']['addresses'][0]['postalCode'];
        }

        if (isset($data['applicationRequest']['addresses'][1]['streetAddress'])) {
            $xml['MailingAddress'] = $data['applicationRequest']['addresses'][1]['streetAddress'];
        }

        if (isset($data['applicationRequest']['addresses'][1]['unitNumber'])) {
            $xml['MailingUnitNumber'] = $data['applicationRequest']['addresses'][1]['unitNumber'];
        }

        if (isset($data['applicationRequest']['addresses'][1]['postalCode'])) {
            $xml['MailingPostalCode'] = $data['applicationRequest']['addresses'][1]['postalCode'];
        }

        if (isset($data['contract']['billingPeriodId'])) {
            $xml['BillingPeriodID'] = $data['contract']['billingPeriodId'];
        }

        if (isset($data['contract']['type'])) {
            $xml['ContractType'] = $data['contract']['type'];
        }

        if (isset($data['contract']['closureNoticePeriod'])) {
            $xml['ContractClosureNoticeDay'] = $data['contract']['closureNoticePeriod']['value'];
        }

        if (isset($data['contract']['contractNumber'])) {
            $xml['FRCContractNumber'] = $data['contract']['contractNumber'];
        }

        if (isset($data['contract']['ebsAccountNumber'])) {
            $xml['EBSAccountNumber'] = $data['contract']['ebsAccountNumber'];
        }

        if (isset($data['contract']['lockInDate'])) {
            $xml['ContractLockInDate'] = $data['contract']['lockInDate'];
        }

        if (isset($data['contract']['msslAccountNumber'])) {
            $xml['MSSLAccountNumber'] = $data['contract']['msslAccountNumber'];
        }

        if (isset($data['contract']['subtype'])) {
            $xml['ContractSubType'] = $data['contract']['subtype'];
        }

        return $xml;
    }
}
