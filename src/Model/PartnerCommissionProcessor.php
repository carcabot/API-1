<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Domain\Command\Partner\GetNextPayoutDate;
use App\Domain\Command\PartnerCommissionStatement\UpdateEndDate;
use App\Domain\Command\PartnerCommissionStatement\UpdateStartDate;
use App\Domain\Command\PartnerCommissionStatement\UpdateStatementNumber;
use App\Entity\ApplicationRequest;
use App\Entity\CustomerAccount;
use App\Entity\InternalDocument;
use App\Entity\Lead;
use App\Entity\MonetaryAmount;
use App\Entity\Partner;
use App\Entity\PartnerCommissionStatement;
use App\Entity\PartnerCommissionStatementData;
use App\Enum\ApplicationRequestStatus;
use App\Enum\CommissionCategory;
use App\Enum\CommissionStatementDataType;
use App\Enum\CommissionStatementStatus;
use App\Enum\DocumentType;
use App\Enum\LeadStatus;
use App\Enum\PaymentStatus;
use App\EventListener\Traits\RunningNumberLockTrait;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use iter;
use League\Tactician\CommandBus;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\SerializerInterface;

class PartnerCommissionProcessor
{
    use RunningNumberLockTrait;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DisqueQueue
     */
    private $reportsQueue;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $documentConverterHost;

    /**
     * @var string
     */
    private $profile;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     * @param DisqueQueue            $emailsQueue
     * @param LoggerInterface        $logger
     * @param DisqueQueue            $reportsQueue
     * @param SerializerInterface    $serializer
     * @param string                 $documentConverterHost
     * @param string                 $profile
     * @param string                 $timezone
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, DisqueQueue $emailsQueue, LoggerInterface $logger, DisqueQueue $reportsQueue, SerializerInterface $serializer, string $documentConverterHost, string $profile, string $timezone)
    {
        $this->commandBus = $commandBus;
        $this->iriConverter = $iriConverter;
        $this->emailsQueue = $emailsQueue;
        $this->logger = $logger;
        $this->reportsQueue = $reportsQueue;
        $this->serializer = $serializer;
        $this->documentConverterHost = $documentConverterHost;
        $this->profile = $profile;
        $this->timezone = new \DateTimeZone($timezone);
        $this->setEntityManager($entityManager);
        $this->setLocked(false);
    }

    /**
     * Creates a new partner commission statement.
     *
     * @param Partner        $partner
     * @param \DateTime      $payoutDate
     * @param \DateTime|null $startDate
     */
    public function createNewStatement(Partner $partner, \DateTime $payoutDate, ?\DateTime $startDate = null)
    {
        if (null === $startDate) {
            if (null !== $partner->getJoiningDate()) {
                $startDate = $partner->getJoiningDate();
            } else {
                $startDate = new \DateTime();
                $startDate->setTimezone($this->timezone)->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));
            }
        }

        $commissionStatement = new PartnerCommissionStatement();
        $commissionStatement->setPartner($partner);
        $commissionStatement->setPaymentStatus(new PaymentStatus(PaymentStatus::PENDING));
        $commissionStatement->setStatus(new CommissionStatementStatus(CommissionStatementStatus::NEW));
        $commissionStatement->setStartDate($startDate);

        $this->commandBus->handle(new UpdateEndDate($commissionStatement, $payoutDate, $this->timezone));
        $this->commandBus->handle(new UpdateStatementNumber($commissionStatement));

        return $commissionStatement;
    }

    /**
     * Gets all commission statements ending within the range.
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     */
    public function getStatementsByDate(\DateTime $startDate, \DateTime $endDate)
    {
        $qb = $this->entityManager->getRepository(PartnerCommissionStatement::class)->createQueryBuilder('st');
        $expr = $qb->expr();

        return $qb->where($expr->gte('st.endDate', $expr->literal($startDate->format('c'))))
            ->andWhere($expr->lte('st.endDate', $expr->literal($endDate->format('c'))))
            ->andWhere($expr->eq('st.status', $expr->literal(CommissionStatementStatus::NEW)))
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets PDF version of the statement.
     *
     * @param PartnerCommissionStatement $statement
     * @param CustomerAccount            $partnerCustomerAccount
     */
    public function getStatementPdf(PartnerCommissionStatement $statement, CustomerAccount $partnerCustomerAccount)
    {
        $path = 'pdf/partner_commission_statement';
        $internalDocument = null;

        if (\count($statement->getData()) > 0) {
            $customerAccountData = $this->serializer->serialize($partnerCustomerAccount, 'jsonld', [
                'groups' => [
                    'customer_account_read',
                    'user_read',
                ],
            ]);

            $statementData = $this->serializer->serialize($statement, 'jsonld', [
                'groups' => [
                    'partner_commission_statement_generate',
                    'corporation_read',
                    'person_read',
                ],
            ]);

            $filename = \sprintf('%s.%s', $statement->getStatementNumber(), 'pdf');

            if (!empty($statementData) && !empty($customerAccountData)) {
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
                        'customerAccount' => \json_decode($customerAccountData),
                        'partnerCommissionStatement' => \json_decode($statementData),
                        'profile' => $this->profile,
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
                    $internalDocument->setType(new DocumentType(DocumentType::PARTNER_COMMISSION_STATEMENT));
                    $internalDocument->setName($filename);
                    $internalDocument->setOwner($partnerCustomerAccount);
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

    /**
     * Processes a commission statement for a given partner.
     *
     * @param array $data
     * @param bool  $queueNextPayout
     */
    public function generatePartnerCommissionStatement(array $data, bool $queueNextPayout = true)
    {
        $partner = $this->entityManager->getRepository(Partner::class)->find($data['partnerId']);
        $partnerCustomerAccount = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['partnerDetails' => $data['partnerId']]);
        $commissionStatementId = $data['commissionStatementId'];
        $commissionStatement = null;
        $errors = null;

        if (null === $partner) {
            $errors = \sprintf('Partner %s not found. ', $data['partnerId']);
        }

        if (null === $partnerCustomerAccount) {
            $errors .= \sprintf('Partner CustomerAccount for %s not found. ', $data['partnerId']);
        }

        if (null !== $partner && null !== $partnerCustomerAccount) {
            $this->entityManager->getConnection()->beginTransaction(); // suspend auto-commit
            try {
                $commissionStatement = $this->entityManager->getRepository(PartnerCommissionStatement::class)->find($commissionStatementId, LockMode::PESSIMISTIC_WRITE);

                $currency = '';
                $totalFromApplicationRequests = 0;
                $totalFromLeads = 0;
                if (null !== $commissionStatement && CommissionStatementStatus::COMPLETED !== $commissionStatement->getStatus()->getValue()) {
                    // same date, proceed
                    if ($commissionStatement->getEndDate()->getTimestamp() === $data['endDateTimestamp']) {
                        $activeApplicationRequestCommissionRate = iter\search(function ($commissionRate) {
                            if ($commissionRate->isActive() && CommissionCategory::CONTRACT_APPLICATION === $commissionRate->getCategory()->getValue()) {
                                return $commissionRate;
                            }
                        }, $partner->getCommissionRates());

                        $activeLeadCommissionRate = iter\search(function ($commissionRate) {
                            if ($commissionRate->isActive() && CommissionCategory::LEAD === $commissionRate->getCategory()->getValue()) {
                                return $commissionRate;
                            }
                        }, $partner->getCommissionRates());

                        if (null !== $activeApplicationRequestCommissionRate) {
                            //fixed rate only for now
                            $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('applicationRequest');
                            $subQb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('appReq');
                            $expr = $qb->expr();

                            //subquery will be reused in query, need to use different alias
                            $subQuery = $subQb->select('appReq.id')
                                ->innerJoin(PartnerCommissionStatementData::class, 'partnerCommissionData', Expr\Join::WITH, 'appReq.id = partnerCommissionData.applicationRequest')
                                ->where($expr->eq('appReq.acquiredFrom', $expr->literal($partnerCustomerAccount->getId())))
                                ->getDQL();

                            $completedApplicationRequests = $qb->select('applicationRequest')
                                ->where($expr->eq('applicationRequest.acquiredFrom', $expr->literal($partnerCustomerAccount->getId())))
                                ->andWhere($expr->eq('applicationRequest.status', $expr->literal(ApplicationRequestStatus::COMPLETED)))
                                ->andWhere($expr->notIn('applicationRequest.id', $subQuery))
                                ->getQuery()
                                ->getResult();

                            if (\count($completedApplicationRequests) > 0) {
                                $clonedCommissionRate = clone $activeApplicationRequestCommissionRate;
                                $clonedCommissionRate->setIsBasedOn($activeApplicationRequestCommissionRate);
                                $this->entityManager->persist($clonedCommissionRate);

                                $amount = $clonedCommissionRate->getValue();
                                $commissionAmount = new MonetaryAmount((string) \number_format((float) $amount, 2, '.', ''), $clonedCommissionRate->getCurrency());
                                $currency = $clonedCommissionRate->getCurrency();

                                foreach ($completedApplicationRequests as $completedApplicationRequest) {
                                    $totalFromApplicationRequests += $amount;

                                    $commissionStatementData = new PartnerCommissionStatementData();
                                    $commissionStatementData->setAmount($commissionAmount);
                                    $commissionStatementData->setApplicationRequest($completedApplicationRequest);
                                    $commissionStatementData->setCommissionRate($clonedCommissionRate);
                                    $commissionStatementData->setStatement($commissionStatement);
                                    $commissionStatementData->setType(new CommissionStatementDataType(CommissionStatementDataType::APPLICATION_REQUEST));

                                    $this->entityManager->persist($commissionStatementData);
                                    $commissionStatement->addData($commissionStatementData);
                                }
                            }
                        }

                        if (null !== $activeLeadCommissionRate) {
                            //fixed rate only for now
                            $qb = $this->entityManager->getRepository(Lead::class)->createQueryBuilder('lead');
                            $subQb = $this->entityManager->getRepository(Lead::class)->createQueryBuilder('ld');
                            $expr = $qb->expr();

                            //subquery will be reused in query, need to use different alias
                            $subQuery = $subQb->select('ld.id')
                                ->innerJoin(PartnerCommissionStatementData::class, 'partnerCommissionData', Expr\Join::WITH, 'ld.id = partnerCommissionData.lead')
                                ->where($expr->eq('ld.acquiredFrom', $expr->literal($partnerCustomerAccount->getId())))
                                ->getDQL();

                            $convertedLeads = $qb->select('lead')
                                ->where($expr->eq('lead.acquiredFrom', $expr->literal($partnerCustomerAccount->getId())))
                                ->andWhere($expr->eq('lead.status', $expr->literal(LeadStatus::CONVERTED)))
                                ->andWhere($expr->notIn('lead.id', $subQuery))
                                ->getQuery()
                                ->getResult();

                            if (\count($convertedLeads) > 0) {
                                $clonedCommissionRate = clone $activeLeadCommissionRate;
                                $clonedCommissionRate->setIsBasedOn($activeLeadCommissionRate);
                                $this->entityManager->persist($clonedCommissionRate);

                                $amount = $clonedCommissionRate->getValue();
                                $commissionAmount = new MonetaryAmount((string) \number_format((float) $amount, 2, '.', ''), $clonedCommissionRate->getCurrency());
                                $currency = $clonedCommissionRate->getCurrency();

                                foreach ($convertedLeads as $convertedLead) {
                                    $totalFromLeads += $amount;

                                    $commissionStatementData = new PartnerCommissionStatementData();
                                    $commissionStatementData->setAmount($commissionAmount);
                                    $commissionStatementData->setCommissionRate($clonedCommissionRate);
                                    $commissionStatementData->setLead($convertedLead);
                                    $commissionStatementData->setStatement($commissionStatement);
                                    $commissionStatementData->setType(new CommissionStatementDataType(CommissionStatementDataType::LEAD));

                                    $this->entityManager->persist($commissionStatementData);
                                    $commissionStatement->addData($commissionStatementData);
                                }
                            }
                        }
                    }

                    $totalAmountDue = $totalFromLeads + $totalFromApplicationRequests;

                    $commissionStatement->setTotalPaymentDue(new MonetaryAmount((string) \number_format($totalAmountDue, 2, '.', ''), $currency));
                    $commissionStatement->setStatus(new CommissionStatementStatus(CommissionStatementStatus::COMPLETED));
                    $this->entityManager->persist($commissionStatement);
                    $this->entityManager->flush();
                    $this->entityManager->getConnection()->commit();
                }
            } catch (\Exception $e) {
                $this->entityManager->getConnection()->rollBack();
                throw $e;
            }

            // create the next commission statement
            if (null !== $commissionStatement) {
                // generate pdf
                $statementPdf = $this->getStatementPdf($commissionStatement, $partnerCustomerAccount);

                if (null !== $statementPdf) {
                    $commissionStatement->setFile($statementPdf);

                    $this->entityManager->persist($commissionStatement);
                    $this->entityManager->flush();
                }
                // generate pdf

                $sendEmailJob = new DisqueJob([
                    'data' => [
                        'partner' => $this->iriConverter->getIriFromItem($partner),
                        'partnerCommissionStatement' => $this->iriConverter->getIriFromItem($commissionStatement),
                        'partnerCustomerAccount' => $this->iriConverter->getIriFromItem($partnerCustomerAccount),
                    ],
                    'type' => JobType::PARTNER_GENERATED_COMMISSION_STATEMENT,
                ]);
                $this->emailsQueue->push($sendEmailJob);

                if (true === $queueNextPayout) {
                    $nextPayoutDate = $this->commandBus->handle(new GetNextPayoutDate($partner, $commissionStatement->getEndDate(), $this->timezone));

                    if (null !== $nextPayoutDate) {
                        $this->startLockTransaction();
                        $nextCommissionStatement = $this->createNewStatement($partner, $nextPayoutDate);
                        $this->commandBus->handle(new UpdateStartDate($nextCommissionStatement, $commissionStatement->getEndDate(), $this->timezone));

                        $this->entityManager->persist($nextCommissionStatement);
                        $this->entityManager->flush();
                        $this->endLockTransaction();

                        $this->scheduleGenerateJob($nextCommissionStatement);
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Schedules a generate job for partner commission statement.
     *
     * @param PartnerCommissionStatement $commissionStatement
     */
    public function scheduleGenerateJob(PartnerCommissionStatement $commissionStatement)
    {
        $now = new \DateTime();
        $endOfToday = clone $now;
        $endOfToday->setTimezone($this->timezone)->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $this->entityManager->refresh($commissionStatement);

        // only if status is NEW and endDate is within the same day.
        if (CommissionStatementStatus::NEW === $commissionStatement->getStatus()->getValue() && $commissionStatement->getEndDate() <= $endOfToday) {
            $statementJob = new DisqueJob([
                'data' => [
                    'commissionStatementId' => $commissionStatement->getId(),
                    'endDateTimestamp' => $commissionStatement->getEndDate()->getTimestamp(),
                    'partnerId' => $commissionStatement->getPartner()->getId(),
                ],
                'type' => JobType::PARTNER_GENERATE_COMMISSION_STATEMENT,
            ]);

            if ($commissionStatement->getEndDate() <= $now) {
                $this->reportsQueue->push($statementJob);
            } else {
                $this->reportsQueue->schedule($statementJob, $commissionStatement->getEndDate());
            }
        }
    }
}
