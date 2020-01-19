<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\Reports\Address;
use App\Document\Reports\ContractReport;
use App\Document\Reports\CustomerDetails;
use App\Entity\Contract;
use App\Enum\AccountType;
use App\Enum\ContractStatus;
use App\Enum\PostalAddressType;
use App\WebService\Billing\Services\DataMapper;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateContractCacheTable extends Command
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
            ->setName('app:contract:update-cache-table')
            ->setDescription('Update Contracts cache table.')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Trigger update for which contract', null)
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
                    $contract = $this->entityManager->getRepository(Contract::class)->createQueryBuilder('c')
                        ->where('c.contractNumber IS NOT NULL')
                        ->andWhere('c.id = ?1')
                        ->setParameter(1, $id)->getQuery()->getOneOrNullResult();

                    if (null !== $contract) {
                        $this->processContractData($contract, $mode);
                        $this->documentManager->flush();
                    } else {
                        $this->logger->error("No Contract with id $id found or Contract doesn't have a Contract Number");
                    }
                } else {
                    $res = $io->ask("Are you sure you want to perform $mode for all Contracts? (yes/no)", '', function ($response) use ($io) {
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
                        $this->logger->alert('Starting insertion of whole Contracts table, this might take some time....');
                        $offset = 0;
                        $loop = 1;

                        do {
                            $addedCount = 0;
                            $qb = $this->entityManager->getRepository(Contract::class)->createQueryBuilder('c');
                            $contracts = $qb->where($qb->expr()->isNotNull('c.contractNumber'))
                                ->setFirstResult($offset)->setMaxResults($batchSize)->getQuery()->getResult();
                            $io->text('Starting Batch #'.$loop);
                            $progressBar = new ProgressBar($output, \count($contracts));
                            $offset += $batchSize;

                            foreach ($contracts as $contract) {
                                $addedCount += $this->processContractData($contract, $mode);

                                $progressBar->advance();
                            }

                            if ($addedCount > 0) {
                                $this->documentManager->flush();
                                $this->documentManager->clear();
                                $this->entityManager->clear();
                            }
                            $io->newLine();
                            $io->text('Total added   :'.$addedCount);
                            $io->text('Total skipped :'.(\count($contracts) - $addedCount));
                            $io->text('Ending Batch #'.$loop);
                            $io->newLine();
                            ++$loop;
                        } while (\count($contracts) > 0);

                        $this->logger->alert('Finished processing all Contracts');
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

    private function processContractData(Contract $contract, $mode = 0)
    {
        try {
            $contractNumber = $contract->getContractNumber();
            $existingData = $this->documentManager->getRepository(ContractReport::class)->findOneBy(['contractNumber' => $contractNumber]);

            if (empty($mode) || \in_array($mode, ['insert', 'update', 'force update'], true)) {
                if (
                    'force update' !== $mode &&
                    null !== $existingData &&
                    null !== $existingData->getDateModified() &&
                    null !== $contract->getDateModified() &&
                    $existingData->getDateModified()->getTimestamp() === $contract->getDateModified()->getTimestamp()
                ) {
                    return 0;
                }
                $data = $this->getCacheTableData($contract, $existingData);
                $this->documentManager->persist($data);
            } elseif ('delete' === $mode) {
                if (null === $existingData) {
                    throw new \Exception("Existing data with Application Request Number $contractNumber not found");
                }
                $this->documentManager->remove($existingData);
            }

            return 1;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    private function getCacheTableData(Contract $contract, ContractReport $existingData = null): ContractReport
    {
        //region Customer Details
        $customerDetails = new CustomerDetails();

        $customerDetails->setCustomerId($contract->getCustomer()->getAccountNumber());
        if (null !== $contract->getCustomerType()) {
            if (AccountType::INDIVIDUAL === $contract->getCustomerType()->getValue()) {
                $customerDetails->setFullName(null !== $contract->getCustomer()->getPersonDetails()
                    ? $contract->getCustomer()->getPersonDetails()->getName() : null);
            } elseif (AccountType::CORPORATE === $contract->getCustomerType()->getValue()) {
                $customerDetails->setFullName(null !== $contract->getCustomer()->getCorporationDetails()
                    ? $contract->getCustomer()->getCorporationDetails()->getName() : null);
            }
        }
        $customerDetails->setStatus($contract->getCustomer()->getStatus()->getValue());
        //endregion

        //region Addresses
        $premiseAddress = new Address();
        $mailingAddress = new Address();
        foreach ($contract->getAddresses() as $contractAddress) {
            $address = $contractAddress->getAddress();
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
            }
        }
        //endregion

        $tableData = $existingData ?? new ContractReport();
        $tableData->setCustomerDetails($customerDetails);
        $tableData->setContractNumber($contract->getContractNumber());
        $tableData->setStatus((null !== $contract->getEndDate()
            && (new \DateTime('now', new \DateTimeZone('UTC')) > $contract->getEndDate()))
            ? ContractStatus::INACTIVE : $contract->getStatus()->getValue()
        );
        $tableData->setType($contract->getType()->getValue());
        $tableData->setStartDate($contract->getStartDate());
        $tableData->setEndDate($contract->getEndDate());
        $tableData->setLockInDate($contract->getLockInDate());
        $tableData->setMeterType(null !== $contract->getMeterType() ? $contract->getMeterType()->getValue() : null);
        $tableData->setMsslNumber($contract->getMsslAccountNumber());
        $tableData->setEbsNumber($contract->getEbsAccountNumber());
        $tableData->setPromotionCode(null !== $contract->getTariffRate() ? $contract->getTariffRate()->getTariffRateNumber() : null);
        $tableData->setPromotionName(null !== $contract->getTariffRate() ? $contract->getTariffRate()->getName() : null);
        $tableData->setCategory(null !== $contract->getCustomerType() ? $contract->getCustomerType()->getValue() : null);
        $tableData->setPremiseAddressDetails($premiseAddress);
        $tableData->setMailingAddressDetails($mailingAddress);
        $tableData->setPaymentMethod(null !== $contract->getPaymentMode() ? $contract->getPaymentMode()->getValue() : null);
        $tableData->setDateCreated($contract->getDateCreated());
        $tableData->setDateModified($contract->getDateModified());

        return $tableData;
    }
}
