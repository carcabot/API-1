<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\Person;
use App\Enum\AccountType;
use App\Enum\IdentificationName;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PatchCustomerAccountType extends Command
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
        parent::__construct();

        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:patch:customer-account-type')
            ->setDescription('Patch a customer\'s account type')
            ->addOption('account-numbers', null, InputOption::VALUE_REQUIRED, 'For which account(s)?', null)
            ->addOption('account-type', null, InputOption::VALUE_OPTIONAL, 'New account type', null)
            ->setHelp(<<<'EOF'
The %command.name% command patches the customer account type.
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

        $accountNumbers = $input->getOption('account-numbers');
        $accountType = $input->getOption('account-type');
        $applicationRequests = [];
        $applicationRequestsOutputTable = [];
        $contracts = [];
        $contractsOutputTable = [];

        $accountNumbers = \array_unique(\explode(',', (string) $accountNumbers));
        foreach ($accountNumbers as $accountNumber) {
            $this->entityManager->clear();
            $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => (string) $accountNumber]);

            if (null === $customer) {
                $customer = $this->entityManager->getRepository(CustomerAccount::class)->find((int) $accountNumber);
            }

            if (null === $customer) {
                $io->error('Unable to find customer with ID/Account Number of '.$accountNumber);

                $applicationRequestsOutputTable[] = [$accountNumber, '', 'Customer not found!'];
                $contractsOutputTable[] = [$accountNumber, '', 'Customer not found!'];

                continue;
            }

            $switchTypes = [
                AccountType::CORPORATE => new AccountType(AccountType::INDIVIDUAL),
                AccountType::INDIVIDUAL => new AccountType(AccountType::CORPORATE),
            ];

            if (null !== $accountType) {
                $accountType = new AccountType($accountType);
            } else {
                $accountType = $switchTypes[$customer->getType()->getValue()];
            }

            if ($accountType->getValue() === $customer->getType()->getValue()) {
                $io->error('Customer is already '.$accountType->getValue());

                $applicationRequestsOutputTable[] = [$accountNumber, '', 'Customer is already '.$accountType->getValue()];
                $contractsOutputTable[] = [$accountNumber, '', 'Customer is already '.$accountType->getValue()];

                continue;
            }

            if (AccountType::CORPORATE === $customer->getType()->getValue() && null !== $customer->getCorporationDetails()) {
                $applicationRequests[$customer->getAccountNumber()] = [];
                foreach ($customer->getApplicationRequests() as $applicationRequest) {
                    if (null !== $applicationRequest->getCorporationDetails()) {
                        if (null !== $applicationRequest->getPersonDetails()) {
                            $this->entityManager->remove($applicationRequest->getPersonDetails());
                            $this->entityManager->flush();
                        }
                        $person = $this->createPersonFromCorporation($applicationRequest->getCorporationDetails());

                        $oldContactPerson = '';
                        if (null !== $applicationRequest->getContactPerson()) {
                            $oldContactPerson = $applicationRequest->getContactPerson()->getId().' - '.$applicationRequest->getContactPerson()->getAccountNumber();
                        }

                        $applicationRequest->setContactPerson($applicationRequest->getCustomer());
                        $applicationRequest->setCustomerType($accountType);
                        $applicationRequest->setPersonDetails($person);

                        $this->entityManager->remove($applicationRequest->getCorporationDetails());
                        $this->entityManager->flush();

                        $applicationRequests[$customer->getAccountNumber()][] = $applicationRequest->getId().' - '.$applicationRequest->getApplicationRequestNumber().' CP '.$oldContactPerson;
                    }
                }

                $contracts[$customer->getAccountNumber()] = [];
                foreach ($customer->getContracts() as $contract) {
                    if (null !== $contract->getCorporationDetails()) {
                        if (null !== $contract->getPersonDetails()) {
                            $this->entityManager->remove($contract->getPersonDetails());
                        }
                        $person = $this->createPersonFromCorporation($contract->getCorporationDetails());

                        $oldContactPerson = $contract->getContactPerson()->getId().' - '.$contract->getContactPerson()->getAccountNumber();
                        $contract->setContactPerson($contract->getCustomer());
                        $contract->setCustomerType($accountType);
                        $contract->setPersonDetails($person);

                        $this->entityManager->remove($contract->getCorporationDetails());
                        $this->entityManager->flush();

                        if (null !== $contract->getContractNumber()) {
                            $contracts[$customer->getAccountNumber()][] = $contract->getId().' - '.$contract->getContractNumber().' CP '.$oldContactPerson;
                        }
                    }
                }

                if (null !== $customer->getPersonDetails()) {
                    $this->entityManager->remove($customer->getPersonDetails());
                }

                $person = $this->createPersonFromCorporation($customer->getCorporationDetails());
                $customer->setPersonDetails($person);
                $customer->setType($accountType);

                $this->entityManager->remove($customer->getCorporationDetails());
                foreach ($customer->getRelationships() as $relationship) {
                    $this->entityManager->remove($relationship);
                }
                $this->entityManager->flush();
            } elseif (AccountType::INDIVIDUAL === $customer->getType()->getValue() && null !== $customer->getPersonDetails()) {
                $io->error('No support for INDIVIDUAL to CORPORATE yet.');

                return 0;
            }
        }

        $customerColumns = [];
        foreach ($applicationRequests as $customer => $applicationRequest) {
            foreach ($applicationRequest as $applicationRequestNumber) {
                if (\in_array($customer, $customerColumns, true)) {
                    $applicationRequestsOutputTable[] = ['', $applicationRequestNumber, 'Success'];
                } else {
                    $applicationRequestsOutputTable[] = [$customer, $applicationRequestNumber, 'Success'];
                    $customerColumns[] = $customer;
                }
            }
        }

        $customerColumns = [];
        foreach ($contracts as $customer => $contract) {
            foreach ($contract as $contractNumber) {
                if (\in_array($customer, $customerColumns, true)) {
                    $contractsOutputTable[] = ['', $contractNumber, 'Success'];
                } else {
                    $contractsOutputTable[] = [$customer, $contractNumber, 'Success'];
                    $customerColumns[] = $customer;
                }
            }
        }

        $io->newLine();
        $io->table(['Customer', 'Application Request', 'Status'], $applicationRequestsOutputTable);
        $io->newLine();
        $io->table(['Customer', 'Contract', 'Status'], $contractsOutputTable);
        $io->newLine();

        return 0;
    }

    private function createCorporationFromPerson(Person $person): Corporation
    {
        $corporation = new Corporation();
        $corporation->setName($person->getName());

        foreach ($person->getIdentifiers() as $identification) {
            if (IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD === $identification->getName()->getValue()) {
                $identification->setName(new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER));
            }
            $corporation->addIdentifier($identification);
        }

        foreach ($person->getContactPoints() as $contactPoint) {
            $corporation->addContactPoint($contactPoint);
        }

        $this->entityManager->persist($corporation);

        return $corporation;
    }

    private function createPersonFromCorporation(Corporation $corporation): Person
    {
        $person = new Person();
        $person->setName($corporation->getName());

        foreach ($corporation->getIdentifiers() as $identification) {
            if (IdentificationName::UNIQUE_ENTITY_NUMBER === $identification->getName()->getValue()) {
                $identification->setName(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
            }
            $person->addIdentifier($identification);
        }

        foreach ($corporation->getContactPoints() as $contactPoint) {
            $person->addContactPoint($contactPoint);
        }

        $this->entityManager->persist($person);

        return $person;
    }
}
