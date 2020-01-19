<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\Reports\Address;
use App\Document\Reports\ApplicationRequestReport;
use App\Document\Reports\CustomerDetails;
use App\Entity\ApplicationRequest;
use App\Enum\AccountType;
use App\Enum\ApplicationRequestType;
use App\Enum\BillSubscriptionType;
use App\Enum\ContractType;
use App\Enum\IdentificationName;
use App\Enum\PostalAddressType;
use App\Enum\Source;
use App\Service\ReportHelper;
use App\WebService\Billing\Services\DataMapper;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use League\Uri\Components\Query as UriQuery;
use League\Uri\Schemes\Http as HttpUri;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateApplicationRequestCacheTable extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PhoneNumberUtil
     */
    private $phoneNumberUtil;

    /**
     * @var DataMapper
     */
    private $dataMapper;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DocumentManager        $documentManager
     * @param LoggerInterface        $logger
     * @param DataMapper             $dataMapper
     * @param PhoneNumberUtil        $phoneNumberUtil
     */
    public function __construct(EntityManagerInterface $entityManager, DocumentManager $documentManager, LoggerInterface $logger, DataMapper $dataMapper, PhoneNumberUtil $phoneNumberUtil)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->dataMapper = $dataMapper;
        $this->phoneNumberUtil = $phoneNumberUtil;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:application-request:update-cache-table')
            ->setDescription('Update application requests cache table.')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Trigger update for which application request', null)
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'what type of CRUD operation to perform? 0 for insert, 1 for update and 2 for delete', null)
            ->setHelp(<<<'EOF'
The %command.name% command update customer contact information.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getOption('id');
        $mode = $input->getOption('mode');

        try {
            if (!empty($this->documentManager->getConnection()->getServer()) && $this->documentManager->getConnection()->connect()) {
                if (empty($mode)) {
                    $mode = $io->choice('What operation do you want to perform?', ['insert', 'update', 'delete', 'force update']);
                }

                if (!empty($id)) {
                    $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->find($id);

                    if (null !== $applicationRequest) {
                        $this->processApplicationrequestData($applicationRequest, $mode);
                        $this->documentManager->flush();
                    } else {
                        $this->logger->error("No Application request with id $id found");
                    }
                } else {
                    $res = $io->ask("Are you sure you want to perform $mode for all Application Requests? (yes/no)", '', function ($response) use ($io) {
                        $finalResp = \strtolower($response);

                        if ('y' === $finalResp) {
                            while ('y' === $finalResp) {
                                $finalResp = $io->ask('Please, type yes');
                            }
                        }

                        return $finalResp;
                    });
                    if ('yes' === \strtolower($res)) {
                        $batchSize = 1000;
                        $this->logger->alert('Starting insertion of whole Application Requests table, this might take some time....');
                        $offset = 0;
                        $loop = 1;

                        do {
                            $addedCount = 0;
                            $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('app');
                            $applicationRequests = $qb->setFirstResult($offset)->setMaxResults($batchSize)->getQuery()->getResult();
                            $io->text('Starting Batch #'.$loop);
                            $progressBar = new ProgressBar($output, \count($applicationRequests));
                            $offset += $batchSize;

                            foreach ($applicationRequests as $applicationRequest) {
                                $addedCount += $this->processApplicationrequestData($applicationRequest, $mode);

                                $progressBar->advance();
                            }

                            if ($addedCount > 0) {
                                $this->documentManager->flush();
                                $this->documentManager->clear();
                                $this->entityManager->clear();
                            }
                            $io->newLine();
                            $io->text('Total added   :'.$addedCount);
                            $io->text('Total skipped :'.(\count($applicationRequests) - $addedCount));
                            $io->text('Ending Batch #'.$loop);
                            $io->newLine();
                            ++$loop;
                        } while (\count($applicationRequests) > 0);

                        $this->logger->alert('Finished processing all Application Requests');
                    } else {
                        $io->text("You entered $res exiting... bye.");
                    }
                }
            } else {
                $io->text('No cache db found. Nothing to do.');
            }

            return 0;
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());

            return 255;
        }
    }

    private function processApplicationrequestData(ApplicationRequest $applicationRequest, $mode = 0)
    {
        try {
            $applicationRequestNumber = $applicationRequest->getApplicationRequestNumber();
            $existingData = $this->documentManager->getRepository(ApplicationRequestReport::class)->findOneBy(['applicationRequestId' => $applicationRequestNumber]);

            if (empty($mode) || \in_array($mode, ['insert', 'update', 'force update'], true)) {
                if (
                    'force update' !== $mode &&
                    null !== $existingData &&
                    null !== $existingData->getDateModified() &&
                    null !== $applicationRequest->getDateModified() &&
                    $existingData->getDateModified()->getTimestamp() === $applicationRequest->getDateModified()->getTimestamp()
                ) {
                    return 0;
                }
                $data = $this->getCacheTableData($applicationRequest, $existingData);
                $this->documentManager->persist($data);
            } elseif ('delete' === $mode) {
                if (null === $existingData) {
                    throw new \Exception("Existing data with Application Request Number $applicationRequestNumber not found");
                }
                $this->documentManager->remove($existingData);
            }

            return 1;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    private function getCacheTableData(ApplicationRequest $applicationRequest, ApplicationRequestReport $existingData = null): ApplicationRequestReport
    {
        $corporateIdentification = $agency = $salesRep = $partnerCode = $channel = $renewalStartDate = null;
        $sourceUrl = $applicationRequest->getSourceUrl();

        if (null !== $sourceUrl) {
            $sourceUri = HttpUri::createFromString($sourceUrl);
            $query = new UriQuery($sourceUri->getFragment());

            if ($query->hasPair('/?channel')) {
                $channel = \ucfirst($query->getPair('/?channel'));
            }

            $channel = 'Portal';
        }
        $status = \str_replace('_', ' ', \ucwords(\strtolower($applicationRequest->getStatus()->getValue()), '_'));
        $status = \str_replace('Partner ', '', $status);
        $contractType = null !== $applicationRequest->getContractType() && ContractType::RESIDENTIAL === $applicationRequest->getContractType()->getValue()
            ? $this->dataMapper->mapContractSubtype($applicationRequest->getContractSubtype()) : null;
        $industry = null !== $applicationRequest->getContractType() && ContractType::COMMERCIAL === $applicationRequest->getContractType()->getValue()
            ? $this->dataMapper->mapContractSubtype($applicationRequest->getContractSubtype()) : null;
        if (null !== $applicationRequest->getCustomerType()
            && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue()) {
            if (null !== $applicationRequest->getCorporationDetails()) {
                $corporateIdentification = ReportHelper::mapIdentifiers($applicationRequest->getCorporationDetails()->getIdentifiers(), IdentificationName::UNIQUE_ENTITY_NUMBER);
            }
        }
        if ('PARTNERSHIP_PORTAL' === $applicationRequest->getSource() && null !== $applicationRequest->getAcquiredFrom()) {
            $partnerCode = $applicationRequest->getAcquiredFrom()->getAccountNumber();
            if (AccountType::INDIVIDUAL === $applicationRequest->getAcquiredFrom()->getType()->getValue()) {
                $agency = null !== $applicationRequest->getAcquiredFrom()->getPersonDetails()
                    ? $applicationRequest->getAcquiredFrom()->getPersonDetails()->getName()
                    : null;
            } elseif (AccountType::CORPORATE === $applicationRequest->getAcquiredFrom()->getType()->getValue()) {
                $agency = null !== $applicationRequest->getAcquiredFrom()->getCorporationDetails()
                    ? $applicationRequest->getAcquiredFrom()->getCorporationDetails()->getName()
                    : null;
            }
        }

        $salesRep = $applicationRequest->getSalesRepName();
        if (Source::PARTNERSHIP_PORTAL === $applicationRequest->getSource() && empty($applicationRequest->getSalesRepName())) {
            $salesRep = null !== $applicationRequest->getCreator() ? $applicationRequest->getCreator()->getCustomerName() : null;
        }

        if (null !== $applicationRequest->getContract() && ApplicationRequestType::CONTRACT_RENEWAL === $applicationRequest->getType()->getValue()) {
            $renewalStartDate = $applicationRequest->getContract()->getStartDate();
        }

        //region Customer Details
        $customerDetails = new CustomerDetails();
        if (null !== $applicationRequest->getCustomerType() && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue()) {
            if (null !== $applicationRequest->getPersonDetails()) {
                $customerDetails->setIdentificationNumber(ReportHelper::mapIdentifiers($applicationRequest->getPersonDetails()->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
            }
        }
        $customerDetails->setCustomerId(null !== $applicationRequest->getCustomer()
            ? $applicationRequest->getCustomer()->getAccountNumber() : null);
        $customerDetails->setSalutation((null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
            && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue())
            ? $applicationRequest->getPersonDetails()->getHonorificPrefix()
            : null);
        $customerDetails->setFirstName((null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
            && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue())
            ? $applicationRequest->getPersonDetails()->getGivenName()
            : null);
        $customerDetails->setMiddleName((null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
            && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue())
            ? $applicationRequest->getPersonDetails()->getAdditionalName()
            : null);
        $customerDetails->setLastName((null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
            && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue())
            ? $applicationRequest->getPersonDetails()->getFamilyName()
            : null);
        $customerDetails->setFullName((null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
            && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue())
            ? $applicationRequest->getPersonDetails()->getName()
            : null);
        $customerDetails->setCategory(null !== $applicationRequest->getCustomerType()
            ? $applicationRequest->getCustomerType()->getValue() : null);
        if (null !== $applicationRequest->getPersonDetails()) {
            $customerDetails->setEmail(ReportHelper::mapContactPoints($applicationRequest->getPersonDetails()->getContactPoints(), 'emails'));
            $mobileNumber = ReportHelper::mapContactPoints($applicationRequest->getPersonDetails()->getContactPoints(), 'mobilePhoneNumbers');
            $telephoneNumber = ReportHelper::mapContactPoints($applicationRequest->getPersonDetails()->getContactPoints(), 'telephoneNumbers');
            if (!empty($mobileNumber)) {
                $customerDetails->setMobileNumber($this->phoneNumberUtil->format($mobileNumber, PhoneNumberFormat::E164));
            }
            if (!empty($telephoneNumber)) {
                $customerDetails->setPhoneNumber($this->phoneNumberUtil->format($telephoneNumber, PhoneNumberFormat::E164));
            }
        }
        //endregion

        //region Contact Person Details
        $contactPersonDetails = new CustomerDetails();
        $contactPersonDetails->setCustomerId((null !== $applicationRequest->getContactPerson() && null !== $applicationRequest->getCustomerType()
            && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue())
            ? $applicationRequest->getContactPerson()->getAccountNumber()
            : null);
        if (null !== $applicationRequest->getContactPerson() && null !== $applicationRequest->getCustomerType()
            && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue()) {
            if (null !== $applicationRequest->getContactPerson()->getPersonDetails()) {
                $contactPersonDetails->setIdentificationNumber(ReportHelper::mapIdentifiers($applicationRequest->getContactPerson()->getPersonDetails()->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
                $contactPersonDetails->setFirstName($applicationRequest->getContactPerson()->getPersonDetails()->getGivenName());
                $contactPersonDetails->setFullName($applicationRequest->getContactPerson()->getPersonDetails()->getName());
            }
        }
        $contactPersonDetails->setSalutation((null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
            && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue())
            ? $applicationRequest->getPersonDetails()->getHonorificPrefix()
            : null);
        //endregion

        //region Addresses
        $premiseAddress = new Address();
        $mailingAddress = new Address();
        $refundAddress = new Address();
        foreach ($applicationRequest->getAddresses() as $address) {
            if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                $premiseAddress->setPostalCode($address->getPostalCode());
                $premiseAddress->setUnitNumber($address->getUnitNumber());
                $premiseAddress->setBuildingNumber($address->getHouseNumber());
                $premiseAddress->setFloor($address->getFloor());
                $premiseAddress->setBuildingName($address->getBuildingName());
                $premiseAddress->setStreet($address->getStreetAddress());
                $premiseAddress->setCity($address->getAddressLocality());
                $premiseAddress->setCountry($address->getAddressCountry());
            } elseif (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                $mailingAddress->setPostalCode($address->getPostalCode());
                $mailingAddress->setUnitNumber($address->getUnitNumber());
                $mailingAddress->setFloor($address->getFloor());
                $mailingAddress->setBuildingNumber($address->getHouseNumber());
                $mailingAddress->setBuildingName($address->getBuildingName());
                $mailingAddress->setStreet($address->getStreetAddress());
                $mailingAddress->setCity($address->getAddressLocality());
                $mailingAddress->setCountry($address->getAddressCountry());
            } elseif (PostalAddressType::REFUND_ADDRESS === $address->getType()->getValue()) {
                $refundAddress->setPostalCode($address->getPostalCode());
                $refundAddress->setUnitNumber($address->getUnitNumber());
                $refundAddress->setFloor($address->getFloor());
                $refundAddress->setBuildingNumber($address->getHouseNumber());
                $refundAddress->setBuildingName($address->getBuildingName());
                $refundAddress->setStreet($address->getStreetAddress());
                $refundAddress->setCity($address->getAddressLocality());
                $refundAddress->setCountry($address->getAddressCountry());
            }
        }
        //endregion

        $averageConsumption = null !== $applicationRequest->getAverageConsumption()->getValue() ? (float) $applicationRequest->getAverageConsumption()->getValue().' '.ReportHelper::mapUnitCodes($applicationRequest->getAverageConsumption()->getUnitCode()) : null;

        $tableData = $existingData ?? new ApplicationRequestReport();
        $tableData->setApplicationRequestId($applicationRequest->getApplicationRequestNumber());
        $tableData->setAverageConsumption($averageConsumption);
        $tableData->setType(\str_replace('_', ' ', \ucwords(\strtolower($applicationRequest->getType()->getValue()), '_')));
        $tableData->setCustomerDetails($customerDetails);
        $tableData->setContactPersonDetails($contactPersonDetails);
        $tableData->setContract(null !== $applicationRequest->getContract()
            ? $applicationRequest->getContract()->getContractNumber() : null);
        $tableData->setContractType(null !== $applicationRequest->getContractType() ? $applicationRequest->getContractType()->getValue() : null);
        $tableData->setPremiseType($contractType);
        $tableData->setIndustry($industry);
        $tableData->setTariffRateCode(null !== $applicationRequest->getTariffRate() ? $applicationRequest->getTariffRate()->getTariffRateNumber() : null);
        $tableData->setTariffRate(null !== $applicationRequest->getTariffRate() ? $applicationRequest->getTariffRate()->getName() : null);
        $tableData->setReferralCode($applicationRequest->getReferralCode());
        $tableData->setSpAccountNumber(null === $applicationRequest->getEbsAccountNumber() ? $applicationRequest->getMsslAccountNumber() : $applicationRequest->getEbsAccountNumber());
        $tableData->setPreferredStartDate($applicationRequest->getPreferredStartDate());
        $tableData->setPreferredEndDate($applicationRequest->getPreferredEndDate());
        $tableData->setSelfReadOption(true === $applicationRequest->isSelfReadMeterOption() ? 'YES' : 'NO');
        $tableData->setGiroApplication(true !== $applicationRequest->isGIROOption() ? 'YES' : 'NO');
        $tableData->setSource(null !== $applicationRequest->getSource()
            ? \str_replace('_', ' ', \ucwords(\strtolower($applicationRequest->getSource()), '_'))
            : null);
        $tableData->setDeposit(null !== $applicationRequest->getDepositRefundType() ? $applicationRequest->getDepositRefundType()->getValue() : null);
        $tableData->setCorporateIdentificationNumber($corporateIdentification);
        $tableData->setCompanyName((null !== $applicationRequest->getCorporationDetails() && null !== $applicationRequest->getCustomerType()
            && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue())
            ? $applicationRequest->getCorporationDetails()->getName() : null);
        $tableData->setPremiseAddressDetails($premiseAddress);
        $tableData->setMailingAddressDetails($mailingAddress);
        $tableData->setRefundAddressDetails($refundAddress);
        $tableData->setRemarks($applicationRequest->getRemark());
        $tableData->setStatus($status);
        $tableData->setTerminationReason($applicationRequest->getTerminationReason());
        $tableData->setReferralSource(null !== $applicationRequest->getReferralSource()
            ? \str_replace('_', ' ', \ucwords(\strtolower($applicationRequest->getReferralSource()->getValue()), '_'))
            : null);
        $tableData->setIndicate($applicationRequest->getSpecifiedReferralSource());
        $tableData->setAgency($agency);
        $tableData->setSalesRep($salesRep);
        $tableData->setPartnerCode($partnerCode);
        $tableData->setChannel($channel);
        $tableData->setLocationCode($applicationRequest->getLocation());
        $tableData->setPaymentMode(null !== $applicationRequest->getPaymentMode()
            ? $applicationRequest->getPaymentMode()->getValue() : null);
        $tableData->setRenewalStartDate($renewalStartDate);
        $tableData->setLockInDate(null !== $applicationRequest->getContract()
            ? $applicationRequest->getContract()->getLockInDate() : null);
        $tableData->setDateCreated($applicationRequest->getDateCreated());
        $tableData->setDateModified($applicationRequest->getDateModified());
        $tableData->setEBilling(\in_array(BillSubscriptionType::ELECTRONIC, $applicationRequest->getBillSubscriptionTypes(), true) ? 'YES' : 'NO');
        $tableData->setSelfApplication(true === $applicationRequest->isSelfApplication() ? 'YES' : 'NO');
        $tableData->setMeterOption(null !== $applicationRequest->getMeterType() ? $applicationRequest->getMeterType()->getValue() : null);
        $tableData->setPromotionCode(null !== $applicationRequest->getPromotion() ? $applicationRequest->getPromotion()->getPromotionNumber() : null);

        return $tableData;
    }
}
