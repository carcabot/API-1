<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Command\CustomerAccount\UpdateRelationships;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountRelationship;
use App\Enum\AccountCategory;
use App\Enum\AccountType;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ContractStatus;
use App\Enum\CustomerRelationshipType;
use App\Enum\Source;
use Doctrine\ORM\EntityManagerInterface;
use iter;
use League\Tactician\CommandBus;
use League\Uri\Components\Query as UriQuery;
use League\Uri\Schemes\Http as HttpUri;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PatchData extends Command
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
        parent::__construct();

        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:patch:data')
            ->setDescription('Patch missing data.')
            ->addOption('patchNumber', null, InputOption::VALUE_REQUIRED, 'Which patch fix?', null)
            ->setHelp(<<<'EOF'
The %command.name% command patches missing data into the database.
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
        $patchNumber = $input->getOption('patchNumber');

        switch ($patchNumber) {
            case 'CONTRACT180912':
                $io->text('Running patch: '.$patchNumber);
                $contracts = $this->entityManager->getRepository(Contract::class)->findAll();

                $progressBar = new ProgressBar($output, \count($contracts));

                foreach ($contracts as $contract) {
                    if (null !== $contract->getCustomerType() && AccountType::CORPORATE === $contract->getCustomerType()->getValue() && null !== $contract->getCustomer()->getCorporationDetails()) {
                        $contract->setCorporationDetails(clone $contract->getCustomer()->getCorporationDetails());

                        if (null !== $contract->getContactPerson()->getPersonDetails()) {
                            $contract->setPersonDetails(clone $contract->getContactPerson()->getPersonDetails());
                        }

                        $relationship = $this->entityManager->getRepository(CustomerAccountRelationship::class)->findOneBy([
                            'from' => $contract->getContactPerson()->getId(),
                            'to' => $contract->getCustomer()->getId(),
                            'type' => new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON),
                        ]);

                        if (null !== $relationship) {
                            $this->commandBus->handle(new UpdateRelationships($relationship));
                        }
                    } elseif (null !== $contract->getCustomerType() && AccountType::INDIVIDUAL === $contract->getCustomerType()->getValue() && null !== $contract->getCustomer()->getPersonDetails()) {
                        $contract->setPersonDetails(clone $contract->getCustomer()->getPersonDetails());
                    }

                    $this->entityManager->persist($contract);
                    $this->entityManager->flush();
                    $progressBar->advance();
                }

                $progressBar->finish();
                $io->text('Done patch: '.$patchNumber);
                break;
            case 'APPLREQT180912':
                $io->text('Running patch: '.$patchNumber);
                $applicationRequests = $this->entityManager->getRepository(ApplicationRequest::class)->findAll();

                $progressBar = new ProgressBar($output, \count($applicationRequests));

                foreach ($applicationRequests as $applicationRequest) {
                    if (null !== $applicationRequest->getCustomer() && null !== $applicationRequest->getCustomerType()) {
                        if (AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue() && null !== $applicationRequest->getCustomer()->getCorporationDetails()) {
                            $applicationRequest->setCorporationDetails(clone $applicationRequest->getCustomer()->getCorporationDetails());

                            if (null !== $applicationRequest->getContactPerson() && null !== $applicationRequest->getContactPerson()->getPersonDetails()) {
                                $applicationRequest->setPersonDetails(clone $applicationRequest->getContactPerson()->getPersonDetails());

                                $relationship = $this->entityManager->getRepository(CustomerAccountRelationship::class)->findOneBy([
                                    'from' => $applicationRequest->getContactPerson()->getId(),
                                    'to' => $applicationRequest->getCustomer()->getId(),
                                    'type' => new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON),
                                ]);

                                if (null === $relationship) {
                                    $relationship = new CustomerAccountRelationship();
                                    $relationship->setFrom($applicationRequest->getContactPerson());
                                    $relationship->setTo($applicationRequest->getCustomer());
                                    $relationship->setType(new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON));
                                    $relationship->setValidFrom($applicationRequest->getDateCreated());
                                    $this->entityManager->persist($relationship);
                                }
                                $this->commandBus->handle(new UpdateRelationships($relationship));
                            }
                        } elseif (AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue() && null !== $applicationRequest->getCustomer()->getPersonDetails()) {
                            $applicationRequest->setPersonDetails(clone $applicationRequest->getCustomer()->getPersonDetails());
                        }

                        $this->entityManager->persist($applicationRequest);
                        $this->entityManager->flush();
                        $progressBar->advance();
                    }
                }

                $progressBar->finish();
                $io->text('Done patch: '.$patchNumber);
                break;
            case 'APPLREQT190507':
                $io->text('Running patch: '.$patchNumber);

                $applicationRequests = $this->entityManager->createQuery(\sprintf('SELECT app FROM %s app JOIN app.creator creator WHERE app.source = \'%s\' and app.sourceUrl IS NOT NULL and creator.username = \'%s\' and app.dateCreated > \'%s\'', ApplicationRequest::class, Source::PARTNERSHIP_PORTAL, 'WebAPI', '2019-04-17'))->execute();

                $progressBar = new ProgressBar($output, \count($applicationRequests));

                foreach ($applicationRequests as $applicationRequest) {
                    if (null !== $applicationRequest->getSourceUrl()) {
                        $sourceUri = HttpUri::createFromString($applicationRequest->getSourceUrl());
                        $query = new UriQuery($sourceUri->getQuery());
                        $creatorCode = null;

                        if ($query->hasPair('referrer')) {
                            $creatorCode = $query->getPair('referrer');
                        } elseif ($query->hasPair('partner')) {
                            $creatorCode = $query->getPair('partner');
                        }

                        if (null !== $creatorCode) {
                            $creator = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $creatorCode]);

                            if (null !== $creator && null !== $creator->getUser()) {
                                if (ApplicationRequestStatus::IN_PROGRESS === $applicationRequest->getStatus()->getValue()) {
                                    $dql = \sprintf('UPDATE %s app SET app.creator = %d, app.agent = %d WHERE app.id = %d', ApplicationRequest::class, $creator->getUser()->getId(), $creator->getUser()->getId(), $applicationRequest->getId());
                                } else {
                                    $dql = \sprintf('UPDATE %s app SET app.creator = %d WHERE app.id = %d', ApplicationRequest::class, $creator->getUser()->getId(), $applicationRequest->getId());
                                }

                                $this->entityManager->createQuery($dql)->execute();
                            }
                        }

                        $progressBar->advance();
                    }
                }

                $progressBar->finish();
                $io->text('Done patch: '.$patchNumber);
                break;
            case 'CUSTACCT190529':
                $io->text('Running patch: '.$patchNumber);

                $customerAccounts = $this->entityManager->getRepository(CustomerAccount::class)->findAll();

                $progressBar = new ProgressBar($output, \count($customerAccounts));

                $maxContractEndDate = new \DateTime();
                $maxContractEndDate->sub(new \DateInterval('P90D'));
                $count = 0;

                foreach ($customerAccounts as $customerAccount) {
                    $enablePortal = null;
                    $contracts = [];

                    if (\in_array(AccountCategory::CONTACT_PERSON, $customerAccount->getCategories(), true)) {
                        $relationships = $customerAccount->getRelationships();

                        foreach ($relationships as $relationship) {
                            $contracts = \array_merge($contracts, $relationship->getContracts());
                        }
                    }

                    if (\in_array(AccountCategory::CUSTOMER, $customerAccount->getCategories(), true)) {
                        $ownContracts = iter\toArray(iter\filter(function ($contract) {
                            return null !== $contract->getContractNumber();
                        }, $customerAccount->getContracts()));

                        $contracts = \array_merge($contracts, $ownContracts);
                    }

                    if (0 === \count($contracts)) {
                        $customerAccount->setCustomerPortalEnabled(false);

                        continue;
                    }

                    $lastEndDate = null;

                    foreach ($contracts as $contract) {
                        if (null === $contract->getEndDate()) {
                            $enablePortal = true;

                            break;
                        }

                        if (ContractStatus::ACTIVE === $contract->getStatus()->getValue() && $contract->getEndDate() > $maxContractEndDate) {
                            $enablePortal = true;

                            break;
                        }

                        if (null !== $lastEndDate) {
                            if ($contract->getEndDate() > $lastEndDate) {
                                $lastEndDate = $contract->getEndDate();
                            }
                        } else {
                            $lastEndDate = $contract->getEndDate();
                        }
                    }

                    if (null !== $lastEndDate && $lastEndDate >= $maxContractEndDate) {
                        $enablePortal = true;
                    }

                    if (true === $customerAccount->getCustomerPortalEnabled()) {
                        if (true !== $enablePortal) {
                            $dql = \sprintf('UPDATE %s customer SET customer.customerPortalEnabled = false WHERE customer.id = %d', CustomerAccount::class, $customerAccount->getId());
                            $this->entityManager->createQuery($dql)->execute();
                            ++$count;
                        }
                    } elseif (true === $enablePortal) {
                        $dql = \sprintf('UPDATE %s customer SET customer.customerPortalEnabled = true WHERE customer.id = %d', CustomerAccount::class, $customerAccount->getId());
                        $this->entityManager->createQuery($dql)->execute();
                        ++$count;
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                $io->text('Done patch: '.$patchNumber);
                $io->text('Total updates: '.$count);
                break;
            default:
                $io->text('Unknown patch number: '.$patchNumber);
                break;
        }

        return 0;
    }
}
