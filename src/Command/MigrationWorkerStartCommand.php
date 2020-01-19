<?php

declare(strict_types=1);

namespace App\Command;

use App\Bridge\Services\ContractApi;
use App\Disque\JobType;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\EarnContractCreditsAction;
use App\Entity\ReferralCreditsScheme;
use App\Enum\AccountType;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Tactician\CommandBus;
use libphonenumber\PhoneNumber;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class MigrationWorkerStartCommand extends Command
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var ContractApi
     */
    private $contractApi;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $emergencyWebhookUrl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DisqueQueue
     */
    private $migrationQueue;

    /**
     * @var DenormalizerInterface
     */
    private $serializer;

    /**
     * @param CommandBus             $commandBus
     * @param ContractApi            $contractApi
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param DisqueQueue            $migrationQueue
     * @param DenormalizerInterface  $serializer
     * @param string                 $emergencyWebhookUrl
     */
    public function __construct(CommandBus $commandBus, ContractApi $contractApi, EntityManagerInterface $entityManager, LoggerInterface $logger, DisqueQueue $migrationQueue, DenormalizerInterface $serializer, string $emergencyWebhookUrl)
    {
        parent::__construct();

        $this->commandBus = $commandBus;
        $this->contractApi = $contractApi;
        $this->emergencyWebhookUrl = $emergencyWebhookUrl;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->migrationQueue = $migrationQueue;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:migration-worker:start')
            ->setDescription('Starts the migration worker.')
            ->setHelp(<<<'EOF'
The %command.name% command starts the migration worker.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->success('Worker ready to accept tasks in the migration queue.');
        $io->comment('Stop the worker with CONTROL-C.');

        while ($job = $this->migrationQueue->pull()) {
            if (!$job instanceof DisqueJob) {
                throw new \UnexpectedValueException();
            }
            $task = $job->getBody();

            if ($job->getNacks() > 0 || $job->getAdditionalDeliveries() > 0) {
                $this->migrationQueue->processed($job);

                $io->text(\sprintf('Failed JOB %s.', $job->getId()));
                $io->text(\json_encode($task, JSON_PRETTY_PRINT));
                $this->logger->info('Failed JOB '.\json_encode($task, JSON_PRETTY_PRINT));

                if (!empty($this->emergencyWebhookUrl)) {
                    $client = new GuzzleClient();

                    $headers = [
                        'User-Agent' => 'U-Centric API',
                        'Content-Type' => 'application/json',
                    ];

                    $payload = [
                        'text' => 'Failed Disque Job #'.$job->getId(),
                        'attachments' => [
                            ['text' => \json_encode($task)],
                        ],
                    ];

                    $submitRequest = new GuzzlePsr7Request('POST', $this->emergencyWebhookUrl, $headers, \json_encode($payload));
                    $client->send($submitRequest);
                }

                $io->newLine();

                continue;
            }

            $error = false;
            $endMessageLog = 'You should not see this message.';
            $errorMessageLog = 'You should not see this error message.';

            //log start of job
            $io->text(\sprintf('[%s] Running JOB %s.', (new \DateTime())->format('r'), $job->getId()));
            $io->text(\json_encode($task, JSON_PRETTY_PRINT));
            $this->logger->info('Running JOB '.\json_encode($task, JSON_PRETTY_PRINT));

            switch ($task['type']) {
                case JobType::MIGRATE_CONTRACT_ACTION:
                    try {
                        $contractId = $task['data']['id'];
                        $contract = $this->entityManager->getRepository(Contract::class)->find($contractId);

                        if (null !== $contract) {
                            $this->contractApi->createContractActions([$contract]);
                        }

                        $endMessageLog = \sprintf('[%s] Done JOB %s. %s of %s', (new \DateTime())->format('r'), $job->getId(), $task['data']['count'], $task['data']['maxCount']);
                    } catch (\Exception $e) {
                        $io->error($e->getMessage());
                        $this->logger->error($e->getMessage());
                    }
                    break;
                case JobType::CLEAN_CUSTOMER_CONTACT_POINT:
                    try {
                        $customerId = $task['data']['id'];
                        $customer = $this->entityManager->getRepository(CustomerAccount::class)->find($customerId);

                        if (null !== $customer) {
                            $this->cleanCustomerContactPoint($customer);
                        }

                        $endMessageLog = \sprintf('[%s] Done JOB %s. #%s', (new \DateTime())->format('r'), $job->getId(), $task['data']['count']);
                    } catch (\Exception $e) {
                        $io->error($e->getMessage());
                        $this->logger->error($e->getMessage());
                    }
                    break;
                case JobType::CLEAN_CUSTOMER_DATA:
                    try {
                        $customerId = $task['data']['id'];
                        $customer = $this->entityManager->getRepository(CustomerAccount::class)->find($customerId);

                        if (null !== $customer) {
                            $this->cleanCustomerAddress($customer);
                        }

                        $endMessageLog = \sprintf('[%s] Done JOB %s. #%s', (new \DateTime())->format('r'), $job->getId(), $task['data']['count']);
                    } catch (\Exception $e) {
                        $io->error($e->getMessage());
                        $this->logger->error($e->getMessage());
                    }
                    break;
                case JobType::CLEAN_CONTRACT_DATA:
                    try {
                        $contractId = $task['data']['id'];
                        $contract = $this->entityManager->getRepository(Contract::class)->find($contractId);

                        if (null !== $contract) {
                            switch ($task['data']['type']) {
                                case 'addresses':
                                    $this->cleanContractAddress($contract);
                                    break;
                                case 'billSubscriptionTypes':
                                    $this->cleanContractBillSubscriptionTypes($contract);
                                    break;
                            }
                        }

                        $endMessageLog = \sprintf('[%s] Done JOB %s. #%s', (new \DateTime())->format('r'), $job->getId(), $task['data']['count']);
                    } catch (\Exception $e) {
                        $io->error($e->getMessage());
                        $this->logger->error($e->getMessage());
                    }
                    break;
                case JobType::UPDATE_REFERRAL_EARNING_INSTRUMENT:
                    try {
                        $earnContractCreditsAction = $this->entityManager->getRepository(EarnContractCreditsAction::class)->find($task['data']['id']);

                        if (null !== $earnContractCreditsAction && null === $earnContractCreditsAction->getInstrument()) {
                            $contract = $earnContractCreditsAction->getObject();
                            $referralCreditsScheme = $earnContractCreditsAction->getScheme();
                            $customer = $contract->getCustomer();
                            if ($referralCreditsScheme instanceof ReferralCreditsScheme && null !== $referralCreditsScheme->getReferralAmount()->getValue()) {
                                $referralCode = $customer->getReferralCode();
                                $subQb = $this->entityManager->getRepository(Contract::class)->createQueryBuilder('c');
                                $subQuery = $subQb->select('c.id')
                                    ->join(EarnContractCreditsAction::class, 'e', Join::WITH, 'e.instrument = c.id')
                                    ->getQuery()
                                    ->getDQL();

                                $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('app');
                                $expr = $qb->expr();

                                $applicationRequests = $qb->join('app.contract', 'con')
                                    ->where($expr->eq('app.referralCode', $expr->literal($referralCode)))
                                    ->andWhere($expr->isNotNull('con.contractNumber'))
                                    ->andWhere($expr->notIn('con.id', $subQuery))
                                    ->getQuery()
                                    ->getResult();

                                foreach ($applicationRequests as $applicationRequest) {
                                    $earnContractCreditsAction->setInstrument($applicationRequest->getContract());

                                    $this->entityManager->persist($earnContractCreditsAction);
                                    $this->entityManager->flush();
                                    break;
                                }
                            }
                        }

                        $endMessageLog = \sprintf('[%s] Done JOB %s. #%s', (new \DateTime())->format('r'), $job->getId(), $task['data']['count']);
                    } catch (\Exception $e) {
                        $io->error($e->getMessage());
                        $this->logger->error($e->getMessage());
                    }
                    break;
                default:
                    $error = true;
                    $errorMessageLog = \sprintf('[%s] Wrong Queue? Fail JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    break;
            }

            // ack the job
            $this->migrationQueue->processed($job);

            if (true === $error) {
                $io->error($errorMessageLog);
                $this->logger->error($errorMessageLog);
            } else {
                $io->text($endMessageLog);
                $this->logger->info($endMessageLog);
            }

            $this->entityManager->clear();
            $io->newLine();
        }

        return 0;
    }

    private function cleanContractBillSubscriptionTypes(Contract $contract)
    {
        if (\count($contract->getBillSubscriptionTypes()) > 1) {
            $billSubscriptionTypes = \array_unique(\array_map('strtoupper', $contract->getBillSubscriptionTypes()));
            $contract->replaceBillSubscriptionTypes($billSubscriptionTypes);

            $this->entityManager->persist($contract);
            $this->entityManager->flush();
        }
    }

    private function cleanContractAddress(Contract $contract)
    {
        $addressStrings = [];
        $duplicateAddresses = [];

        foreach ($contract->getAddresses() as $contractAddress) {
            if (!\in_array($contractAddress->getAddress()->__toString(), $addressStrings, true)) {
                $addressStrings[] = $contractAddress->getAddress()->__toString();
            } else {
                $duplicateAddresses[] = $contractAddress;
            }
        }

        foreach ($duplicateAddresses as $duplicateAddress) {
            $this->entityManager->remove($duplicateAddress);
        }

        $this->entityManager->flush();
    }

    private function cleanCustomerAddress(CustomerAccount $customer)
    {
        $addressStrings = [];
        $duplicateAddresses = [];

        foreach ($customer->getAddresses() as $customerAddress) {
            if (!\in_array($customerAddress->getAddress()->__toString(), $addressStrings, true)) {
                $addressStrings[] = $customerAddress->getAddress()->__toString();
            } else {
                $duplicateAddresses[] = $customerAddress;
            }
        }

        foreach ($duplicateAddresses as $duplicateAddress) {
            $this->entityManager->remove($duplicateAddress);
        }

        $this->entityManager->flush();
    }

    private function cleanCustomerContactPoint(CustomerAccount $customer)
    {
        $persist = null;
        $contactPoints = [];

        if (AccountType::CORPORATE === $customer->getType()->getValue() && null !== $customer->getCorporationDetails()) {
            $contactPoints = $customer->getCorporationDetails()->getContactPoints();
        } elseif (AccountType::INDIVIDUAL === $customer->getType()->getValue() && null !== $customer->getPersonDetails()) {
            $contactPoints = $customer->getPersonDetails()->getContactPoints();
        }

        foreach ($contactPoints as $contactPoint) {
            $update = false;

            if (\count($contactPoint->getEmails()) > 1) {
                $emails = \array_unique(\array_map('strtolower', $contactPoint->getEmails()));

                if ($emails !== $contactPoint->getEmails()) {
                    $update = true;
                    $contactPoint->replaceEmails($emails);
                }
            }

            if (\count($contactPoint->getFaxNumbers()) > 1) {
                $numbers = [];
                foreach ($contactPoint->getFaxNumbers() as $phoneNumber) {
                    $numbers[] = '+65'.$phoneNumber->getNationalNumber();
                }

                $uniqueNumbers = \array_unique($numbers);

                if ($uniqueNumbers !== $numbers) {
                    $update = true;
                    $contactPoint->clearFaxNumbers();
                    foreach ($uniqueNumbers as $uniqueNumber) {
                        $contactPoint->addFaxNumber($this->serializer->denormalize($uniqueNumber, PhoneNumber::class));
                    }
                }
            }

            if (\count($contactPoint->getMobilePhoneNumbers()) > 1) {
                $numbers = [];
                foreach ($contactPoint->getMobilePhoneNumbers() as $phoneNumber) {
                    $numbers[] = '+65'.$phoneNumber->getNationalNumber();
                }

                $uniqueNumbers = \array_unique($numbers);

                if ($uniqueNumbers !== $numbers) {
                    $update = true;
                    $contactPoint->clearMobilePhoneNumbers();
                    foreach ($uniqueNumbers as $uniqueNumber) {
                        $contactPoint->addMobilePhoneNumber($this->serializer->denormalize($uniqueNumber, PhoneNumber::class));
                    }
                }
            }

            if (\count($contactPoint->getTelephoneNumbers()) > 1) {
                $numbers = [];
                foreach ($contactPoint->getTelephoneNumbers() as $phoneNumber) {
                    $numbers[] = '+65'.$phoneNumber->getNationalNumber();
                }

                $uniqueNumbers = \array_unique($numbers);

                if ($uniqueNumbers !== $numbers) {
                    $update = true;
                    $contactPoint->clearTelephoneNumbers();
                    foreach ($uniqueNumbers as $uniqueNumber) {
                        $contactPoint->addTelephoneNumber($this->serializer->denormalize($uniqueNumber, PhoneNumber::class));
                    }
                }
            }

            if (true === $update) {
                $persist = true;
                $this->entityManager->persist($contactPoint);
                $this->entityManager->flush();
            }
        }

        return $persist;
    }
}
