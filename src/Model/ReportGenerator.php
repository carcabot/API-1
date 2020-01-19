<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Document\Reports\ApplicationRequestReport;
use App\Document\Reports\ContractReport;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\CreditsSubtractionInterface;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountRelationship;
use App\Entity\EarnContractCreditsAction;
use App\Entity\FreeGiftListItem;
use App\Entity\InternalDocument;
use App\Entity\Lead;
use App\Entity\OfferCatalog;
use App\Entity\OfferListItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\RedeemCreditsAction;
use App\Entity\Ticket;
use App\Entity\UpdateContractAction;
use App\Entity\UpdateCreditsAction;
use App\Entity\User;
use App\Enum\AccountType;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use App\Enum\BillSubscriptionType;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Enum\DocumentType;
use App\Enum\IdentificationName;
use App\Enum\OrderStatus;
use App\Enum\PostalAddressType;
use App\Enum\Source;
use App\Repository\ApplicationRequestRepository;
use App\Repository\CustomerAccountRelationshipRepository;
use App\Repository\EarnContractCreditsActionRepository;
use App\Repository\LeadRepository;
use App\Repository\OrderRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Service\ReportHelper;
use App\WebService\Billing\Services\DataMapper;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Tactician\CommandBus;
use League\Uri\Components\Query as UriQuery;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ReportGenerator
{
    //region Member Declaration
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
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var string
     */
    private $documentConverterHost;

    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var PhoneNumberUtil
     */
    private $phoneNumberUtil;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var ServiceLevelAgreementTimerCalculator
     */
    private $serviceLevelAgreementTimerCalculator;

    /**
     * @var string
     */
    private $appProfile;

    /**
     * @var array
     */
    private $exclusionHeadersList;
    //endregion

    /**
     * @param CommandBus                           $commandBus
     * @param DataMapper                           $dataMapper
     * @param EntityManagerInterface               $entityManager
     * @param DocumentManager                      $documentManager
     * @param string                               $documentConverterHost
     * @param DisqueQueue                          $emailsQueue
     * @param IriConverterInterface                $iriConverter
     * @param PhoneNumberUtil                      $phoneNumberUtil
     * @param string                               $timezone
     * @param ServiceLevelAgreementTimerCalculator $serviceLevelAgreementTimerCalculator
     * @param string                               $appProfile
     */
    public function __construct(CommandBus $commandBus, DataMapper $dataMapper, EntityManagerInterface $entityManager, DocumentManager $documentManager, string $documentConverterHost, DisqueQueue $emailsQueue, IriConverterInterface $iriConverter, PhoneNumberUtil $phoneNumberUtil, string $timezone, ServiceLevelAgreementTimerCalculator $serviceLevelAgreementTimerCalculator, string $appProfile)
    {
        $this->commandBus = $commandBus;
        $this->dataMapper = $dataMapper;
        $this->entityManager = $entityManager;
        $this->documentConverterHost = $documentConverterHost;
        $this->emailsQueue = $emailsQueue;
        $this->iriConverter = $iriConverter;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->timezone = new \DateTimeZone($timezone);
        $this->serviceLevelAgreementTimerCalculator = $serviceLevelAgreementTimerCalculator;
        $this->documentManager = $documentManager;
        $this->appProfile = $appProfile;

        switch ($appProfile) {
            case 'iswitch':
                $this->exclusionHeadersList = [
                    'applicationRequest' => ['Promotion Code', 'Energy Rate', 'Conditional Discounts'],
                    'contract' => [],
                    'creditsAction' => ['MSSL No', 'EBS No'],
                    'customerAccount' => [],
                    'customerAccountRelationship' => [],
                    'lead' => [],
                    'order' => ['MSSL No', 'EBS No'],
                    'ticket' => ['Customer Account', 'MSSL No', 'EBS No'],
                    'user' => [],
                ];
                break;
            case 'unionpower':
                $this->exclusionHeadersList = [
                    'applicationRequest' => ['Channel', 'Location Code'],
                    'contract' => [],
                    'creditsAction' => [],
                    'customerAccount' => [],
                    'customerAccountRelationship' => [],
                    'lead' => [],
                    'order' => [],
                    'ticket' => [],
                    'user' => [],
                ];
                break;
            default:
                $this->exclusionHeadersList = [
                    'applicationRequest' => [],
                    'contract' => [],
                    'creditsAction' => [],
                    'customerAccount' => [],
                    'customerAccountRelationship' => [],
                    'lead' => [],
                    'order' => [],
                    'ticket' => [],
                    'user' => [],
                ];
        }
    }

    public function convertDataToInternalDocument(array $allReportData, string $type)
    {
        try {
            $documentType = new DocumentType($type);
        } catch (\Exception $e) {
            throw $e;
        }

        $path = 'reports/xlsx_spreadsheet';
        $reportDocuments = [];
        $documentId = null;

        if (!empty($allReportData)) {
            foreach ($allReportData as $reportData) {
                if (!empty($reportData['documentId'])) {
                    $documentId = $reportData['documentId'];
                }

                if (!empty($reportData['data'])) {
                    if (\array_key_exists('customReport', $reportData) && true === $reportData['customReport']) {
                        $path = $path.'_custom';
                    }
                    $baseUri = HttpUri::createFromString($this->documentConverterHost);
                    $modifier = new AppendSegment($path);
                    $uri = $modifier->process($baseUri);

                    $client = new GuzzleClient();
                    $headers = [
                        'User-Agent' => 'U-Centric API',
                        'Content-Type' => 'application/json',
                    ];

                    $request = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($reportData));
                    $filePath = \sprintf('%s/%s', \sys_get_temp_dir(), $reportData['filename']);
                    $resource = \fopen($filePath, 'wb+');
                    $response = $client->send($request, ['save_to' => $filePath]);

                    // mime type hack
                    $fileType = MimeTypeGuesser::getInstance()->guess($filePath);
                    $pathInfo = \pathinfo($filePath);
                    if ('application/zip' === $fileType && 'xlsx' === $pathInfo['extension']) {
                        $fileType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    }
                    // mime type hack
                    $contentFile = new UploadedFile(
                        $filePath,
                        $reportData['filename'],
                        $fileType,
                        null,
                        true
                    );

                    $internalDocument = null !== $documentId
                        ? $this->entityManager->getRepository(InternalDocument::class)->find($documentId)
                        : new InternalDocument();

                    if (null !== $internalDocument) {
                        $internalDocument->setContentFile($contentFile);
                        $internalDocument->setType($documentType);
                        $internalDocument->setName($reportData['filename']);
                        $this->entityManager->persist($internalDocument);
                        $this->entityManager->flush();
                        \fclose($resource);
                    } else {
                        throw  new \Exception("Internal Document with id {$documentId} does not exist");
                    }

                    $reportDocuments[] = $internalDocument;
                }
            }
        }

        return $reportDocuments;
    }

    public function createOfferCatalogReport(int $id)
    {
        $offerCatalog = $this->entityManager->getRepository(OfferCatalog::class)->findOneBy(['id' => $id]);

        if (null !== $offerCatalog) {
            $offerListItems = $offerCatalog->getItemListElement();

            $report = [
                'data' => [],
                'filename' => \sprintf('%s %s.xlsx', 'Offer Catalog', $offerCatalog->getName()),
                'headers' => [
                    'Partner Name',
                    'Product Name',
                    'Product Category',
                    'Product Type',
                    'SKU',
                    'Valid From',
                    'Valid To',
                    'Points',
                    'Amount',
                ],
                'column-format' => [],
            ];

            // populate all column-format
            foreach ($report['headers'] as $key => $header) {
                $report['column-format'][$key] = ['wch' => \strlen($header) + 3];
            }

            // populate all column-format
            foreach ($offerListItems as $offerListItem) {
                if ($offerListItem instanceof OfferListItem) {
                    $data = [];
                    foreach ($report['headers'] as $column => $key) {
                        $data[$key] = $this->mapOfferListItemData($offerListItem, $key);

                        if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                            $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                        }

                        $report['data'][] = $data;
                    }
                }
            }

            return $report;
        }

        return null;
    }

    /**
     * @param \DateTime|null $startDate
     * @param \DateTime      $endDate
     * @param string|null    $reportType
     * @param string|null    $filename
     *
     * @return array|null
     */
    public function createPartnerContractApplicationReport(?\DateTime $startDate, \DateTime $endDate, ?string $reportType = null, ?string $filename = null)
    {
        if (null !== $startDate) {
            $utcStartDate = clone $startDate;
            $utcStartDate->setTimezone(new \DateTimeZone('UTC'));
        } else {
            $utcStartDate = null;
        }
        $this->timezone = $endDate->getTimezone();
        $utcEndDate = clone $endDate;
        $utcEndDate->setTimezone(new \DateTimeZone('UTC'));

        $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('applicationRequest');
        $expr = $qb->expr();
        $reports = [
            'complete' => [
                'data' => [],
                'filename' => \sprintf('%s%s_%s.xlsx', 'Partnership Report_', $endDate->format('d-M'), 'Approved'),
                'headers' => [
                    'Partner Name',
                    'Application ID',
                    'Status',
                    'Sales Rep Name',
                    'Location Code',
                    'Account Holder',
                    'Premise Address',
                    'Promotion Plan Code',
                    'Promotion Plan Name',
                    'Dwelling Type',
                    'Created By (ID)',
                    'Created Datetime',
                    'Submitted By (ID)',
                    'Submitted Datetime',
                    'Remarks',
                ],
                'column-format' => [],
            ],
            'draft_in_progress_voided' => [
                'data' => [],
                'filename' => \sprintf('%s%s_%s.xlsx', 'Partnership Report_', $endDate->format('d-M'), 'DraftInProgressVoided'),
                'headers' => [
                    'Partner Name',
                    'Draft App ID',
                    'In Progress App ID',
                    'Status',
                    'Sales Rep Name',
                    'Location Code',
                    'Account Holder',
                    'Account Holder Contact',
                    'Premise Address',
                    'Promotion Plan Code',
                    'Promotion Plan Name',
                    'Dwelling Type',
                    'Created By (ID)',
                    'Created Datetime',
                    'Submitted By (ID)',
                    'Submitted Datetime',
                    'Remarks',
                    'SP Account Missing for Draft Applications?',
                    'Sign on Behalf Attachment Missing for Draft Applications?',
                    'SP Account Holder',
                    'Applicant',
                    'Applicant Contact',
                    'Applicant Email',
                ],
                'column-format' => [],
            ],
            'fail' => [
                'data' => [],
                'filename' => \sprintf('%s%s_%s.xlsx', 'Partnership Report_', $endDate->format('d-M'), 'Failed'),
                'headers' => [
                    'Partner Name',
                    'Application ID',
                    'Status',
                    'Sales Rep Name',
                    'Location Code',
                    'Account Holder',
                    'Account Holder Contact',
                    'Premise Address',
                    'Promotion Plan Code',
                    'Promotion Plan Name',
                    'Remarks',
                    'MSSL Account Number (For Verification Purpose)',
                    'EBS Account Number (For Verification Purpose)',
                    'Created By (ID)',
                    'Created Datetime',
                    'Submitted By (ID)',
                    'Submitted Datetime',
                    'Reason of Failure',
                    'Partner Remarks',
                ],
                'column-format' => [],
            ],
        ];

        // this means only the specified report type will be generated.
        if (null !== $reportType && isset($reports[$reportType])) {
            $reports = [$reportType => $reports[$reportType]];
        }

        // populate all column-format
        foreach ($reports as $reportKey => $report) {
            // this should only be called together with $reportKey. If not, all filenames will be the same. Pending massive refactoring for reports
            if (null !== $filename) {
                $reports[$reportKey]['filename'] = $filename;
            }

            foreach ($report['headers'] as $key => $header) {
                $reports[$reportKey]['column-format'][$key] = ['wch' => \strlen($header) + 3];
            }
        }
        // populate all column-format

        // end date should always be present
        $dateCreatedExpr = $expr->andX();
        $dateCreatedExpr->add($expr->lte('applicationRequest.dateCreated', $expr->literal($utcEndDate->format('c'))));

        $dateModifiedExpr = $expr->andX();
        $dateModifiedExpr->add($expr->lte('applicationRequest.dateModified', $expr->literal($utcEndDate->format('c'))));
        $dateModifiedExpr->add($expr->in('applicationRequest.status', ':modifyStatuses'));

        $dateSubmittedExpr = $expr->andX();
        $dateSubmittedExpr->add($expr->lte('applicationRequest.dateSubmitted', $expr->literal($utcEndDate->format('c'))));
        // end date should always be present

        if (null !== $utcStartDate) {
            $dateCreatedExpr->add($expr->gte('applicationRequest.dateCreated', $expr->literal($utcStartDate->format('c'))));
            $dateModifiedExpr->add($expr->gte('applicationRequest.dateModified', $expr->literal($utcStartDate->format('c'))));
            $dateSubmittedExpr->add($expr->gte('applicationRequest.dateSubmitted', $expr->literal($utcStartDate->format('c'))));
        }

        $qb->where(
            $expr->orX(
                $expr->orX(
                    $dateCreatedExpr,
                    $dateSubmittedExpr
                ),
                $dateModifiedExpr
            )
        )
            ->andWhere($expr->eq('applicationRequest.source', $expr->literal(Source::PARTNERSHIP_PORTAL)))
            ->setParameter('modifyStatuses', [ApplicationRequestStatus::COMPLETED, ApplicationRequestStatus::CANCELLED, ApplicationRequestStatus::REJECTED, ApplicationRequestStatus::VOIDED]);

        if (null !== $reportType) {
            $statuses = [];
            switch ($reportType) {
                case 'complete':
                    $statuses[] = ApplicationRequestStatus::COMPLETED;
                    break;
                case 'draft_in_progress_voided':
                    $statuses[] = ApplicationRequestStatus::IN_PROGRESS;
                    $statuses[] = ApplicationRequestStatus::PARTNER_DRAFT;
                    $statuses[] = ApplicationRequestStatus::VOIDED;
                    break;
                case 'fail':
                    $statuses[] = ApplicationRequestStatus::CANCELLED;
                    $statuses[] = ApplicationRequestStatus::REJECTED;
                    break;
                default:
                    break;
            }

            if (!empty($statuses)) {
                $qb->andWhere($expr->in('applicationRequest.status', ':statuses'))
                    ->setParameter('statuses', $statuses);
            }
        }

        $applicationRequests = $qb->orderBy('applicationRequest.dateCreated', 'ASC')
            ->getQuery()
            ->useResultCache(false)
            ->getResult();

        $reportKey = null;

        foreach ($applicationRequests as $applicationRequest) {
            if (ApplicationRequestStatus::COMPLETED === $applicationRequest->getStatus()->getValue()) {
                $reportKey = 'complete';
            } elseif (\in_array($applicationRequest->getStatus()->getValue(), [
                ApplicationRequestStatus::IN_PROGRESS,
                ApplicationRequestStatus::PARTNER_DRAFT,
                ApplicationRequestStatus::VOIDED,
            ], true)
            ) {
                $reportKey = 'draft_in_progress_voided';
            } elseif (\in_array($applicationRequest->getStatus()->getValue(), [
                ApplicationRequestStatus::CANCELLED,
                ApplicationRequestStatus::REJECTED,
            ], true)
            ) {
                $reportKey = 'fail';
            }

            if (null !== $reportKey) {
                $data = [];
                foreach ($reports[$reportKey]['headers'] as $column => $key) {
                    $data[$key] = $this->mapApplicationRequestReportData($applicationRequest, $key);
                    if (\strlen((string) $data[$key]) + 3 > $reports[$reportKey]['column-format'][$column]['wch']) {
                        $reports[$reportKey]['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                    }
                }
                $reports[$reportKey]['data'][] = $data;
            }
        }

        return $reports;
    }

    /**
     * @param array $params
     *
     * @return array|null
     */
    public function createApplicationRequestReport(array $params)
    {
        $queryParams = [];

        $utcEndDate = $utcStartDate = $createdDateTo = $createdDateFrom = $tariffRate = $applicationRequestNumber =
        $contractType = $category = $contractSubType = $status = $source = $referralCode = $utcCreatedDateTo = $agency =
        $utcCreatedDateFrom = $utcPreferredEndDateTo = $utcPreferredStartDateTo = $startDate = $endDate = $accountNumber =
        $industry = $channel = $salesRep = $partnerCode = $preferredEndDateFrom = $preferredEndDateTo = $type =
        $preferredStartDateFrom = $preferredStartDateTo = $utcPreferredEndDateFrom = $utcPreferredStartDateFrom =
        $selfApplication = $customerType = null;

        $applicationRequesRepo = $this->entityManager->getRepository(ApplicationRequest::class);
        $qb = $applicationRequesRepo->createQueryBuilder('applicationRequest');
        $expr = $qb->expr();

        if (\array_key_exists('selfApplication', $params)) {
            if ('true' === $params['selfApplication']) {
                $selfApplication = $queryParams['selfApplication'] = $expr->literal(true);
            } else {
                $selfApplication = $queryParams['selfApplication'] = $expr->literal(false);
            }
        }

        if (\array_key_exists('type', $params)) {
            $type = $queryParams['type'] = $params['type'];
        }

        if (\array_key_exists('applicationRequestNumber', $params)) {
            $applicationRequestNumber = $queryParams['applicationRequestNumber'] = $params['applicationRequestNumber'];
        }

        if (\array_key_exists('contractType', $params)) {
            $contractType = $queryParams['contractType'] = $params['contractType'];
        }

        if (\array_key_exists('category', $params)) {
            $category = $queryParams['category'] = $params['category'];
        }

        if (\array_key_exists('customerType', $params)) {
            $customerType = $queryParams['customerType'] = $params['customerType'];
        }

        if (\array_key_exists('contractSubtype', $params)) {
            $contractSubType = $queryParams['contractSubtype'] = $params['contractSubtype'];
        }

        if (\array_key_exists('channel', $params)) {
            $channel = $queryParams['channel'] = $params['channel'];
        }

        if (\array_key_exists('agency', $params)) {
            if ($applicationRequesRepo instanceof ApplicationRequestRepository) {
                $agencyTsQuery = $applicationRequesRepo->getKeywordTsquery([$params['agency']], true);

                $agency = $queryParams['agency'] = $agencyTsQuery;
            }
        }

        if (\array_key_exists('salesRep', $params)) {
            if ($applicationRequesRepo instanceof ApplicationRequestRepository) {
                $salesRepTsQuery = $applicationRequesRepo->getKeywordTsquery([$params['salesRep']], true);

                $salesRep = $queryParams['salesRep'] = $salesRepTsQuery;
            }
        }

        if (\array_key_exists('accountNumber', $params)) {
            $accountNumber = $queryParams['accountNumber'] = $params['accountNumber'];
        }

        if (\array_key_exists('acquiredFrom.accountNumber', $params)) {
            $partnerCode = $queryParams['partnerCode'] = $params['acquiredFrom.accountNumber'];
        }

        if (\array_key_exists('tariffRate.isBasedOn', $params)) {
            $tariffRate = $queryParams['tariffRate'] = $this->getAllObjectIdsFromIris($params['tariffRate.isBasedOn']);
        }

        if (\array_key_exists('status', $params)) {
            $status = $queryParams['status'] = $params['status'];

            if (\in_array(ApplicationRequestStatus::DRAFT, $params['status'], true)
                && !\in_array(ApplicationRequestStatus::PARTNER_DRAFT, $queryParams['status'], true)) {
                \array_push($queryParams['status'], ApplicationRequestStatus::PARTNER_DRAFT);
            }
        }

        if (\array_key_exists('source', $params)) {
            $source = $queryParams['source'] = $params['source'];
        }

        if (\array_key_exists('referralCode', $params)) {
            $referralCode = $queryParams['referralCode'] = $params['referralCode'];
        }

        if (\array_key_exists('dateCreated[after]', $params) && null !== $params['dateCreated[after]']) {
            $createdDateFrom = new \DateTime($params['dateCreated[after]']);
            $this->timezone = $createdDateFrom->getTimezone();
            $utcCreatedDateFrom = clone $createdDateFrom;
            $utcCreatedDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('dateCreated[before]', $params) && null !== $params['dateCreated[before]']) {
            $createdDateTo = new \DateTime($params['dateCreated[before]']);
            $this->timezone = $createdDateTo->getTimezone();
            $utcCreatedDateTo = clone $createdDateTo;
            $utcCreatedDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('startDate', $params) && null !== $params['startDate']) {
            $startDate = new \DateTime($params['startDate']);
            $this->timezone = $startDate->getTimezone();
            $utcStartDate = clone $startDate;
            $utcStartDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('endDate', $params) && null !== $params['endDate']) {
            $endDate = new \DateTime($params['endDate']);
            $this->timezone = $endDate->getTimezone();
            $utcEndDate = clone $endDate;
            $utcEndDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('preferredStartDate[after]', $params) && null !== $params['preferredStartDate[after]']) {
            $preferredStartDateFrom = new \DateTime($params['preferredStartDate[after]']);
            $this->timezone = $preferredStartDateFrom->getTimezone();
            $utcPreferredStartDateFrom = clone $preferredStartDateFrom;
            $utcPreferredStartDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('preferredStartDate[before]', $params) && null !== $params['preferredStartDate[before]']) {
            $preferredStartDateTo = new \DateTime($params['preferredStartDate[before]']);
            $this->timezone = $preferredStartDateTo->getTimezone();
            $utcPreferredStartDateTo = clone $preferredStartDateTo;
            $utcPreferredStartDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('preferredEndDate[after]', $params) && null !== $params['preferredEndDate[after]']) {
            $preferredEndDateFrom = new \DateTime($params['preferredEndDate[after]']);
            $this->timezone = $preferredEndDateFrom->getTimezone();
            $utcPreferredEndDateFrom = clone $preferredEndDateFrom;
            $utcPreferredEndDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('preferredEndDate[before]', $params) && null !== $params['preferredEndDate[before]']) {
            $preferredEndDateTo = new \DateTime($params['preferredEndDate[before]']);
            $this->timezone = $preferredEndDateTo->getTimezone();
            $utcPreferredEndDateTo = clone $preferredEndDateTo;
            $utcPreferredEndDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        $report = [
            'data' => [],
            'filename' => \sprintf('%s_%s.xlsx', 'Application Request Report', (new \DateTime('now'))->format('d-M')),
            'headers' => [
                'Application Request ID',
                'Application Type',
                'Customer ID',
                'Customer Account',
                'Contract Type',
                'Premise Type',
                'Industry',
                'Average Consumption',
                'Tariff Rate Code',
                'Tariff Rate',
                'Referral Code',
                'Meter Option',
                'SP Account No.',
                'Preferred Start Date',
                'Preferred Turn Off Date',
                'Self Read Option',
                'Source',
                'Category',
                'Deposit',
                'NRIC/FIN',
                'Salutation',
                'First Name',
                'Middle Name',
                'Last Name',
                'Full Name',
                'Mobile No.',
                'Phone No.',
                'Email',
                'UEN',
                'Company Name',
                'CP Customer ID',
                'CP NRIC/FIN',
                'CP Salutation',
                'CP First Name',
                'CP Full Name',
                'Premise Address Postal Code',
                'Premise Address Unit No.',
                'Premise Address Floor',
                'Premise Address House/Building No.',
                'Premise Address Building Name',
                'Premise Address Street',
                'Premise Address City',
                'Premise Address Country',
                'Mailing Address Postal Code',
                'Mailing Address Unit No.',
                'Mailing Address Floor',
                'Mailing Address House/Building No.',
                'Mailing Address Building Name',
                'Mailing Address Street',
                'Mailing Address City',
                'Mailing Address Country',
                'Refund Address Postal Code',
                'Refund Address Unit No.',
                'Refund Address Floor',
                'Refund Address House/Building No.',
                'Refund Address Building Name',
                'Refund Address Street',
                'Refund Address City',
                'Refund Address Country',
                'Remarks',
                'Status',
                'Termination Reason',
                'Referral Source',
                'Indicate',
                'SP Account Holder',
                'E-Billing',
                'Agency',
                'Sales Representative',
                'Partner Code',
                'Channel',
                'Location Code',
                'Renewal Start Date',
                'Lock-In Date',
                'Payment Mode',
                'Created Date/Time',
                'Promotion Code',
                'Energy Rate',
                'Conditional Discounts',
            ],
            'column-format' => [],
        ];

        // populate all column-format
        foreach ($report['headers'] as $key => $header) {
            $report['column-format'][$key] = ['wch' => \strlen($header) + 3];
        }
        // populate all column-format

        if (null !== $utcStartDate && null !== $utcEndDate) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('applicationRequest.dateCreated', $expr->literal($utcStartDate->format('c'))),
                    $expr->lte('applicationRequest.dateCreated', $expr->literal($utcEndDate->format('c')))
                )
            );
        } elseif (null !== $utcEndDate) {
            $qb->andWhere($expr->lte('applicationRequest.dateCreated', $expr->literal($utcEndDate->format('c'))));
        } elseif (null !== $utcStartDate) {
            $qb->andWhere($expr->gte('applicationRequest.dateCreated', $expr->literal($utcStartDate->format('c'))));
        }

        if (null !== $status) {
            $qb->andWhere($expr->in('applicationRequest.status', ':status'));
        }

        if (null !== $selfApplication) {
            $qb->andWhere($expr->eq('applicationRequest.selfApplication', ':selfApplication'));
        }

        if (null !== $source) {
            $qb->andWhere($expr->in('applicationRequest.source', ':source'));
        }

        if (null !== $tariffRate) {
            $qb->leftJoin('applicationRequest.tariffRate', 'tariffRate')
                ->andWhere($expr->in('tariffRate.isBasedOn', ':tariffRate'));
        }

        if (null !== $utcPreferredStartDateFrom && null !== $utcPreferredStartDateTo) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('applicationRequest.preferredStartDate', $expr->literal($utcPreferredStartDateFrom->format('c'))),
                    $expr->lte('applicationRequest.preferredStartDate', $expr->literal($utcPreferredStartDateTo->format('c')))
                )
            );
        } elseif (null !== $utcPreferredStartDateTo) {
            $qb->andWhere($expr->lte('applicationRequest.preferredStartDate', $expr->literal($utcPreferredStartDateTo->format('c'))));
        } elseif (null !== $utcPreferredStartDateFrom) {
            $qb->andWhere($expr->gte('applicationRequest.preferredStartDate', $expr->literal($utcPreferredStartDateFrom->format('c'))));
        }

        if (null !== $utcPreferredEndDateFrom && null !== $utcPreferredEndDateTo) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('applicationRequest.preferredEndDate', $expr->literal($utcPreferredEndDateFrom->format('c'))),
                    $expr->lte('applicationRequest.preferredEndDate', $expr->literal($utcPreferredEndDateTo->format('c')))
                )
            );
        } elseif (null !== $utcPreferredEndDateTo) {
            $qb->andWhere($expr->lte('applicationRequest.preferredEndDate', $expr->literal($utcPreferredEndDateTo->format('c'))));
        } elseif (null !== $utcPreferredEndDateFrom) {
            $qb->andWhere($expr->gte('applicationRequest.preferredEndDate', $expr->literal($utcPreferredEndDateFrom->format('c'))));
        }

        if (null !== $applicationRequestNumber) {
            $qb->andWhere($expr->eq('applicationRequest.applicationRequestNumber', ':applicationRequestNumber'));
        }

        if (null !== $type) {
            $qb->andWhere($expr->in('applicationRequest.type', ':type'));
        }

        if (null !== $customerType) {
            $qb->andWhere($expr->in('applicationRequest.customerType', ':customerType'));
        }

        if (null !== $contractType) {
            $qb->andWhere($expr->eq('applicationRequest.contractType', ':contractType'));
        }

        if (null !== $category) {
            $qb->andWhere($expr->eq('applicationRequest.customerType', ':category'));
        }

        if (null !== $contractSubType) {
            $qb->andWhere($expr->in('applicationRequest.contractSubtype', ':contractSubtype'));
        }

        if (null !== $utcCreatedDateFrom && null !== $utcCreatedDateTo) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('applicationRequest.dateCreated', $expr->literal($utcCreatedDateFrom->format('c'))),
                    $expr->lte('applicationRequest.dateCreated', $expr->literal($utcCreatedDateTo->format('c')))
                )
            );
        } elseif (null !== $utcCreatedDateTo) {
            $qb->andWhere($expr->lte('applicationRequest.dateCreated', $expr->literal($utcCreatedDateTo->format('c'))));
        } elseif (null !== $utcCreatedDateFrom) {
            $qb->andWhere($expr->gte('applicationRequest.dateCreated', $expr->literal($utcCreatedDateFrom->format('c'))));
        }

        if (null !== $channel) {
            $qb->andWhere($expr->in('applicationRequest.source', ':channel'));
        }

        if (null !== $agency) {
            $tsvectorSubquery = $this->entityManager->createQueryBuilder()
                ->select('tsvector_concat(applicationRequest.keywords, coalesce(customerAccountAgency.keywords, \'\'), coalesce(agencyCorporationDetails.keywords, \'\'), coalesce(agencyPersonDetails.keywords, \'\'))')
                ->from(ApplicationRequest::class, 'appReq')
                ->leftJoin('applicationRequest.acquiredFrom', 'customerAccountAgency')
                ->leftJoin('customerAccountAgency.corporationDetails', 'agencyCorporationDetails')
                ->leftJoin('customerAccountAgency.personDetails', 'agencyPersonDetails')
                ->andWhere($expr->andX(
                    $expr->eq('appReq', 'applicationRequest')
                ))
                ->getDQL();

            $qb->leftJoin('applicationRequest.acquiredFrom', 'agency')
                ->leftJoin('agency.personDetails', 'agencyDetails')
                ->leftJoin('agency.corporationDetails', 'agencyCorporation')
                ->andWhere($expr->orX(
                    $expr->andX(
                        $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                            , $tsvectorSubquery, 'agency'), $expr->literal(true))
                    )
                ));
        }

        if (null !== $salesRep) {
            $tsvectorSubquery = $this->entityManager->createQueryBuilder()
                ->select('tsvector_concat(applicationRequest.keywords, coalesce(customerAccountSalesRep.keywords, \'\'), coalesce(salesRepPersonDetails.keywords, \'\'))')
                ->from(ApplicationRequest::class, 'applicationReq')
                ->leftJoin('applicationRequest.creator', 'salesRepId')
                ->leftJoin('salesRepId.customerAccount', 'customerAccountSalesRep')
                ->leftJoin('customerAccountSalesRep.personDetails', 'salesRepPersonDetails')
                ->andWhere($expr->andX(
                    $expr->eq('applicationReq', 'applicationRequest')
                ))
                ->getDQL();

            $qb->leftJoin('applicationRequest.creator', 'salesRep')
                ->leftJoin('salesRep.customerAccount', 'salesRepAccount')
                ->leftJoin('salesRepAccount.personDetails', 'salesRepDetails')
                ->andWhere($expr->orX($expr->andX(
                    $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                        , $tsvectorSubquery, 'salesRep'), $expr->literal(true))
                ),
                    $expr->andX(
                        $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                            , 'applicationRequest.salesRepName', 'salesRep'), $expr->literal(true))
                    )));
        }

        if (null !== $accountNumber) {
            $qb->andWhere($expr->orX($expr->eq('applicationRequest.msslAccountNumber', ':accountNumber'),
                $expr->eq('applicationRequest.ebsAccountNumber', ':accountNumber')));
        }

        if (null !== $partnerCode) {
            $qb->leftJoin('applicationRequest.acquiredFrom', 'partner')
                ->andWhere($expr->eq('partner.accountNumber', ':partnerCode'));
        }

        if (null !== $referralCode) {
            $qb->andWhere($expr->eq('applicationRequest.referralCode', ':referralCode'));
        }

        $applicationRequests = [];

        if (!empty($this->documentManager->getConnection()->getServer()) && $this->documentManager->getConnection()->connect()) {
            $queryResult = $qb->select('applicationRequest.applicationRequestNumber')
                ->setParameters($queryParams)
                ->orderBy('applicationRequest.dateCreated', 'ASC')
                ->getQuery()
                ->useResultCache(false)
                ->getResult();

            $applicationRequestIds = [];

            foreach ($queryResult as $item) {
                $applicationRequestIds[] = $item['applicationRequestNumber'];
            }

            /**
             * @var ApplicationRequestReport[]
             */
            $applicationRequests = $this->documentManager->createQueryBuilder(ApplicationRequestReport::class)
                ->eagerCursor(true)
                ->field('applicationRequestId')->in($applicationRequestIds)
                ->sort('dateCreated', 'asc')
                ->refresh()
                ->getQuery()
                ->execute();

            foreach ($applicationRequests as $applicationRequest) {
                $data = [];
                $reportHeaders = \array_diff($report['headers'], $this->exclusionHeadersList['applicationRequest']);
                foreach ($reportHeaders as $column => $key) {
                    $data[$key] = $this->mapCacheDbApplicationRequestData($applicationRequest, $key);
                    if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                        $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                    }
                }
                $report['data'][] = $data;
            }
        } else {
            $applicationRequests = $qb->setParameters($queryParams)
                ->orderBy('applicationRequest.dateCreated', 'ASC')
                ->getQuery()
                ->useResultCache(false)
                ->getResult();

            foreach ($applicationRequests as $applicationRequest) {
                $data = [];
                $reportHeaders = \array_diff($report['headers'], $this->exclusionHeadersList['applicationRequest']);
                $currentColumnPosition = 0;
                $currentIncentiveCount = 1;
                foreach ($reportHeaders as $column => $key) {
                    $data[$key] = $this->mapApplicationRequestData($applicationRequest, $key);
                    if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                        $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                    }
                    $currentColumnPosition = $column;
                }

                if ('unionpower' === $this->appProfile) {
                    if (null !== $applicationRequest->getTariffRate() && $applicationRequest->getTariffRate()->getTerms()) {
                        if (null !== $applicationRequest->getTariffRate()->getTerms()->getFreeGiftList()) {
                            $freeGifts = $applicationRequest->getTariffRate()->getTerms()->getFreeGiftList()->getItemListElement();
                            foreach ($freeGifts as $freeGift) {
                                if ($freeGift instanceof FreeGiftListItem) {
                                    $columnHeader = "Details of Incentive {$currentIncentiveCount}";
                                    $report['column-format'][$currentColumnPosition] = ['wch' => \strlen($columnHeader) + 3];
                                    $data[$columnHeader] = null !== $freeGift->getItem()->getDescription()
                                        ? \strip_tags(\html_entity_decode($freeGift->getItem()->getDescription())) : null;
                                    if (\strlen((string) $data[$columnHeader]) + 3 > $report['column-format'][$currentColumnPosition]['wch']) {
                                        $report['column-format'][$currentColumnPosition]['wch'] = \strlen($data[$columnHeader]) + 3;
                                    }
                                    ++$currentColumnPosition;
                                    ++$currentIncentiveCount;
                                }
                            }
                        }
                    }

                    if (null !== $applicationRequest->getAddonServices()) {
                        if (!empty($applicationRequest->getAddonServices())) {
                            $addOnServices = $applicationRequest->getAddonServices();
                            foreach ($addOnServices as $addOnService) {
                                $columnHeader = "Details of Incentive {$currentIncentiveCount}";
                                $report['column-format'][$currentColumnPosition] = ['wch' => \strlen($columnHeader) + 3];
                                $data[$columnHeader] = null !== $addOnService->getDescription()
                                    ? \strip_tags(\html_entity_decode($addOnService->getDescription())) : null;
                                if (\strlen((string) $data[$columnHeader]) + 3 > $report['column-format'][$currentColumnPosition]['wch']) {
                                    $report['column-format'][$currentColumnPosition]['wch'] = \strlen($data[$columnHeader]) + 3;
                                }
                                ++$currentColumnPosition;
                                ++$currentIncentiveCount;
                            }
                        }
                    }
                }

                $report['data'][] = $data;
            }
        }

        return $report;
    }

    /**
     * @param array $params
     *
     * @return array|null
     */
    public function createContractReport(array $params)
    {
        $queryParams = [];

        $startDate = $endDate = $lockInPeriodTo = $lockInPeriodFrom = $endDateTo = $endDateFrom =
        $startDateFrom = $startDateTo = $utcEndDate = $utcStartDate = $utcLockInPeriodTo = $utcLockInPeriodFrom =
        $utcEndDateTo = $utcEndDateFrom = $utcStartDateFrom = $utcStartDateTo = $contractType = $category = $status =
        $source = $meterType = null;

        $qb = $this->entityManager->getRepository(Contract::class)->createQueryBuilder('contract');
        $expr = $qb->expr();
        $qb->where(
            $expr->isNotNull('contract.contractNumber')
        );

        if (\array_key_exists('type', $params)) {
            $contractType = $queryParams['contractType'] = $params['type'];
        }

        if (\array_key_exists('customerType', $params)) {
            $category = $queryParams['category'] = $params['customerType'];
        }

        if (\array_key_exists('endDate[exists]', $params)) {
            $status = $params['endDate[exists]'];
        }

        if (\array_key_exists('source', $params)) {
            $source = $queryParams['source'] = $params['source'];
        }

        if (\array_key_exists('meterType', $params)) {
            $meterType = $queryParams['meterType'] = $params['meterType'];
        }

        if (\array_key_exists('lockInDate[after]', $params) && null !== $params['lockInDate[after]']) {
            $lockInPeriodFrom = new \DateTime($params['lockInDate[after]']);
            $this->timezone = $lockInPeriodFrom->getTimezone();
            $utcLockInPeriodFrom = clone $lockInPeriodFrom;
            $utcLockInPeriodFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('lockInDate[before]', $params) && null !== $params['lockInDate[before]']) {
            $lockInPeriodTo = new \DateTime($params['lockInDate[before]']);
            $this->timezone = $lockInPeriodTo->getTimezone();
            $utcLockInPeriodTo = clone $lockInPeriodTo;
            $utcLockInPeriodTo->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('startDate', $params) && null !== $params['startDate']) {
            $startDate = new \DateTime($params['startDate']);
            $this->timezone = $startDate->getTimezone();
            $utcStartDate = clone $startDate;
            $utcStartDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('endDate', $params) && null !== $params['endDate']) {
            $endDate = new \DateTime($params['endDate']);
            $this->timezone = $endDate->getTimezone();
            $utcEndDate = clone $endDate;
            $utcEndDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('startDate[after]', $params) && null !== $params['startDate[after]']) {
            $startDateFrom = new \DateTime($params['startDate[after]']);
            $this->timezone = $startDateFrom->getTimezone();
            $utcStartDateFrom = clone $startDateFrom;
            $utcStartDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('startDate[before]', $params) && null !== $params['startDate[before]']) {
            $startDateTo = new \DateTime($params['startDate[before]']);
            $this->timezone = $startDateTo->getTimezone();
            $utcStartDateTo = clone $startDateTo;
            $utcStartDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('endDate[after]', $params) && null !== $params['endDate[after]']) {
            $endDateFrom = new \DateTime($params['endDate[after]']);
            $this->timezone = $endDateFrom->getTimezone();
            $utcEndDateFrom = clone $endDateFrom;
            $utcEndDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('endDate[before]', $params) && null !== $params['endDate[before]']) {
            $endDateTo = new \DateTime($params['endDate[before]']);
            $this->timezone = $endDateTo->getTimezone();
            $utcEndDateTo = clone $endDateTo;
            $utcEndDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        $report = [
            'data' => [],
            'filename' => \sprintf('%s_%s.xlsx', 'Contract Report', (new \DateTime('now'))->format('d-M')),
            'headers' => [
                'Customer ID',
                'Customer Name',
                'Customer Status',
                'Customer Account',
                'Contract Status',
                'Contract Type',
                'Contract Start Date',
                'Contract End Date',
                'Lock-In Date',
                'Meter Type',
                'MSSL Number',
                'EBS Number',
                'Promotion Code',
                'Promotion Name',
                'Category',
                'Premise Address Postal Code',
                'Premise Address Unit No.',
                'Premise Address Floor',
                'Premise Address House/Building No.',
                'Premise Address Building Name',
                'Premise Address Street',
                'Premise Address City',
                'Premise Address Country',
                'Mailing Address Postal Code',
                'Mailing Address Unit No.',
                'Mailing Address Floor',
                'Mailing Address House/Building No.',
                'Mailing Address Building Name',
                'Mailing Address Street',
                'Mailing Address City',
                'Mailing Address Country',
                'Payment Method',
                'Created Date/Time',
            ],
            'column-format' => [],
        ];

        // populate all column-format
        foreach ($report['headers'] as $key => $header) {
            $report['column-format'][$key] = ['wch' => \strlen($header) + 3];
        }
        // populate all column-format

        if (null !== $utcStartDate && null !== $utcEndDate) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('contract.dateCreated', $expr->literal($utcStartDate->format('c'))),
                    $expr->lte('contract.dateCreated', $expr->literal($utcEndDate->format('c')))
                )
            );
        } elseif (null !== $utcEndDate) {
            $qb->andWhere($expr->lte('contract.dateCreated', $expr->literal($utcEndDate->format('c'))));
        } elseif (null !== $utcStartDate) {
            $qb->andWhere($expr->gte('contract.dateCreated', $expr->literal($utcStartDate->format('c'))));
        }

        if (null !== $utcLockInPeriodFrom && null !== $utcLockInPeriodTo) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('contract.lockInDate', $expr->literal($utcLockInPeriodFrom->format('c'))),
                    $expr->lte('contract.lockInDate', $expr->literal($utcLockInPeriodTo->format('c')))
                )
            );
        } elseif (null !== $utcLockInPeriodTo) {
            $qb->andWhere($expr->lte('contract.lockInDate', $expr->literal($utcLockInPeriodTo->format('c'))));
        } elseif (null !== $utcLockInPeriodFrom) {
            $qb->andWhere($expr->gte('contract.lockInDate', $expr->literal($utcLockInPeriodFrom->format('c'))));
        }

        if (null !== $utcStartDateFrom && null !== $utcStartDateTo) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('contract.startDate', $expr->literal($utcStartDateFrom->format('c'))),
                    $expr->lte('contract.startDate', $expr->literal($utcStartDateTo->format('c')))
                )
            );
        } elseif (null !== $utcStartDateTo) {
            $qb->andWhere($expr->lte('contract.startDate', $expr->literal($utcStartDateTo->format('c'))));
        } elseif (null !== $utcStartDateFrom) {
            $qb->andWhere($expr->gte('contract.startDate', $expr->literal($utcStartDateFrom->format('c'))));
        }

        if (null !== $utcEndDateFrom && null !== $utcEndDateTo) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('contract.endDate', $expr->literal($utcEndDateFrom->format('c'))),
                    $expr->lte('contract.endDate', $expr->literal($utcEndDateTo->format('c')))
                )
            );
        } elseif (null !== $utcEndDateTo) {
            $qb->andWhere($expr->lte('contract.endDate', $expr->literal($utcEndDateTo->format('c'))));
        } elseif (null !== $utcEndDateFrom) {
            $qb->andWhere($expr->gte('contract.endDate', $expr->literal($utcEndDateFrom->format('c'))));
        }

        if (null !== $contractType) {
            $qb->andWhere($expr->in('contract.type', ':contractType'));
        }

        if (null !== $category) {
            $qb->andWhere($expr->in('contract.customerType', ':category'));
        }

        if (null !== $status) {
            if (true === $status) {
                $qb->andWhere($expr->isNotNull('contract.endDate'));
            } else {
                $qb->andWhere($expr->isNull('contract.endDate'));
            }
        }

        if (null !== $source) {
            $qb->andWhere($expr->in('contract.source', ':source'));
        }

        if (null !== $meterType) {
            $qb->andWhere($expr->in('contract.meterType', ':meterType'));
        }

        $contracts = [];

        if (!empty($this->documentManager->getConnection()->getServer()) && $this->documentManager->getConnection()->connect()) {
            $queryResult = $qb->select('contract.contractNumber')
                ->setParameters($queryParams)
                ->orderBy('contract.dateCreated', 'ASC')
                ->getQuery()
                ->useResultCache(false)
                ->getResult();

            $contractNumbers = [];

            foreach ($queryResult as $item) {
                $contractNumbers[] = $item['contractNumber'];
            }

            /**
             * @var ContractReport[]
             */
            $contracts = $this->documentManager->createQueryBuilder(ContractReport::class)
                ->eagerCursor(true)
                ->field('contractNumber')->in($contractNumbers)
                ->sort('dateCreated', 'asc')
                ->refresh()
                ->getQuery()
                ->execute();

            foreach ($contracts as $contract) {
                $data = [];
                $reportHeaders = \array_diff($report['headers'], $this->exclusionHeadersList['contract']);
                foreach ($reportHeaders as $column => $key) {
                    $data[$key] = $this->mapCacheDbContractData($contract, $key);
                    if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                        $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                    }
                }
                $report['data'][] = $data;
            }
        } else {
            $contracts = $qb->setParameters($queryParams)
                ->orderBy('contract.dateCreated', 'ASC')
                ->getQuery()
                ->useResultCache(false)
                ->getResult();

            foreach ($contracts as $contract) {
                $data = [];
                $reportHeaders = \array_diff($report['headers'], $this->exclusionHeadersList['contract']);
                foreach ($reportHeaders as $column => $key) {
                    $data[$key] = $this->mapContractData($contract, $key);
                    if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                        $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                    }
                }
                $report['data'][] = $data;
            }
        }

        return $report;
    }

    /**
     * @param array $params
     *
     * @return array|null
     */
    public function createCreditsActionReport(array $params)
    {
        $queryParams = $contractActions = $redemptionActions = [];
        $createdDateTo = $createdDateFrom = $transactionType = $customerName = $startDate = $endDate =
        $contractNumber = $pointType = null;

        $utcCreatedDateTo = $utcCreatedDateFrom = $utcStartDate = $utcEndDate = null;

        $contractRepository = $this->entityManager->getRepository(EarnContractCreditsAction::class);

        //region Parameter Instantiation
        if (\array_key_exists('transactionType', $params)) {
            $transactionType = $params['transactionType'];
        }
        if (\array_key_exists('contractNumber', $params)) {
            $contractNumber = $queryParams['contractNumber'] = $params['contractNumber'];
        }
        if (\array_key_exists('customerName', $params)) {
            if ($contractRepository instanceof EarnContractCreditsActionRepository) {
                $customerNameTsQuery = $contractRepository->getKeywordTsquery([$params['customerName']], true);

                $customerName = $queryParams['customerName'] = $customerNameTsQuery;
            }
        }
        if (\array_key_exists('pointType', $params)) {
            $pointType = $queryParams['pointType'] = $params['pointType'];
        }

        if (\array_key_exists('dateCreated[after]', $params) && null !== $params['dateCreated[after]']) {
            $createdDateFrom = new \DateTime($params['dateCreated[after]']);
            $this->timezone = $createdDateFrom->getTimezone();
            $utcCreatedDateFrom = clone $createdDateFrom;
            $utcCreatedDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('dateCreated[before]', $params) && null !== $params['dateCreated[before]']) {
            $createdDateTo = new \DateTime($params['dateCreated[before]']);
            $this->timezone = $createdDateTo->getTimezone();
            $utcCreatedDateTo = clone $createdDateTo;
            $utcCreatedDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('startDate', $params) && null !== $params['startDate']) {
            $startDate = new \DateTime($params['startDate']);
            $this->timezone = $startDate->getTimezone();
            $utcStartDate = clone $startDate;
            $utcStartDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('endDate', $params) && null !== $params['endDate']) {
            $endDate = new \DateTime($params['endDate']);
            $this->timezone = $endDate->getTimezone();
            $utcEndDate = clone $endDate;
            $utcEndDate->setTimezone(new \DateTimeZone('UTC'));
        }

        $qbRedemptionActions = $this->entityManager->getRepository(RedeemCreditsAction::class)->createQueryBuilder('redeemCreditsAction');
        $qbContractActions = $contractRepository->createQueryBuilder('contractCreditsAction');
        $exprRedemptionActions = $qbRedemptionActions->expr();
        $exprContractActions = $qbContractActions->expr();
        $report = [
            'data' => [],
            'filename' => \sprintf('%s_%s.xlsx', 'Point_Histories Report', (new \DateTime('now'))->format('d-M')),
            'headers' => [
                'Customer ID',
                'Customer Name',
                'Customer Account',
                'MSSL No',
                'EBS No',
                'Transaction Type',
                'Points',
                'Description',
                'Created Date',
            ],
            'column-format' => [],
        ];

        // populate all column-format
        foreach ($report['headers'] as $key => $header) {
            $report['column-format'][$key] = ['wch' => \strlen($header) + 3];
        }
        // populate all column-format

        if (null === $transactionType || (null !== $transactionType && 'ACC' === $transactionType)) {
            $qbContractActions->leftJoin('contractCreditsAction.object', 'contract')
                ->leftJoin('contract.customer', 'customer');

            if (null !== $utcStartDate && null !== $utcEndDate) {
                $qbContractActions->where(
                    $exprContractActions->andX(
                        $exprContractActions->gte('contractCreditsAction.dateCreated', $exprContractActions->literal($utcStartDate->format('c'))),
                        $exprContractActions->lte('contractCreditsAction.dateCreated', $exprContractActions->literal($utcEndDate->format('c')))
                    )
                );
            } elseif (null !== $utcEndDate) {
                $qbContractActions->andWhere($exprContractActions->lte('contractCreditsAction.dateCreated', $exprContractActions->literal($utcEndDate->format('c'))));
            } elseif (null !== $utcStartDate) {
                $qbContractActions->andWhere($exprContractActions->gte('contractCreditsAction.dateCreated', $exprContractActions->literal($utcStartDate->format('c'))));
            }

            if (null !== $utcCreatedDateFrom && null !== $utcCreatedDateTo) {
                $qbContractActions->where(
                    $exprContractActions->andX(
                        $exprContractActions->gte('contractCreditsAction.dateCreated', $exprContractActions->literal($utcCreatedDateFrom->format('c'))),
                        $exprContractActions->lte('contractCreditsAction.dateCreated', $exprContractActions->literal($utcCreatedDateTo->format('c')))
                    )
                );
            } elseif (null !== $utcCreatedDateTo) {
                $qbContractActions->andWhere($exprContractActions->lte('contractCreditsAction.dateCreated', $exprContractActions->literal($utcCreatedDateTo->format('c'))));
            } elseif (null !== $utcCreatedDateFrom) {
                $qbContractActions->andWhere($exprContractActions->gte('contractCreditsAction.dateCreated', $exprContractActions->literal($utcCreatedDateFrom->format('c'))));
            }

            if (null !== $customerName) {
                $tsvectorSubquery = $this->entityManager->createQueryBuilder()
                    ->select('tsvector_concat(coalesce(contractActionCustomer.keywords, \'\'), coalesce(contractActionCorporation.keywords, \'\'), coalesce(contractActionPerson.keywords, \'\'))')
                    ->from(EarnContractCreditsAction::class, 'contractAction')
                    ->leftJoin('contractCreditsAction.object', 'contractActionContract')
                    ->leftJoin('contractActionContract.customer', 'contractActionCustomer')
                    ->leftJoin('contractActionCustomer.corporationDetails', 'contractActionCorporation')
                    ->leftJoin('contractActionCustomer.personDetails', 'contractActionPerson')
                    ->andWhere($exprContractActions->andX(
                        $exprContractActions->eq('contractAction', 'contractCreditsAction')
                    ))
                    ->getDQL();

                $qbContractActions->leftJoin('customer.personDetails', 'customerDetails')
                    ->leftJoin('customer.corporationDetails', 'corporationDetails')
                    ->leftJoin('contract.contactPerson', 'contactPerson')
                    ->leftJoin('contactPerson.personDetails', 'contactPersonDetails')
                    ->leftJoin('contactPerson.corporationDetails', 'contactPersonCorporationDetails')
                    ->andWhere($exprContractActions->orX(
                        $exprContractActions->andX(
                            $exprContractActions->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                                , $tsvectorSubquery, 'customerName'), $exprContractActions->literal(true))
                        )
                    ));
            }

            if (null !== $pointType) {
                $qbContractActions->leftJoin('contractCreditsAction.scheme', 'creditsScheme')
                    ->andWhere($exprContractActions->eq('creditsScheme.schemeId', ':pointType'));
            }

            if (null !== $contractNumber) {
                $qbContractActions->andWhere($exprContractActions->eq('contract.contractNumber', ':contractNumber'));
            }

            $contractActions = $qbContractActions->setParameters($queryParams)
                ->orderBy('contractCreditsAction.dateCreated', 'ASC')
                ->getQuery()
                ->getResult();
        }

        if (null === $transactionType || (null !== $transactionType && 'RED' === $transactionType)) {
            $qbRedemptionActions->leftJoin('redeemCreditsAction.object', 'contract')
                ->leftJoin('contract.customer', 'customer');

            if (null !== $utcStartDate && null !== $utcEndDate) {
                $qbRedemptionActions->where(
                    $exprRedemptionActions->andX(
                        $exprRedemptionActions->gte('redeemCreditsAction.dateCreated', $exprRedemptionActions->literal($utcStartDate->format('c'))),
                        $exprRedemptionActions->lte('redeemCreditsAction.dateCreated', $exprRedemptionActions->literal($utcEndDate->format('c')))
                    )
                );
            } elseif (null !== $utcEndDate) {
                $qbRedemptionActions->andWhere($exprRedemptionActions->lte('redeemCreditsAction.dateCreated', $exprRedemptionActions->literal($utcEndDate->format('c'))));
            } elseif (null !== $utcStartDate) {
                $qbRedemptionActions->andWhere($exprRedemptionActions->gte('redeemCreditsAction.dateCreated', $exprRedemptionActions->literal($utcStartDate->format('c'))));
            }

            if (null !== $utcCreatedDateFrom && null !== $utcCreatedDateTo) {
                $qbRedemptionActions->where(
                    $exprRedemptionActions->andX(
                        $exprRedemptionActions->gte('redeemCreditsAction.dateCreated', $exprRedemptionActions->literal($utcCreatedDateFrom->format('c'))),
                        $exprRedemptionActions->lte('redeemCreditsAction.dateCreated', $exprRedemptionActions->literal($utcCreatedDateTo->format('c')))
                    )
                );
            } elseif (null !== $utcCreatedDateTo) {
                $qbRedemptionActions->andWhere($exprRedemptionActions->lte('redeemCreditsAction.dateCreated', $exprRedemptionActions->literal($utcCreatedDateTo->format('c'))));
            } elseif (null !== $utcCreatedDateFrom) {
                $qbRedemptionActions->andWhere($exprRedemptionActions->gte('redeemCreditsAction.dateCreated', $exprRedemptionActions->literal($utcCreatedDateFrom->format('c'))));
            }

            if (null !== $customerName) {
                $tsvectorSubquery = $this->entityManager->createQueryBuilder()
                    ->select('tsvector_concat(coalesce(customerAccount.keywords, \'\'), coalesce(customerAccountCorporation.keywords, \'\'), coalesce(customerAccountPersonDetails.keywords, \'\'))')
                    ->from(RedeemCreditsAction::class, 'redeemAction')
                    ->leftJoin('redeemCreditsAction.object', 'actionContract')
                    ->leftJoin('actionContract.customer', 'customerAccount')
                    ->leftJoin('customerAccount.corporationDetails', 'customerAccountCorporation')
                    ->leftJoin('customerAccount.personDetails', 'customerAccountPersonDetails')
                    ->andWhere($exprRedemptionActions->andX(
                        $exprRedemptionActions->eq('redeemAction', 'redeemCreditsAction')
                    ))
                    ->getDQL();

                $qbRedemptionActions->leftJoin('customer.personDetails', 'customerDetails')
                    ->leftJoin('customer.corporationDetails', 'corporationDetails')
                    ->leftJoin('contract.contactPerson', 'contactPerson')
                    ->leftJoin('contactPerson.personDetails', 'contactPersonDetails')
                    ->leftJoin('contactPerson.corporationDetails', 'contactPersonCorporationDetails')
                    ->andWhere($exprRedemptionActions->orX(
                        $exprRedemptionActions->andX(
                            $exprRedemptionActions->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                                , $tsvectorSubquery, 'customerName'), $exprRedemptionActions->literal(true))
                        )
                    ));
            }

            if (null !== $contractNumber) {
                $qbRedemptionActions->andWhere($exprRedemptionActions->eq('contract.contractNumber', ':contractNumber'));
            }

            $redemptionActions = $qbRedemptionActions->setParameters($queryParams)
                ->orderBy('redeemCreditsAction.dateCreated', 'ASC')
                ->getQuery()
                ->getResult();
        }

        $creditsActions = \array_merge($contractActions, $redemptionActions);

        foreach ($creditsActions as $creditAction) {
            $data = [];
            $reportHeaders = \array_diff($report['headers'], $this->exclusionHeadersList['creditsAction']);
            foreach ($reportHeaders as $column => $key) {
                $data[$key] = $this->mapCreditsActionReportData($creditAction, $key);
                if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                    $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                }
            }
            $report['data'][] = $data;
        }

        return $report;
    }

    /**
     * @param array $params
     *
     * @return array|null
     */
    public function createCustomerAccountReport(array $params)
    {
        $queryParams = $categoryParamNames = [];
        $createdDateTo = $createdDateFrom = $email = $customerNumber = $customerName = $categories = $type =
        $blacklistStatus = $status = $source = $referralCode = $utcCreatedDateTo = $utcCreatedDateFrom = $utcEndDate =
        $utcStartDate = $startDate = $endDate = null;

        if (\array_key_exists('email', $params)) {
            $email = $queryParams['userEmail'] = \json_encode(\strtolower($params['email']));
        }

        if (\array_key_exists('customerNumber', $params)) {
            $customerNumber = $queryParams['customerNumber'] = $params['customerNumber'];
        }

        if (\array_key_exists('customerName', $params)) {
            $customerName = $queryParams['customerName'] = $params['customerName'];
        }

        if (\array_key_exists('categories', $params)) {
            $categories = $params['categories'];
        } else {
            $categories = ['NONCUSTOMER', 'CONTACT_PERSON', 'CUSTOMER'];
        }

        if (\is_array($categories)) {
            foreach ($categories as $category) {
                $cat = \strtolower($category);
                \array_push($categoryParamNames, "{$cat}_category");
                $queryParams["{$cat}_category"] = \json_encode($category);
            }
        }

        if (\array_key_exists('type', $params)) {
            $type = $queryParams['type'] = $params['type'];
        }

        if (\array_key_exists('status', $params)) {
            $status = $queryParams['status'] = $params['status'];
        }

        if (\array_key_exists('source', $params)) {
            $source = $queryParams['source'] = $params['source'];
        }

        if (\array_key_exists('referralCode', $params)) {
            $referralCode = $queryParams['referralCode'] = $params['referralCode'];
        }

        if (\array_key_exists('dateBlacklisted[exists]', $params)) {
            $blacklistStatus = $params['dateBlacklisted[exists]'];
        }

        if (\array_key_exists('dateCreated[after]', $params) && null !== $params['dateCreated[after]']) {
            $createdDateFrom = new \DateTime($params['dateCreated[after]']);
            $this->timezone = $createdDateFrom->getTimezone();
            $utcCreatedDateFrom = clone $createdDateFrom;
            $utcCreatedDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('dateCreated[before]', $params) && null !== $params['dateCreated[before]']) {
            $createdDateTo = new \DateTime($params['dateCreated[before]']);
            $this->timezone = $createdDateTo->getTimezone();
            $utcCreatedDateTo = clone $createdDateTo;
            $utcCreatedDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('startDate', $params) && null !== $params['startDate']) {
            $startDate = new \DateTime($params['startDate']);
            $this->timezone = $startDate->getTimezone();
            $utcStartDate = clone $startDate;
            $utcStartDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('endDate', $params) && null !== $params['endDate']) {
            $endDate = new \DateTime($params['endDate']);
            $this->timezone = $endDate->getTimezone();
            $utcEndDate = clone $endDate;
            $utcEndDate->setTimezone(new \DateTimeZone('UTC'));
        }

        $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customer');
        $qb->leftJoin('customer.personDetails', 'customerDetails')
            ->leftJoin('customer.corporationDetails', 'corporationDetails');
        $expr = $qb->expr();
        $report = [
            'data' => [],
            'filename' => \sprintf('%s_%s.xlsx', 'Customer Report', (new \DateTime('now'))->format('d-M')),
            'headers' => [
                'Customer ID',
                'Customer Category',
                'Customer Type',
                'Source',
                'Company Name',
                'Salutation',
                'First Name',
                'Middle Name',
                'Last Name',
                'Full Name',
                'UEN / NRIC',
                'Preferred Contact Method',
                'Mobile No',
                'Phone No',
                'Fax No',
                'Email',
                'Referral Code',
                'Gender',
                'Marital Status',
                'Date of Birth',
                'Place of Birth',
                'Date of Death',
                'Nationality',
                'Race',
                'Languages',
                'Preferred Language',
                'Status',
                'Blacklisted',
                'Created Date/Time',
            ],
            'column-format' => [],
        ];

        // populate all column-format
        foreach ($report['headers'] as $key => $header) {
            $report['column-format'][$key] = ['wch' => \strlen($header) + 3];
        }
        // populate all column-format

        if (null !== $utcStartDate && null !== $utcEndDate) {
            $qb->where(
                $expr->andX(
                    $expr->gte('customer.dateCreated', $expr->literal($utcStartDate->format('c'))),
                    $expr->lte('customer.dateCreated', $expr->literal($utcEndDate->format('c')))
                )
            );
        } elseif (null !== $utcEndDate) {
            $qb->andWhere($expr->lte('customer.dateCreated', $expr->literal($utcEndDate->format('c'))));
        } elseif (null !== $utcStartDate) {
            $qb->andWhere($expr->gte('customer.dateCreated', $expr->literal($utcStartDate->format('c'))));
        }

        if (null !== $blacklistStatus) {
            if (true === $blacklistStatus || 'true' === $blacklistStatus) {
                $qb->andWhere($expr->isNotNull('customer.dateBlacklisted'));
            } else {
                $qb->andWhere($expr->isNull('customer.dateBlacklisted'));
            }
        }

        if (null !== $customerNumber) {
            $qb->andWhere($expr->eq('customer.customerNumber', ':customerNumber'));
        }

        if (null !== $referralCode) {
            $qb->andWhere($expr->eq('customer.referralCode', ':referralCode'));
        }

        if (null !== $type) {
            $qb->andWhere($expr->in('customer.type', ':type'));
        }

        if (null !== $status) {
            $qb->andWhere($expr->in('customer.status', ':status'));
        }

        if (null !== $source) {
            $qb->andWhere($expr->in('customer.source', ':source'));
        }

        if (null !== $categories) {
            $expressions = [];
            foreach ($categoryParamNames as $categoryParamName) {
                \array_push($expressions, $expr->eq(<<<"SQL"
                    jsonb_contains(CAST(customer.categories AS jsonb), :$categoryParamName)
SQL
                    , $expr->literal(true)));
            }

            $orExpressions = $expr->orX();
            foreach ($expressions as $expression) {
                $orExpressions->add($expression);
            }
            $qb->andWhere($orExpressions);
        }

        if (null !== $utcCreatedDateTo && null !== $utcCreatedDateFrom) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('customer.dateCreated', $expr->literal($utcCreatedDateFrom->format('c'))),
                    $expr->lte('customer.dateCreated', $expr->literal($utcCreatedDateTo->format('c')))
                )
            );
        } elseif (null !== $utcCreatedDateTo) {
            $qb->andWhere($expr->lte('customer.dateCreated', $expr->literal($utcCreatedDateTo->format('c'))));
        } elseif (null !== $utcCreatedDateFrom) {
            $qb->andWhere($expr->gte('customer.dateCreated', $expr->literal($utcCreatedDateFrom->format('c'))));
        }

        if (null !== $customerName) {
            $qb->andWhere($expr->orX(
                $expr->eq('customerDetails.name', ':customerName'),
                $expr->orX($expr->eq('corporationDetails.name', ':customerName'),
                    $expr->eq('corporationDetails.legalName', ':customerName'))
            ));
        }

        if (null !== $email) {
            $qb->leftJoin('customerDetails.contactPoints', 'customerContactPoint')
                ->leftJoin('corporationDetails.contactPoints', 'corporationContactPoint')
                ->andWhere($expr->andX(
                    $expr->orX(
                        $expr->eq(\sprintf(<<<'SQL'
                        jsonb_contains(CAST(lower(CAST(%s.%s AS text)) AS jsonb), :%s)
SQL
                            , 'customerContactPoint', 'emails', 'userEmail'),
                            $expr->literal(true)),
                        $expr->eq(\sprintf(<<<'SQL'
                        jsonb_contains(CAST(lower(CAST(%s.%s AS text)) AS jsonb), :%s)
SQL
                            , 'corporationContactPoint', 'emails', 'userEmail'),
                            $expr->literal(true))
                    )
                ));
        }

        $customerAccounts = $qb->setParameters($queryParams)
            ->orderBy('customer.dateCreated', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($customerAccounts as $customerAccount) {
            if (!empty(\array_intersect(['CONTACT_PERSON', 'CUSTOMER', 'NONCUSTOMER'], $customerAccount->getCategories()))) {
                $data = [];
                $reportHeaders = \array_diff($report['headers'], $this->exclusionHeadersList['customerAccount']);
                foreach ($reportHeaders as $column => $key) {
                    $data[$key] = $this->mapCustomerAccountReportData($customerAccount, $key);
                    if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                        $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                    }
                }
                $report['data'][] = $data;
            }
        }

        return $report;
    }

    /**
     * @param array $params
     *
     * @return array|null
     */
    public function createCustomerAccountRelationshipReport(array $params)
    {
        $queryParams = [];
        $validThrough = $validFrom = $customerId = $customerName = $relationshipType =
        $utcValidThrough = $utcValidFrom = $utcEndDate = $utcStartDate = $startDate = $endDate = null;

        $customerAccountRelationshipRepo = $this->entityManager->getRepository(CustomerAccountRelationship::class);

        if (\array_key_exists('type', $params)) {
            $relationshipType = $params['type'];
        }
        if (\array_key_exists('customerId', $params)) {
            $customerId = $queryParams['customerId'] = $params['customerId'];
        }

        if (\array_key_exists('customerName', $params)) {
            if ($customerAccountRelationshipRepo instanceof CustomerAccountRelationshipRepository) {
                $customerNameTsQuery = $customerAccountRelationshipRepo->getKeywordTsquery([$params['customerName']], true);

                $customerName = $queryParams['customerName'] = $customerNameTsQuery;
            }
        }

        if (\array_key_exists('validThrough', $params) && null !== $params['validThrough']) {
            $validThrough = new \DateTime($params['validThrough']);
            $this->timezone = $validThrough->getTimezone();
            $utcValidThrough = clone $validThrough;
            $utcValidThrough->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('validFrom', $params) && null !== $params['validFrom']) {
            $validFrom = new \DateTime($params['validFrom']);
            $this->timezone = $validFrom->getTimezone();
            $utcValidFrom = clone $validFrom;
            $utcValidFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('startDate', $params) && null !== $params['startDate']) {
            $startDate = new \DateTime($params['startDate']);
            $this->timezone = $startDate->getTimezone();
            $utcStartDate = clone $startDate;
            $utcStartDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('endDate', $params) && null !== $params['endDate']) {
            $endDate = new \DateTime($params['endDate']);
            $this->timezone = $endDate->getTimezone();
            $utcEndDate = clone $endDate;
            $utcEndDate->setTimezone(new \DateTimeZone('UTC'));
        }

        $qb = $customerAccountRelationshipRepo->createQueryBuilder('customerRelationship');
//        $qb->leftJoin('customerRelationship.to', 'customer')
//            ->leftJoin('customer.personDetails', 'customerDetails')
//            ->leftJoin('customer.corporationDetails', 'corporationDetails')
//            ->leftJoin('customerRelationship.from', 'contactPerson')
//            ->leftJoin('contactPerson.personDetails', 'contactPersonDetails')
//            ->leftJoin('contactPerson.corporationDetails', 'contactPersonCorporationDetails');

        $expr = $qb->expr();
        $report = [
            'data' => [],
            'filename' => \sprintf('%s_%s.xlsx', 'Customer Relationship Report', (new \DateTime('now'))->format('d-M')),
            'headers' => [
                'Customer 1 ID',
                'Customer 1 Name',
                'Type',
                'Customer 2 ID',
                'Customer 2 Name',
                'Relating To Account',
                'Valid From',
                'Valid To',
            ],
            'column-format' => [],
        ];

        // populate all column-format
        foreach ($report['headers'] as $key => $header) {
            $report['column-format'][$key] = ['wch' => \strlen($header) + 3];
        }
        // populate all column-format

        if (null !== $utcStartDate && null !== $utcEndDate) {
            $qb->where(
                $expr->andX(
                    $expr->gte('customerRelationship.dateCreated', $expr->literal($utcStartDate->format('c'))),
                    $expr->lte('customerRelationship.dateCreated', $expr->literal($utcEndDate->format('c')))
                )
            );
        } elseif (null !== $utcEndDate) {
            $qb->andWhere($expr->lte('customerRelationship.dateCreated', $expr->literal($utcEndDate->format('c'))));
        } elseif (null !== $utcStartDate) {
            $qb->andWhere($expr->gte('customerRelationship.dateCreated', $expr->literal($utcStartDate->format('c'))));
        }

        if (null !== $utcValidThrough && null !== $utcValidFrom) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('customerRelationship.validFrom', $expr->literal($utcValidFrom->format('c'))),
                    $expr->lte('customerRelationship.validThrough', $expr->literal($utcValidThrough->format('c')))
                )
            );
        } elseif (null !== $utcValidThrough) {
            $qb->andWhere($expr->lte('customerRelationship.validThrough', $expr->literal($utcValidThrough->format('c'))));
        } elseif (null !== $utcValidFrom) {
            $qb->andWhere($expr->gte('customerRelationship.validFrom', $expr->literal($utcValidFrom->format('c'))));
        }

        if ('IS_CONTACT_PERSON' === $relationshipType || null === $relationshipType) {
            $qb->leftJoin('customerRelationship.from', 'contactPerson')
                ->leftJoin('contactPerson.personDetails', 'contactPersonDetails')
                ->leftJoin('contactPerson.corporationDetails', 'contactPersonCorporationDetails')
                ->andWhere($expr->isNotNull('customerRelationship.from'));

            if (null !== $customerName) {
                $tsvectorSubquery = $this->entityManager->createQueryBuilder()
                    ->select('tsvector_concat(coalesce(contactPersonAccount.keywords, \'\'), coalesce(contactPersonCorporation.keywords, \'\'), coalesce(contactPersonAccDetails.keywords, \'\'))')
                    ->from(CustomerAccountRelationship::class, 'custAccRelationship')
                    ->leftJoin('customerRelationship.from', 'contactPersonAccount')
                    ->leftJoin('contactPersonAccount.corporationDetails', 'contactPersonCorporation')
                    ->leftJoin('contactPersonAccount.personDetails', 'contactPersonAccDetails')
                    ->andWhere($expr->andX(
                        $expr->eq('custAccRelationship', 'customerRelationship')
                    ))
                    ->getDQL();

                $qb->andWhere($expr->orX(
                    $expr->andX(
                        $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                            , $tsvectorSubquery, 'customerName'), $expr->literal(true))
                    )
                ));
            }

            if (null !== $customerId) {
                $qb->andWhere($expr->eq('contactPerson.accountNumber', ':customerId'));
            }
        } elseif ('HAS_CONTACT_PERSON' === $relationshipType || null === $relationshipType) {
            $qb->leftJoin('customerRelationship.to', 'customer')
                ->leftJoin('customer.personDetails', 'customerDetails')
                ->leftJoin('customer.corporationDetails', 'customerCorporation')
                ->andWhere($expr->isNotNull('customerRelationship.to'));
            if (null !== $customerName) {
                $tsvectorSubquery = $this->entityManager->createQueryBuilder()
                    ->select('tsvector_concat(coalesce(customerAccount.keywords, \'\'), coalesce(customerAccountCorporation.keywords, \'\'), coalesce(customerAccountDetails.keywords, \'\'))')
                    ->from(CustomerAccountRelationship::class, 'custRelationship')
                    ->leftJoin('customerRelationship.to', 'customerAccount')
                    ->leftJoin('customerAccount.corporationDetails', 'customerAccountCorporation')
                    ->leftJoin('customerAccount.personDetails', 'customerAccountDetails')
                    ->andWhere($expr->andX(
                        $expr->eq('custRelationship', 'customerRelationship')
                    ))
                    ->getDQL();

                $qb->andWhere($expr->orX(
                    $expr->andX(
                        $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                            , $tsvectorSubquery, 'customerName'), $expr->literal(true))
                    )
                ));
            }

            if (null !== $customerId) {
                $qb->andWhere($expr->eq('customer.accountNumber', ':customerId'));
            }
        } else {
            if (null !== $customerId) {
                $qb->andWhere($expr->orX(
                    $expr->eq('contactPerson.accountNumber', ':customerId'),
                    $expr->eq('customer.accountNumber', ':customerId')
                ));
            }
        }

        $customerAccountRelationships = $qb->setParameters($queryParams)
            ->orderBy('customerRelationship.dateCreated', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($customerAccountRelationships as $customerAccountRelationship) {
            for ($operationType = 0; $operationType < 2; ++$operationType) {
                $data = [];
                $reportHeaders = \array_diff($report['headers'], $this->exclusionHeadersList['customerAccountRelationship']);
                foreach ($reportHeaders as $column => $key) {
                    $data[$key] = $this->mapCustomerAccountRelationshipReportData($customerAccountRelationship, $key, $operationType);
                    if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                        $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                    }
                }
                $report['data'][] = $data;
            }
        }

        return $report;
    }

    /**
     * @param array $params
     *
     * @return array|null
     */
    public function createLeadReport(array $params)
    {
        $queryParams = [];
        $createdDateTo = $createdDateFrom = $followUpDateTo = $followUpDateFrom = $leadNumber = $status = $type =
        $customerName = $source = $score = $assignee = $endDate = null;

        $utcCreatedDateTo = $utcCreatedDateFrom = $utcFollowUpDateTo = $utcFollowUpDateFrom = $utcStartDate =
        $utcEndDate = null;

        $leadRepository = $this->entityManager->getRepository(Lead::class);
        $qb = $leadRepository->createQueryBuilder('lead');
        $expr = $qb->expr();

        //region Parameter Instantiation
        if (\array_key_exists('leadNumber', $params)) {
            $leadNumber = $queryParams['leadNumber'] = $params['leadNumber'];
        }
        if (\array_key_exists('type', $params)) {
            $type = $queryParams['type'] = $params['type'];
        }
        if (\array_key_exists('status', $params)) {
            $status = $queryParams['status'] = $params['status'];
        }
        if (\array_key_exists('customerName', $params)) {
            if ($leadRepository instanceof LeadRepository) {
                $customerNameTsQuery = $leadRepository->getKeywordTsquery([$params['customerName']], true);

                $customerName = $queryParams['customerName'] = $customerNameTsQuery;
            }
        }
        if (\array_key_exists('source', $params)) {
            $source = $queryParams['source'] = $params['source'];
        }
        if (\array_key_exists('score', $params)) {
            $score = $queryParams['score'] = $params['score'];
        }
        if (\array_key_exists('assignee', $params)) {
            $assignee = $queryParams['assignee'] = $this->getAllObjectIdsFromIris($params['assignee']);
        }

        if (\array_key_exists('dateCreated[after]', $params) && null !== $params['dateCreated[after]']) {
            $createdDateFrom = new \DateTime($params['dateCreated[after]']);
            $this->timezone = $createdDateFrom->getTimezone();
            $utcCreatedDateFrom = clone $createdDateFrom;
            $utcCreatedDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('dateCreated[before]', $params) && null !== $params['dateCreated[before]']) {
            $createdDateTo = new \DateTime($params['dateCreated[before]']);
            $this->timezone = $createdDateTo->getTimezone();
            $utcCreatedDateTo = clone $createdDateTo;
            $utcCreatedDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('dateFollowedUp[after]', $params) && null !== $params['dateFollowedUp[after]']) {
            $followUpDateFrom = new \DateTime($params['dateFollowedUp[after]']);
            $this->timezone = $followUpDateFrom->getTimezone();
            $utcFollowUpDateFrom = clone $followUpDateFrom;
            $utcFollowUpDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('dateFollowedUp[before]', $params) && null !== $params['dateFollowedUp[before]']) {
            $followUpDateTo = new \DateTime($params['dateFollowedUp[before]']);
            $this->timezone = $followUpDateTo->getTimezone();
            $utcFollowUpDateTo = clone $followUpDateTo;
            $utcFollowUpDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('startDate', $params) && null !== $params['startDate']) {
            $startDate = new \DateTime($params['startDate']);
            $this->timezone = $startDate->getTimezone();
            $utcStartDate = clone $startDate;
            $utcStartDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('endDate', $params) && null !== $params['endDate']) {
            $endDate = new \DateTime($params['endDate']);
            $this->timezone = $endDate->getTimezone();
            $utcEndDate = clone $endDate;
            $utcEndDate->setTimezone(new \DateTimeZone('UTC'));
        }

        $report = [
            'data' => [],
            'filename' => \sprintf('%s_%s.xlsx', 'Lead Report', (new \DateTime('now'))->format('d-M')),
            'headers' => [
                'Lead ID',
                'Contract Type',
                'Industry',
                'Premise Type',
                'Tariff Rate Code',
                'Tariff Rate',
                'Meter Type',
                'Average Consumption',
                'Average Consumption UOM',
                'Purchase Time Frame',
                'Score',
                'Source',
                'Assignee',
                'Category',
                'NRIC/FIN',
                'Designation',
                'Salutation',
                'Full Name',
                'UEN',
                'Company Name',
                'Legal Name',
                'Website',
                'Contact Person NRIC/FIN',
                'Contact Person Designation',
                'Contact Person Salutation',
                'Contact Person Full Name',
                'Preferred Contact Method',
                'Mobile No',
                'Phone No',
                'Fax No',
                'Email',
                'Social Media Account',
                'Do Not Contact',
                'Is Existing Customer ?',
                'Are You A LPG User?',
                'Are You A Tenant?',
                'Address Type',
                'Postal Code',
                'Floor',
                'Unit No',
                'Building No',
                'Building Name',
                'Street Address',
                'City',
                'State',
                'Country',
                'Note Type',
                'Note',
                'Status',
                'Created By',
                'Created Date / Time',
                'Updated By',
                'Updated Date / Time',
                'Followed Up Date / Time',
                'Referral Source',
                'Indicate',
            ],
            'column-format' => [],
        ];

        // populate all column-format
        foreach ($report['headers'] as $key => $header) {
            $report['column-format'][$key] = ['wch' => \strlen($header) + 3];
        }
        // populate all column-format

        if (null !== $utcStartDate && null !== $utcEndDate) {
            $qb->where(
                $expr->andX(
                    $expr->gte('lead.dateCreated', $expr->literal($utcStartDate->format('c'))),
                    $expr->lte('lead.dateCreated', $expr->literal($utcEndDate->format('c')))
                )
            );
        } elseif (null !== $utcEndDate) {
            $qb->andWhere($expr->lte('lead.dateCreated', $expr->literal($utcEndDate->format('c'))));
        } elseif (null !== $utcStartDate) {
            $qb->andWhere($expr->gte('lead.dateCreated', $expr->literal($utcStartDate->format('c'))));
        }

        if (null !== $customerName) {
            $tsvectorSubquery = $this->entityManager->createQueryBuilder()
                ->select('tsvector_concat(lead.keywords, coalesce(leadPersonDetails.keywords, \'\'), coalesce(leadCorporationDetails.keywords, \'\'))')
                ->from(Lead::class, 'leads')
                ->leftJoin('leads.corporationDetails', 'leadCorporationDetails')
                ->leftJoin('leads.personDetails', 'leadPersonDetails')
                ->andWhere($expr->andX(
                    $expr->eq('leads', 'lead')
                ))
                ->getDQL();

            $qb->leftJoin('lead.personDetails', 'leadDetails')
                ->leftJoin('lead.corporationDetails', 'corporationDetails')
                ->andWhere($expr->andX(
                    $expr->eq(\sprintf(<<<'SQL'
                        ts_match((%s), :%s)
SQL
                        , $tsvectorSubquery, 'customerName'), $expr->literal(true))
                ));
        }

        if (null !== $assignee) {
            $qb->andWhere($expr->in('lead.assignee', ':assignee'));
        }

        if (null !== $source) {
            $qb->andWhere($expr->in('lead.source', ':source'));
        }

        if (null !== $utcCreatedDateTo && null !== $utcCreatedDateFrom) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('lead.dateCreated', $expr->literal($utcCreatedDateFrom->format('c'))),
                    $expr->lte('lead.dateCreated', $expr->literal($utcCreatedDateTo->format('c')))
                )
            );
        } elseif (null !== $utcCreatedDateTo) {
            $qb->andWhere($expr->lte('lead.dateCreated', $expr->literal($utcCreatedDateTo->format('c'))));
        } elseif (null !== $utcCreatedDateFrom) {
            $qb->andWhere($expr->gte('lead.dateCreated', $expr->literal($utcCreatedDateFrom->format('c'))));
        }

        if (null !== $utcFollowUpDateFrom && null !== $utcFollowUpDateTo) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('lead.dateFollowedUp', $expr->literal($utcFollowUpDateFrom->format('c'))),
                    $expr->lte('lead.dateFollowedUp', $expr->literal($utcFollowUpDateTo->format('c')))
                )
            );
        } elseif (null !== $utcFollowUpDateTo) {
            $qb->andWhere($expr->lte('lead.dateFollowedUp', $expr->literal($utcFollowUpDateTo->format('c'))));
        } elseif (null !== $utcFollowUpDateFrom) {
            $qb->andWhere($expr->gte('lead.dateFollowedUp', $expr->literal($utcFollowUpDateFrom->format('c'))));
        }

        if (null !== $leadNumber) {
            $qb->andWhere($expr->eq('lead.leadNumber', ':leadNumber'));
        }

        if (null !== $type) {
            $qb->andWhere($expr->in('lead.type', ':type'));
        }

        if (null !== $status) {
            $qb->andWhere($expr->in('lead.status', ':status'));
        }

        if (null !== $score) {
            $qb->andWhere($expr->in('lead.score', ':score'));
        }

        $leads = $qb->setParameters($queryParams)
            ->orderBy('lead.dateCreated', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($leads as $lead) {
            $data = [];
            $reportHeaders = \array_diff($report['headers'], $this->exclusionHeadersList['lead']);
            foreach ($reportHeaders as $column => $key) {
                $data[$key] = $this->mapLeadReportData($lead, $key);
                if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                    $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                }
            }
            $report['data'][] = $data;
        }

        return $report;
    }

    /**
     * @param array $params
     *
     * @return array|null
     */
    public function createOrderReport(array $params)
    {
        $queryParams = [];
        $redemptionDateTo = $redemptionDateFrom = $orderNumber = $customerName = $startDate = $endDate =
        $contractNumber = null;

        $utcRedemptionDateTo = $utcRedemptionDateFrom = $utcStartDate = $utcEndDate = null;
        $orderRepository = $this->entityManager->getRepository(Order::class);

        //region Parameter Instantiation
        if (\array_key_exists('orderNumber', $params)) {
            $orderNumber = $queryParams['orderNumber'] = $params['orderNumber'];
        }
        if (\array_key_exists('contractNumber', $params)) {
            $contractNumber = $queryParams['contractNumber'] = $params['contractNumber'];
        }
        if (\array_key_exists('customerName', $params)) {
            if ($orderRepository instanceof OrderRepository) {
                $customerNameTsQuery = $orderRepository->getKeywordTsquery([$params['customerName']], true);

                $customerName = $queryParams['customerName'] = $customerNameTsQuery;
            }
        }

        if (\array_key_exists('orderDate[before]', $params) && null !== $params['orderDate[before]']) {
            $redemptionDateTo = new \DateTime($params['orderDate[before]']);
            $this->timezone = $redemptionDateTo->getTimezone();
            $utcRedemptionDateTo = clone $redemptionDateTo;
            $utcRedemptionDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('orderDate[after]', $params) && null !== $params['orderDate[after]']) {
            $redemptionDateFrom = new \DateTime($params['orderDate[after]']);
            $this->timezone = $redemptionDateFrom->getTimezone();
            $utcRedemptionDateFrom = clone $redemptionDateFrom;
            $utcRedemptionDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('startDate', $params) && null !== $params['startDate']) {
            $startDate = new \DateTime($params['startDate']);
            $this->timezone = $startDate->getTimezone();
            $utcStartDate = clone $startDate;
            $utcStartDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('endDate', $params) && null !== $params['endDate']) {
            $endDate = new \DateTime($params['endDate']);
            $this->timezone = $endDate->getTimezone();
            $utcEndDate = clone $endDate;
            $utcEndDate->setTimezone(new \DateTimeZone('UTC'));
        }

        $qb = $orderRepository->createQueryBuilder('customerOrder');
        $expr = $qb->expr();
        $qb->where($expr->neq('customerOrder.orderStatus', $expr->literal(OrderStatus::DRAFT)));
        $report = [
            'data' => [],
            'filename' => \sprintf('%s_%s.xlsx', 'Loyalty_Redemption_Orders Report', (new \DateTime('now'))->format('d-M')),
            'headers' => [
                'Redemption Order Number',
                'Redemption Date',
                'Customer Name',
                'Customer Account',
                'MSSL No',
                'EBS No',
                'Total Points Redeemed',
                'Product',
                'Points',
                'Quantity',
            ],
            'column-format' => [],
        ];

        // populate all column-format
        foreach ($report['headers'] as $key => $header) {
            $report['column-format'][$key] = ['wch' => \strlen($header) + 3];
        }
        // populate all column-format

        if (null !== $utcStartDate && null !== $utcEndDate) {
            $qb->where(
                $expr->andX(
                    $expr->gte('customerOrder.orderDate', $expr->literal($utcStartDate->format('c'))),
                    $expr->lte('customerOrder.orderDate', $expr->literal($utcEndDate->format('c')))
                )
            );
        } elseif (null !== $utcEndDate) {
            $qb->andWhere($expr->lte('customerOrder.orderDate', $expr->literal($utcEndDate->format('c'))));
        } elseif (null !== $utcStartDate) {
            $qb->andWhere($expr->gte('customerOrder.orderDate', $expr->literal($utcStartDate->format('c'))));
        }

        if (null !== $customerName) {
            $tsvectorSubquery = $this->entityManager->createQueryBuilder()
                ->select('tsvector_concat(coalesce(orderCustomer.keywords, \'\'), coalesce(orderCorporationDetails.keywords, \'\'), coalesce(orderCustomerDetails.keywords, \'\'))')
                ->from(Order::class, 'orders')
                ->leftJoin('customerOrder.customer', 'orderCustomer')
                ->leftJoin('orderCustomer.corporationDetails', 'orderCorporationDetails')
                ->leftJoin('orderCustomer.personDetails', 'orderCustomerDetails')
                ->andWhere($expr->andX(
                    $expr->eq('orders', 'customerOrder')
                ))
                ->getDQL();

            $qb->leftJoin('customerOrder.customer', 'customer')
                ->leftJoin('customer.personDetails', 'customerDetails')
                ->leftJoin('customer.corporationDetails', 'corporationDetails')
                ->andWhere(
                    $expr->andX(
                        $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                            , $tsvectorSubquery, 'customerName'), $expr->literal(true))
                    )
                );
        }

        if (null !== $utcRedemptionDateFrom && null !== $utcRedemptionDateTo) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('customerOrder.orderDate', $expr->literal($utcRedemptionDateFrom->format('c'))),
                    $expr->lte('customerOrder.orderDate', $expr->literal($utcRedemptionDateTo->format('c')))
                )
            );
        } elseif (null !== $utcRedemptionDateTo) {
            $qb->andWhere($expr->lte('customerOrder.orderDate', $expr->literal($utcRedemptionDateTo->format('c'))));
        } elseif (null !== $utcRedemptionDateFrom) {
            $qb->andWhere($expr->gte('customerOrder.orderDate', $expr->literal($utcRedemptionDateFrom->format('c'))));
        }

        if (null !== $orderNumber) {
            $qb->andWhere($expr->eq('customerOrder.orderNumber', ':orderNumber'));
        }

        if (null !== $contractNumber) {
            $qb->leftJoin('customerOrder.object', 'contract')
                ->andWhere($expr->eq('contract.contractNumber', ':contractNumber'));
        }

        $orders = $qb->setParameters($queryParams)
            ->orderBy('customerOrder.dateCreated', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($orders as $order) {
            $data = [];
            $reportHeaders = \array_diff($report['headers'], $this->exclusionHeadersList['order']);
            foreach ($order->getItems() as $orderItem) {
                foreach ($reportHeaders as $column => $key) {
                    $data[$key] = $this->mapOrderReportData($order, $key, $orderItem);
                    if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                        $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                    }
                }
            }
            $report['data'][] = $data;
        }

        return $report;
    }

    /**
     * @param array $params
     *
     * @return array|null
     */
    public function createTicketReport(array $params)
    {
        $queryParams = [];
        $createdDateTo = $createdDateFrom = $ticketNumber = $type = $category = $subCategory = $status = $priority =
        $customerName = $source = $slaBreached = $anonymous = $assignee = $startDate = $endDate = null;

        $utcEndDate = $utcCreatedDateTo = $utcCreatedDateFrom = $utcStartDate = null;
        $ticketRepository = $this->entityManager->getRepository(Ticket::class);
        $qb = $ticketRepository->createQueryBuilder('ticket');
        $expr = $qb->expr();

        if (\array_key_exists('ticketNumber', $params)) {
            $ticketNumber = $queryParams['ticketNumber'] = $params['ticketNumber'];
        }
        if (\array_key_exists('type', $params)) {
            $type = $queryParams['type'] = $this->getAllObjectIdsFromIris($params['type']);
        }
        if (\array_key_exists('category', $params)) {
            $category = $queryParams['category'] = $this->getAllObjectIdsFromIris($params['category']);
        }
        if (\array_key_exists('subcategory', $params)) {
            $subCategory = $queryParams['subcategory'] = $this->getAllObjectIdsFromIris($params['subcategory']);
        }
        if (\array_key_exists('status', $params)) {
            $status = $queryParams['status'] = $params['status'];
        }
        if (\array_key_exists('priority', $params)) {
            $priority = $queryParams['priority'] = $params['priority'];
        }
        if (\array_key_exists('customerName', $params)) {
            if ($ticketRepository instanceof TicketRepository) {
                $customerNameTsQuery = $ticketRepository->getKeywordTsquery([$params['customerName']], true);

                $customerName = $queryParams['customerName'] = $customerNameTsQuery;
            }
        }
        if (\array_key_exists('source', $params)) {
            $source = $queryParams['source'] = $params['source'];
        }
        if (\array_key_exists('slaBreached', $params)) {
            $slaBreached = $params['slaBreached'];
        }
        if (\array_key_exists('customer[exists]', $params)) {
            $anonymous = $params['customer[exists]'];
        }
        if (\array_key_exists('assignee', $params)) {
            $assignee = $queryParams['assignee'] = $this->getAllObjectIdsFromIris($params['assignee']);
        }

        if (\array_key_exists('dateCreated[after]', $params) && null !== $params['dateCreated[after]']) {
            $createdDateFrom = new \DateTime($params['dateCreated[after]']);
            $this->timezone = $createdDateFrom->getTimezone();
            $utcCreatedDateFrom = clone $createdDateFrom;
            $utcCreatedDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('dateCreated[before]', $params) && null !== $params['dateCreated[before]']) {
            $createdDateTo = new \DateTime($params['dateCreated[before]']);
            $this->timezone = $createdDateTo->getTimezone();
            $utcCreatedDateTo = clone $createdDateTo;
            $utcCreatedDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('startDate', $params) && null !== $params['startDate']) {
            $startDate = new \DateTime($params['startDate']);
            $this->timezone = $startDate->getTimezone();
            $utcStartDate = clone $startDate;
            $utcStartDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('endDate', $params) && null !== $params['endDate']) {
            $endDate = new \DateTime($params['endDate']);
            $this->timezone = $endDate->getTimezone();
            $utcEndDate = clone $endDate;
            $utcEndDate->setTimezone(new \DateTimeZone('UTC'));
        }

        $report = [
            'data' => [],
            'filename' => \sprintf('%s_%s.xlsx', 'Case Report', (new \DateTime('now'))->format('d-M')),
            'headers' => [
                'Case ID',
                'Customer ID',
                'Anonymous',
                'Customer Name',
                'Customer Account',
                'MSSL No',
                'EBS No',
                'Channel',
                'Source',
                'Case Type',
                'Main Category',
                'Sub Category',
                'Description',
                'Status',
                'Priority',
                'SLA Breach',
                'Assigned To',
                'Resolution Officer',
                'Incident Date',
                'Created At',
            ],
            'column-format' => [],
        ];

        // populate all column-format
        foreach ($report['headers'] as $key => $header) {
            $report['column-format'][$key] = ['wch' => \strlen($header) + 3];
        }
        // populate all column-format

        if (null !== $utcStartDate && null !== $utcEndDate) {
            $qb->where(
                $expr->andX(
                    $expr->gte('ticket.dateCreated', $expr->literal($utcStartDate->format('c'))),
                    $expr->lte('ticket.dateCreated', $expr->literal($utcEndDate->format('c')))
                )
            );
        } elseif (null !== $utcEndDate) {
            $qb->andWhere($expr->lte('ticket.dateCreated', $expr->literal($utcEndDate->format('c'))));
        } elseif (null !== $utcStartDate) {
            $qb->andWhere($expr->gte('ticket.dateCreated', $expr->literal($utcStartDate->format('c'))));
        }

        if (null !== $type) {
            $qb->andWhere($expr->in('ticket.type', ':type'));
        }

        if (null !== $category) {
            $qb->andWhere($expr->in('ticket.category', ':category'));
        }

        if (null !== $subCategory) {
            $qb->andWhere($expr->in('ticket.subcategory', ':subcategory'));
        }

        if (null !== $slaBreached) {
            if ('true' === $slaBreached) {
                $qb->andWhere($expr->lt('ticket.timeLeft.value', 0));
            } else {
                $qb->andWhere($expr->gte('ticket.timeLeft.value', 0));
            }
        }

        if (null !== $customerName) {
            $tsvectorSubquery = $this->entityManager->createQueryBuilder()
                ->select('tsvector_concat(ticket.keywords, coalesce(ticketPerson.keywords, \'\'), coalesce(ticketCustomer.keywords, \'\'), coalesce(ticketCustomerCorporation.keywords, \'\'), coalesce(ticketCustomerDetails.keywords, \'\'))')
                ->from(Ticket::class, 'tickets')
                ->leftJoin('tickets.personDetails', 'ticketPerson')
                ->leftJoin('tickets.customer', 'ticketCustomer')
                ->leftJoin('ticketCustomer.corporationDetails', 'ticketCustomerCorporation')
                ->leftJoin('ticketCustomer.personDetails', 'ticketCustomerDetails')
                ->andWhere($expr->andX(
                    $expr->eq('tickets', 'ticket')
                ))
                ->getDQL();

            $qb->leftJoin('ticket.personDetails', 'customerInfo')
                ->leftJoin('ticket.customer', 'customerAccount')
                ->leftJoin('customerAccount.personDetails', 'customerDetails')
                ->leftJoin('customerAccount.corporationDetails', 'corporationDetails')
                ->andWhere($expr->orX(
                    $expr->andX(
                        $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                            , $tsvectorSubquery, 'customerName'), $expr->literal(true))
                    )));
        }

        if (null !== $assignee) {
            $qb->andWhere($expr->in('ticket.assignee', ':assignee'));
        }

        if (null !== $source) {
            $qb->andWhere($expr->in('ticket.source', ':source'));
        }

        if (null !== $utcCreatedDateTo && null !== $utcCreatedDateFrom) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('ticket.dateCreated', $expr->literal($utcCreatedDateFrom->format('c'))),
                    $expr->lte('ticket.dateCreated', $expr->literal($utcCreatedDateTo->format('c')))
                )
            );
        } elseif (null !== $utcCreatedDateTo) {
            $qb->andWhere($expr->lte('ticket.dateCreated', $expr->literal($utcCreatedDateTo->format('c'))));
        } elseif (null !== $utcCreatedDateFrom) {
            $qb->andWhere($expr->gte('ticket.dateCreated', $expr->literal($utcCreatedDateFrom->format('c'))));
        }

        if (null !== $ticketNumber) {
            $qb->andWhere($expr->eq('ticket.ticketNumber', ':ticketNumber'));
        }

        if (null !== $status) {
            $qb->andWhere($expr->in('ticket.status', ':status'));
        }

        if (null !== $priority) {
            $qb->andWhere($expr->in('ticket.priority', ':priority'));
        }

        if (null !== $anonymous) {
            if (true === $anonymous || 'true' === $anonymous) {
                $qb->andWhere($expr->isNotNull('ticket.customer'));
            } else {
                $qb->andWhere($expr->isNull('ticket.customer'));
            }
        }

        $tickets = $qb->setParameters($queryParams)
            ->orderBy('ticket.dateCreated', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($tickets as $ticket) {
            $data = [];
            $reportHeaders = \array_diff($report['headers'], $this->exclusionHeadersList['ticket']);
            foreach ($reportHeaders as $column => $key) {
                $data[$key] = $this->mapTicketReportData($ticket, $key);
                if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                    $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                }
            }
            $report['data'][] = $data;
        }

        return $report;
    }

    /**
     * @param array     $params
     * @param bool|null $retunQueryResultOnly
     *
     * @return array|null
     */
    public function createUserReport(array $params = [], $retunQueryResultOnly = false)
    {
        $queryParams = ['customerCategoryParam' => \json_encode('CUSTOMER'), 'contactPersonCategoryParam' => \json_encode('CONTACT_PERSON')];

        $keyword = $utcEndDate = $startDate = $endDate = $utcEndDate = $utcStartDate = $registeredDateTo =
        $registeredDateFrom = $email = $mobileDeviceLogin = $utcRegisteredDateTo = $utcRegisteredDateFrom =
        $postalCode = $paymentMode = $contractSubtype = $pricePlan = $birthDate = $gender = $customerAccountStatus =
        $customerType = $utcBirthDate = $keywordsTsQuery = null;

        $userRepository = $this->entityManager->getRepository(User::class);
        $qb = $userRepository->createQueryBuilder('user')->join('user.customerAccount', 'userAccount');
        $expr = $qb->expr();
        $qb->where($expr->orX($expr->eq(<<<'SQL'
                    jsonb_contains(CAST(userAccount.categories AS jsonb), :customerCategoryParam)
SQL
            , $expr->literal(true)),
            $expr->eq(<<<'SQL'
                    jsonb_contains(CAST(userAccount.categories AS jsonb), :contactPersonCategoryParam)
SQL
                , $expr->literal(true))));

        if (\array_key_exists('postalCode', $params)) {
            $postalCode = $queryParams['postalCode'] = $params['postalCode'];
        }

        if (\array_key_exists('paymentMode', $params)) {
            $paymentMode = $queryParams['paymentMode'] = $params['paymentMode'];
        }

        if (\array_key_exists('contractSubtype', $params)) {
            $contractSubtype = $queryParams['contractSubtype'] = $params['contractSubtype'];
        }

        if (\array_key_exists('pricePlan', $params)) {
            $pricePlan = $queryParams['pricePlan'] = $this->getAllObjectIdsFromIris($params['pricePlan']);
        }

        if (\array_key_exists('birthDate', $params) && null !== $params['birthDate']) {
            $birthDate = new \DateTime($params['birthDate']);
            $this->timezone = $birthDate->getTimezone();
            $utcBirthDate = clone $birthDate;
            $utcBirthDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('gender', $params)) {
            $gender = $queryParams['gender'] = $params['gender'];
        }

        if (\array_key_exists('customerAccountStatus', $params)) {
            $customerAccountStatus = $queryParams['customerAccountStatus'] = $params['customerAccountStatus'];
        }

        if (\array_key_exists('customerType', $params)) {
            $customerType = $queryParams['customerType'] = $params['customerType'];
        }

        if (\array_key_exists('email', $params)) {
            $email = $queryParams['userEmail'] = $params['email'];
        }

        if (\array_key_exists('mobileDeviceLogin', $params)) {
            if (true === $params['mobileDeviceLogin'] || 'true' === $params['mobileDeviceLogin']) {
                $mobileDeviceLogin = $queryParams['mobileDeviceLogin'] = $expr->literal(true);
            } else {
                $mobileDeviceLogin = $queryParams['mobileDeviceLogin'] = $expr->literal(false);
            }
        }

        if (\array_key_exists('customerName', $params) || \array_key_exists('keywords', $params)) {
            if ($userRepository instanceof UserRepository) {
                if (\array_key_exists('customerName', $params)) {
                    $keyword = $queryParams['keyword'] = $params['customerName'];
                    $keywordsTsQuery = $userRepository->getKeywordTsquery([$params['customerName']], true);
                } else {
                    $keyword = $queryParams['keyword'] = $params['keywords'];
                    $keywordsTsQuery = $userRepository->getKeywordTsquery([$params['keywords']], true);
                }

                $queryParams['keywordsTsQuery'] = $keywordsTsQuery;
            }
        }

        if (\array_key_exists('registeredDateFrom', $params) && null !== $params['registeredDateFrom']) {
            $registeredDateFrom = new \DateTime($params['registeredDateFrom']);
            $this->timezone = $registeredDateFrom->getTimezone();
            $utcRegisteredDateFrom = clone $registeredDateFrom;
            $utcRegisteredDateFrom->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('registeredDateTo', $params) && null !== $params['registeredDateTo']) {
            $registeredDateTo = new \DateTime($params['registeredDateTo']);
            $this->timezone = $registeredDateTo->getTimezone();
            $utcRegisteredDateTo = clone $registeredDateTo;
            $utcRegisteredDateTo->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('startDate', $params) && null !== $params['startDate']) {
            $startDate = new \DateTime($params['startDate']);
            $this->timezone = $startDate->getTimezone();
            $utcStartDate = clone $startDate;
            $utcStartDate->setTimezone(new \DateTimeZone('UTC'));
        }

        if (\array_key_exists('endDate', $params) && null !== $params['endDate']) {
            $endDate = new \DateTime($params['endDate']);
            $this->timezone = $endDate->getTimezone();
            $utcEndDate = clone $endDate;
            $utcEndDate->setTimezone(new \DateTimeZone('UTC'));
        }

        $report = [
            'data' => [],
            'filename' => \sprintf('%s_%s.xlsx', 'User List', (new \DateTime('now'))->format('d-M')),
            'headers' => [
                'Customer ID',
                'Customer Name',
                'Email',
                'Last Login',
                'Mobile',
                'Mobile No',
                'Date Registered',
            ],
            'column-format' => [],
        ];

        // populate all column-format
        foreach ($report['headers'] as $key => $header) {
            $report['column-format'][$key] = ['wch' => \strlen($header) + 3];
        }
        // populate all column-format

        if (null !== $utcEndDate && null !== $utcStartDate) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('user.dateCreated', $expr->literal($utcStartDate->format('c'))),
                    $expr->lte('user.dateCreated', $expr->literal($utcEndDate->format('c')))
                )
            );
        } elseif (null !== $utcEndDate) {
            $qb->andWhere($expr->lte('user.dateCreated', $expr->literal($utcEndDate->format('c'))));
        } elseif (null !== $utcStartDate) {
            $qb->andWhere($expr->gte('user.dateCreated', $expr->literal($utcStartDate->format('c'))));
        }

        if (null !== $mobileDeviceLogin) {
            if (false === $params['mobileDeviceLogin'] || 'false' === $params['mobileDeviceLogin']) {
                $qb->andWhere($expr->orX($expr->eq('user.mobileDeviceLogin', ':mobileDeviceLogin'),
                    $expr->isNull('user.mobileDeviceLogin')));
            } else {
                $qb->andWhere($expr->eq('user.mobileDeviceLogin', ':mobileDeviceLogin'));
            }
        }

        if (null !== $utcRegisteredDateTo && null !== $utcRegisteredDateFrom) {
            $qb->andWhere(
                $expr->andX(
                    $expr->gte('user.dateCreated', $expr->literal($utcRegisteredDateFrom->format('c'))),
                    $expr->lte('user.dateCreated', $expr->literal($utcRegisteredDateTo->format('c')))
                )
            );
        } elseif (null !== $utcRegisteredDateTo) {
            $qb->andWhere($expr->lte('user.dateCreated', $expr->literal($utcRegisteredDateTo->format('c'))));
        } elseif (null !== $utcRegisteredDateFrom) {
            $qb->andWhere($expr->gte('user.dateCreated', $expr->literal($utcRegisteredDateFrom->format('c'))));
        }

        if (null !== $email) {
            $qb->andWhere($expr->eq('user.email', ':userEmail'));
        }

        if (null !== $postalCode) {
            $qb->join('userAccount.addresses', 'customerAddress')
                ->join('customerAddress.address', 'userAddress')
                ->andWhere($expr->eq('userAddress.postalCode', ':postalCode'));
        }

        if (null !== $paymentMode || null !== $contractSubtype || null !== $pricePlan) {
            $qb->join('userAccount.contracts', 'userContracts');
            if (null !== $paymentMode) {
                $qb->andWhere($expr->in('userContracts.paymentMode', ':paymentMode'));
            }

            if (null !== $contractSubtype) {
                $qb->andWhere($expr->in('userContracts.subtype', ':contractSubtype'));
            }

            if (null !== $pricePlan) {
                $qb->join('userContracts.tariffRate', 'contractTariffRate')
                    ->andWhere($expr->in('contractTariffRate.isBasedOn', ':pricePlan'));
            }
        }

        if (null !== $birthDate || null !== $gender || null !== $keyword) {
            $qb->join('userAccount.personDetails', 'userDetails');

            if (null !== $keyword && null !== $keywordsTsQuery) {
                $tsvectorSubquery = $this->entityManager->createQueryBuilder()
                    ->select('tsvector_concat(coalesce(userCustomer.keywords, \'\'), coalesce(customerCorporation.keywords, \'\'), coalesce(customerDetails.keywords, \'\'))')
                    ->from(User::class, 'appUser')
                    ->join('user.customerAccount', 'userCustomer')
                    ->leftJoin('userCustomer.corporationDetails', 'customerCorporation')
                    ->leftJoin('userCustomer.personDetails', 'customerDetails')
                    ->andWhere($expr->andX(
                        $expr->eq('appUser', 'user')
                    ))
                    ->getDQL();

                $filterOrExprs = $expr->orX();

                $filterOrExprs->add($expr->andX(
                    $expr->eq(\sprintf(<<<'SQL'
                    ts_match((%s), :%s)
SQL
                        , $tsvectorSubquery, 'keywordsTsQuery'), $expr->literal(true))
                ));

                $filterOrExprs->add($expr->eq('user.email', ':keyword'));
                $filterOrExprs->add($expr->eq('user.username', ':keyword'));

                $qb->andWhere($filterOrExprs);
            }

            if (null !== $birthDate) {
                $qb->andWhere($expr->eq('userDetails.birthDate', $expr->literal($birthDate->format('c'))));
            }

            if (null !== $gender) {
                $qb->andWhere($expr->in('userDetails.gender', ':gender'));
            }
        }

        if (null !== $customerAccountStatus) {
            $qb->andWhere($expr->in('userAccount.status', ':customerAccountStatus'));
        }

        if (null !== $customerType) {
            $qb->andWhere($expr->in('userAccount.type', ':customerType'));
        }

        $users = $qb->setParameters($queryParams)
            ->orderBy('user.dateCreated', 'ASC')
            ->getQuery()
            ->getResult();

        if (true === $retunQueryResultOnly) {
            return $users;
        }

        foreach ($users as $user) {
            $data = [];
            $reportHeaders = \array_diff($report['headers'], $this->exclusionHeadersList['user']);
            foreach ($reportHeaders as $column => $key) {
                $data[$key] = $this->mapUserReportData($user, $key);
                if (\strlen((string) $data[$key]) + 3 > $report['column-format'][$column]['wch']) {
                    $report['column-format'][$column]['wch'] = \strlen($data[$key]) + 3;
                }
            }
            $report['data'][] = $data;
        }

        return $report;
    }

    /**
     * @param array          $reports
     * @param array          $recipients
     * @param \DateTime|null $date
     */
    public function queueEmailJob(array $reports, array $recipients, ?\DateTime $date = null)
    {
        $now = new \DateTime();
        $documentGroups = [];

        foreach ($reports as $report) {
            $documentType = $report->getType()->getValue();
            if (!isset($documentGroups[$documentType])) {
                $documentGroups[$documentType] = [];
            }
            $documentGroups[$documentType][] = $this->iriConverter->getIriFromItem($report);
        }

        foreach ($documentGroups as $type => $documentGroupIris) {
            $jobType = $this->mapDocumentTypeJobType($type);
            $job = new DisqueJob([
                'data' => [
                    'reports' => $documentGroupIris,
                ],
                'type' => $jobType,
                'recipients' => $recipients,
            ]);

            if (null !== $date && $now < $date) {
                $this->emailsQueue->schedule($job, $date);
            } else {
                $this->emailsQueue->push($job);
            }
        }
    }

    private function mapApplicationRequestReportData(ApplicationRequest $applicationRequest, string $header)
    {
        switch ($header) {
            case 'Application ID':
                return $applicationRequest->getApplicationRequestNumber();
            case 'Partner Name':
                if (null !== $applicationRequest->getAcquiredFrom()) {
                    $customerAccount = $applicationRequest->getAcquiredFrom();
                    if (AccountType::CORPORATE === $customerAccount->getType()->getValue() && null !== $customerAccount->getCorporationDetails()) {
                        return $customerAccount->getCorporationDetails()->getName();
                    } elseif (null !== $customerAccount->getPersonDetails()) {
                        return $customerAccount->getPersonDetails()->getName();
                    }
                }

                return null;
            case 'Draft App ID':
                if (\in_array($applicationRequest->getStatus()->getValue(), [
                    ApplicationRequestStatus::PARTNER_DRAFT,
                    ApplicationRequestStatus::VOIDED,
                ], true)) {
                    return $applicationRequest->getApplicationRequestNumber();
                }

                return null;
            case 'In Progress App ID':
                if (ApplicationRequestStatus::IN_PROGRESS === $applicationRequest->getStatus()->getValue()) {
                    return $applicationRequest->getApplicationRequestNumber();
                }

                return null;
            case 'Status':
                $status = \str_replace('_', ' ', \ucwords(\strtolower($applicationRequest->getStatus()->getValue()), '_'));
                $status = \str_replace('Partner ', '', $status);

                return $status;
            case 'Sales Rep Name':
                if (null !== $applicationRequest->getCreator()) {
                    $customerAccount = $applicationRequest->getCreator()->getCustomerAccount();

                    if (AccountType::CORPORATE === $customerAccount->getType()->getValue() && null !== $customerAccount->getCorporationDetails()) {
                        return $customerAccount->getCorporationDetails()->getName();
                    } elseif (null !== $customerAccount->getPersonDetails()) {
                        return $customerAccount->getPersonDetails()->getName();
                    }
                }

                return null;
            case 'Location Code':
                return $applicationRequest->getLocation();
            case 'Account Holder':
                if (null !== $applicationRequest->getCorporationDetails()) {
                    return $applicationRequest->getCorporationDetails()->getName();
                } elseif (null !== $applicationRequest->getPersonDetails()) {
                    return $applicationRequest->getPersonDetails()->getName();
                }

                return null;
            case 'Account Holder Contact':
                $contactPoints = [];

                if (null !== $applicationRequest->getCorporationDetails()) {
                    $contactPoints = $applicationRequest->getCorporationDetails()->getContactPoints();
                } elseif (null !== $applicationRequest->getPersonDetails()) {
                    $contactPoints = $applicationRequest->getPersonDetails()->getContactPoints();
                }

                foreach ($contactPoints as $contactPoint) {
                    if (\count($contactPoint->getMobilePhoneNumbers()) > 0) {
                        $mobileNumber = \array_values(\array_slice($contactPoint->getMobilePhoneNumbers(), -1))[0];
                        if (!empty($mobileNumber)) {
                            return $this->phoneNumberUtil->format($mobileNumber, PhoneNumberFormat::E164);
                        }
                    }
                }

                return null;
            case 'Premise Address':
                $premiseAddress = '';

                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                        if (null !== $address->getHouseNumber()) {
                            $premiseAddress .= \sprintf('%s ', $address->getHouseNumber());
                        }

                        if (null !== $address->getBuildingName()) {
                            $premiseAddress .= \sprintf('%s ', $address->getBuildingName());
                        }

                        if (null !== $address->getStreetAddress()) {
                            $premiseAddress .= \sprintf('%s ', $address->getStreetAddress());
                        }

                        if (null !== $address->getFloor()) {
                            $premiseAddress .= \sprintf('#%s-', $address->getFloor());
                        }

                        if (null !== $address->getUnitNumber()) {
                            $premiseAddress .= \sprintf('%s ', $address->getUnitNumber());
                        }

                        if (null !== $address->getAddressLocality()) {
                            if ($address->getAddressLocality() !== $address->getAddressCountry()) {
                                $premiseAddress .= \sprintf('%s %s ', $address->getAddressLocality(), $address->getAddressCountry());
                            } else {
                                $premiseAddress .= \sprintf('%s ', $address->getAddressLocality());
                            }
                        } elseif (null !== $address->getAddressCountry()) {
                            $premiseAddress .= \sprintf('%s ', $address->getAddressCountry());
                        }

                        if (null !== $address->getPostalCode()) {
                            $premiseAddress .= \sprintf('%s', $address->getPostalCode());
                        }
                        break;
                    }
                }

                return $premiseAddress;
            case 'Dwelling Type':
                return $this->dataMapper->mapContractSubtype($applicationRequest->getContractSubtype());
            case 'Created By (ID)':
                if (null !== $applicationRequest->getCreator()) {
                    return $applicationRequest->getCreator()->getCustomerAccount()->getAccountNumber();
                }

                return null;
            case 'Created Datetime':
                if (null !== $applicationRequest->getDateCreated()) {
                    return $applicationRequest->getDateCreated()->setTimezone($this->timezone)->format('d-m-Y g:ia');
                }

                return null;
            case 'Submitted By (ID)':
                if (\in_array($applicationRequest->getStatus()->getValue(), [
                    ApplicationRequestStatus::CANCELLED,
                    ApplicationRequestStatus::COMPLETED,
                    ApplicationRequestStatus::IN_PROGRESS,
                    ApplicationRequestStatus::REJECTED,
                ], true)) {
                    if (null !== $applicationRequest->getSubmitter()) {
                        return $applicationRequest->getSubmitter()->getCustomerAccount()->getAccountNumber();
                    }
                }

                return null;
            case 'Submitted Datetime':
                if (\in_array($applicationRequest->getStatus()->getValue(), [
                    ApplicationRequestStatus::CANCELLED,
                    ApplicationRequestStatus::COMPLETED,
                    ApplicationRequestStatus::IN_PROGRESS,
                    ApplicationRequestStatus::REJECTED,
                ], true)) {
                    if (null !== $applicationRequest->getDateSubmitted()) {
                        return $applicationRequest->getDateSubmitted()->setTimezone($this->timezone)->format('d-m-Y g:ia');
                    }
                }

                return null;
            case 'Remarks':
                return $applicationRequest->getRemark();
            case 'SP Account Missing for Draft Applications?':
                if (null !== $applicationRequest->getMsslAccountNumber() || null !== $applicationRequest->getEbsAccountNumber()) {
                    return 'No';
                }

                return 'Yes';
            case 'SP Account Holder':
                return true === $applicationRequest->isSelfApplication() ? 'Yes' : 'No';
            case 'Sign on Behalf Attachment Missing for Draft Applications?':
                if (false === $applicationRequest->isSelfApplication() && \count($applicationRequest->getSupplementaryFiles()) < 2) {
                    return 'Yes';
                }

                return 'No';
            case 'MSSL Account Number (For Verification Purpose)':
                if (null !== $applicationRequest->getMsslAccountNumber()) {
                    return $applicationRequest->getMsslAccountNumber();
                }

                return null;
            case 'EBS Account Number (For Verification Purpose)':
                if (null !== $applicationRequest->getEbsAccountNumber()) {
                    return $applicationRequest->getEbsAccountNumber();
                }

                return null;
            case 'Promotion Plan Code':
                if (null !== $applicationRequest->getTariffRate()) {
                    return $applicationRequest->getTariffRate()->getTariffRateNumber();
                }

                return null;
            case 'Promotion Plan Name':
                if (null !== $applicationRequest->getTariffRate()) {
                    return $applicationRequest->getTariffRate()->getName();
                }

                return null;
            case 'Applicant':
                if (false === $applicationRequest->isSelfApplication() && null !== $applicationRequest->getRepresentativeDetails()) {
                    return $applicationRequest->getRepresentativeDetails()->getName();
                }

                return null;
            case 'Applicant Contact':
                if (false === $applicationRequest->isSelfApplication() && null !== $applicationRequest->getRepresentativeDetails()) {
                    $mobileNumber = ReportHelper::mapContactPoints($applicationRequest->getRepresentativeDetails()->getContactPoints(), 'mobilePhoneNumbers');

                    if (!empty($mobileNumber)) {
                        return $this->phoneNumberUtil->format($mobileNumber, PhoneNumberFormat::E164);
                    }
                }

                return null;
            case 'Applicant Email':
                if (false === $applicationRequest->isSelfApplication() && null !== $applicationRequest->getRepresentativeDetails()) {
                    return ReportHelper::mapContactPoints($applicationRequest->getRepresentativeDetails()->getContactPoints(), 'emails');
                }

                return null;
            default:
                return null;
        }
    }

    private function mapDocumentTypeJobType(string $type)
    {
        switch ($type) {
            case DocumentType::PARTNER_CONTRACT_APPLICATION_REQUEST_REPORT:
                return JobType::PARTNER_GENERATED_CONTRACT_APPLICATION_REPORT;
            case DocumentType::APPLICATION_REQUEST_REPORT:
                return JobType::APPLICATION_REQUEST_REPORT_GENERATE;
            case DocumentType::CUSTOMER_ACCOUNT_REPORT:
                return JobType::CUSTOMER_ACCOUNT_REPORT_GENERATE;
            case DocumentType::LEAD_REPORT:
                return JobType::LEAD_REPORT_GENERATE;
            case DocumentType::TICKET_REPORT:
                return JobType::TICKET_REPORT_GENERATE;
            case DocumentType::USER_REPORT:
                return JobType::USER_REPORT_GENERATE;
            case DocumentType::CONTRACT_REPORT:
                return JobType::CONTRACT_REPORT_GENERATE;
            case DocumentType::CREDITS_ACTION_REPORT:
                return JobType::CREDITS_ACTION_REPORT_GENERATE;
            case DocumentType::CUSTOMER_ACCOUNT_RELATIONSHIP_REPORT:
                return JobType::CUSTOMER_ACCOUNT_RELATIONSHIP_REPORT_GENERATE;
            case DocumentType::ORDER_REPORT:
                return JobType::ORDER_REPORT_GENERATE;
            default:
                return null;
        }
    }

    private function mapApplicationRequestData(ApplicationRequest $applicationRequest, string $header)
    {
        switch ($header) {
            case 'Application Request ID':
                return $applicationRequest->getApplicationRequestNumber();
            case 'Application Type':
                return \str_replace('_', ' ', \ucwords(\strtolower($applicationRequest->getType()->getValue()), '_'));
            case 'Average Consumption':
                return null !== $applicationRequest->getAverageConsumption()->getValue() ? (float) $applicationRequest->getAverageConsumption()->getValue().' '.ReportHelper::mapUnitCodes($applicationRequest->getAverageConsumption()->getUnitCode()) : null;
            case 'Customer ID':
                if (null !== $applicationRequest->getCustomer()) {
                    return $applicationRequest->getCustomer()->getAccountNumber();
                }

                return null;
            case 'Customer Account':
                return null !== $applicationRequest->getContract()
                    ? $applicationRequest->getContract()->getContractNumber()
                    : null;
            case 'Contract Type':
                return null !== $applicationRequest->getContractType() ? $applicationRequest->getContractType()->getValue() : null;
            case 'Premise Type':
                return null !== $applicationRequest->getContractType()
                && ContractType::RESIDENTIAL === $applicationRequest->getContractType()->getValue()
                    ? $this->dataMapper->mapContractSubtype($applicationRequest->getContractSubtype()) : null;
            case 'Industry':
                return null !== $applicationRequest->getContractType()
                && ContractType::COMMERCIAL === $applicationRequest->getContractType()->getValue()
                    ? $this->dataMapper->mapContractSubtype($applicationRequest->getContractSubtype()) : null;
            case 'Tariff Rate Code':
                return null !== $applicationRequest->getTariffRate() ? $applicationRequest->getTariffRate()->getTariffRateNumber() : null;
            case 'Tariff Rate':
                return null !== $applicationRequest->getTariffRate() ? $applicationRequest->getTariffRate()->getName() : null;
            case 'Referral Code':
                return $applicationRequest->getReferralCode();
            case 'Meter Option':
                return null !== $applicationRequest->getMeterType() ? $applicationRequest->getMeterType()->getValue() : null;
            case 'SP Account No.':
                return null === $applicationRequest->getEbsAccountNumber() ? $applicationRequest->getMsslAccountNumber() : $applicationRequest->getEbsAccountNumber();
            case 'Preferred Start Date':
                return null !== $applicationRequest->getPreferredStartDate()
                    ? $applicationRequest->getPreferredStartDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Preferred Turn Off Date':
                return null !== $applicationRequest->getPreferredEndDate()
                    ? $applicationRequest->getPreferredEndDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Self Read Option':
                return true === $applicationRequest->isSelfReadMeterOption() ? 'YES' : 'NO';
            case 'GIRO Application':
                return true !== $applicationRequest->isGIROOption() ? 'YES' : 'NO';
            case 'Source':
                return null !== $applicationRequest->getSource()
                    ? \str_replace('_', ' ', \ucwords(\strtolower($applicationRequest->getSource()), '_'))
                    : null;
            case 'Category':
                return null !== $applicationRequest->getCustomerType()
                    ? $applicationRequest->getCustomerType()->getValue()
                    : null;
            case 'Deposit':
                return null !== $applicationRequest->getDepositRefundType() ? $applicationRequest->getDepositRefundType()->getValue() : null;
            case 'NRIC/FIN':
                if (null !== $applicationRequest->getCustomerType() && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue()) {
                    if (null !== $applicationRequest->getPersonDetails()) {
                        return ReportHelper::mapIdentifiers($applicationRequest->getPersonDetails()->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
                    }
                }

                return null;
            case 'Salutation':
                return (null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
                    && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue())
                    ? $applicationRequest->getPersonDetails()->getHonorificPrefix()
                    : null;
            case 'First Name':
                return (null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
                    && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue())
                    ? $applicationRequest->getPersonDetails()->getGivenName()
                    : null;
            case 'Middle Name':
                return (null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
                    && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue())
                    ? $applicationRequest->getPersonDetails()->getAdditionalName()
                    : null;
            case 'Last Name':
                return (null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
                    && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue())
                    ? $applicationRequest->getPersonDetails()->getFamilyName()
                    : null;
            case 'Full Name':
                return (null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
                    && AccountType::INDIVIDUAL === $applicationRequest->getCustomerType()->getValue())
                    ? $applicationRequest->getPersonDetails()->getName()
                    : null;
            case 'Mobile No.':
                if (null !== $applicationRequest->getPersonDetails()) {
                    $mobileNumber = ReportHelper::mapContactPoints($applicationRequest->getPersonDetails()->getContactPoints(), 'mobilePhoneNumbers');
                    if (!empty($mobileNumber)) {
                        return $this->phoneNumberUtil->format($mobileNumber, PhoneNumberFormat::E164);
                    }
                }

                return null;
            case 'Phone No.':
                if (null !== $applicationRequest->getPersonDetails()) {
                    $telephoneNumber = ReportHelper::mapContactPoints($applicationRequest->getPersonDetails()->getContactPoints(), 'telephoneNumbers');
                    if (!empty($telephoneNumber)) {
                        return $this->phoneNumberUtil->format($telephoneNumber, PhoneNumberFormat::E164);
                    }
                }

                return null;
            case 'Email':
                if (null !== $applicationRequest->getPersonDetails()) {
                    return ReportHelper::mapContactPoints($applicationRequest->getPersonDetails()->getContactPoints(), 'emails');
                }

                return null;
            case 'UEN':
                if (null !== $applicationRequest->getCustomerType()
                    && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue()) {
                    if (null !== $applicationRequest->getCorporationDetails()) {
                        return ReportHelper::mapIdentifiers($applicationRequest->getCorporationDetails()->getIdentifiers(), IdentificationName::UNIQUE_ENTITY_NUMBER);
                    }
                }

                return null;
            case 'Company Name':
                return (null !== $applicationRequest->getCorporationDetails() && null !== $applicationRequest->getCustomerType()
                    && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue())
                    ? $applicationRequest->getCorporationDetails()->getName()
                    : null;
            case 'CP Customer ID':
                return (null !== $applicationRequest->getContactPerson() && null !== $applicationRequest->getCustomerType()
                    && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue())
                    ? $applicationRequest->getContactPerson()->getAccountNumber()
                    : null;
            case 'CP NRIC/FIN':
                if (null !== $applicationRequest->getContactPerson() && null !== $applicationRequest->getCustomerType()
                    && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue()) {
                    if (null !== $applicationRequest->getContactPerson()->getPersonDetails()) {
                        return ReportHelper::mapIdentifiers($applicationRequest->getContactPerson()->getPersonDetails()->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
                    }
                }

                return null;
            case 'CP Salutation':
                return (null !== $applicationRequest->getPersonDetails() && null !== $applicationRequest->getCustomerType()
                    && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue())
                    ? $applicationRequest->getPersonDetails()->getHonorificPrefix()
                    : null;
            case 'CP First Name':
                if (null !== $applicationRequest->getContactPerson() && null !== $applicationRequest->getCustomerType()
                    && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue()) {
                    if (null !== $applicationRequest->getContactPerson()->getPersonDetails()) {
                        return $applicationRequest->getContactPerson()->getPersonDetails()->getGivenName();
                    }
                }

                return null;
            case 'CP Full Name':
                if (null !== $applicationRequest->getContactPerson() && null !== $applicationRequest->getCustomerType()
                    && AccountType::CORPORATE === $applicationRequest->getCustomerType()->getValue()) {
                    if (null !== $applicationRequest->getContactPerson()->getPersonDetails()) {
                        return $applicationRequest->getContactPerson()->getPersonDetails()->getName();
                    }
                }

                return null;
            case 'Premise Address Postal Code':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                        return $address->getPostalCode();
                    }
                }

                return null;
            case 'Premise Address Unit No.':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                        return $address->getUnitNumber();
                    }
                }

                return null;
            case 'Premise Address Floor':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                        return $address->getFloor();
                    }
                }

                return null;
            case 'Premise Address House/Building No.':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                        return $address->getHouseNumber();
                    }
                }

                return null;
            case 'Premise Address Building Name':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                        return $address->getBuildingName();
                    }
                }

                return null;
            case 'Premise Address Street':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                        return $address->getStreetAddress();
                    }
                }

                return null;
            case 'Premise Address City':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                        return $address->getAddressLocality();
                    }
                }

                return null;
            case 'Premise Address Country':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                        return $address->getAddressCountry();
                    }
                }

                return null;
            case 'Mailing Address Postal Code':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                        return $address->getPostalCode();
                    }
                }

                return null;
            case 'Mailing Address Unit No.':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                        return $address->getUnitNumber();
                    }
                }

                return null;
            case 'Mailing Address Floor':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                        return $address->getFloor();
                    }
                }

                return null;
            case 'Mailing Address House/Building No.':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                        return $address->getHouseNumber();
                    }
                }

                return null;
            case 'Mailing Address Building Name':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                        return $address->getBuildingName();
                    }
                }

                return null;
            case 'Mailing Address Street':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                        return $address->getStreetAddress();
                    }
                }

                return null;
            case 'Mailing Address City':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                        return $address->getAddressLocality();
                    }
                }

                return null;
            case 'Mailing Address Country':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                        return $address->getAddressCountry();
                    }
                }

                return null;
            case 'Refund Address Postal Code':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::REFUND_ADDRESS === $address->getType()->getValue()) {
                        return $address->getPostalCode();
                    }
                }

                return null;
            case 'Refund Address Unit No.':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::REFUND_ADDRESS === $address->getType()->getValue()) {
                        return $address->getUnitNumber();
                    }
                }

                return null;
            case 'Refund Address Floor':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::REFUND_ADDRESS === $address->getType()->getValue()) {
                        return $address->getFloor();
                    }
                }

                return null;
            case 'Refund Address House/Building No.':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::REFUND_ADDRESS === $address->getType()->getValue()) {
                        return $address->getHouseNumber();
                    }
                }

                return null;
            case 'Refund Address Building Name':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::REFUND_ADDRESS === $address->getType()->getValue()) {
                        return $address->getBuildingName();
                    }
                }

                return null;
            case 'Refund Address Street':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::REFUND_ADDRESS === $address->getType()->getValue()) {
                        return $address->getStreetAddress();
                    }
                }

                return null;
            case 'Refund Address City':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::REFUND_ADDRESS === $address->getType()->getValue()) {
                        return $address->getAddressLocality();
                    }
                }

                return null;
            case 'Refund Address Country':
                foreach ($applicationRequest->getAddresses() as $address) {
                    if (PostalAddressType::REFUND_ADDRESS === $address->getType()->getValue()) {
                        return $address->getAddressCountry();
                    }
                }

                return null;
            case 'Remarks':
                return $applicationRequest->getRemark();
            case 'Status':
                $status = \str_replace('_', ' ', \ucwords(\strtolower($applicationRequest->getStatus()->getValue()), '_'));
                $status = \str_replace('Partner ', '', $status);

                return $status;
            case 'Termination Reason':
                return $applicationRequest->getTerminationReason();
            case 'Referral Source':
                return null !== $applicationRequest->getReferralSource()
                    ? \str_replace('_', ' ', \ucwords(\strtolower($applicationRequest->getReferralSource()->getValue()), '_'))
                    : null;
            case 'Indicate':
                return $applicationRequest->getSpecifiedReferralSource();
            case 'SP Account Holder':
                return true === $applicationRequest->isSelfApplication() ? 'YES' : 'NO';
            case 'E-Billing':
                return \in_array(BillSubscriptionType::ELECTRONIC, $applicationRequest->getBillSubscriptionTypes(), true) ? 'YES' : 'NO';
            case 'Agency':
                if ('PARTNERSHIP_PORTAL' === $applicationRequest->getSource() && null !== $applicationRequest->getAcquiredFrom()) {
                    if (AccountType::INDIVIDUAL === $applicationRequest->getAcquiredFrom()->getType()->getValue()) {
                        return null !== $applicationRequest->getAcquiredFrom()->getPersonDetails()
                            ? $applicationRequest->getAcquiredFrom()->getPersonDetails()->getName()
                            : null;
                    } elseif (AccountType::CORPORATE === $applicationRequest->getAcquiredFrom()->getType()->getValue()) {
                        return null !== $applicationRequest->getAcquiredFrom()->getCorporationDetails()
                            ? $applicationRequest->getAcquiredFrom()->getCorporationDetails()->getName()
                            : null;
                    }
                }

                return null;
            case 'Sales Representative':
                if (Source::PARTNERSHIP_PORTAL === $applicationRequest->getSource()) {
                    if (empty($applicationRequest->getSalesRepName())) {
                        return null !== $applicationRequest->getCreator() ? $applicationRequest->getCreator()->getCustomerName() : null;
                    }
                }

                return $applicationRequest->getSalesRepName();
            case 'Partner Code':
                if ('PARTNERSHIP_PORTAL' === $applicationRequest->getSource() && null !== $applicationRequest->getAcquiredFrom()) {
                    return $applicationRequest->getAcquiredFrom()->getAccountNumber();
                }

                return null;
            case 'Channel':
                $sourceUrl = $applicationRequest->getSourceUrl();

                if (null !== $sourceUrl) {
                    $sourceUri = HttpUri::createFromString($sourceUrl);
                    $query = new UriQuery($sourceUri->getFragment());

                    if ($query->hasPair('/?channel')) {
                        return \ucfirst($query->getPair('/?channel'));
                    }

                    return 'Portal';
                }

                return null;
            case 'Location Code':
                return $applicationRequest->getLocation();
            case 'Payment Mode':
                return null !== $applicationRequest->getPaymentMode()
                    ? $applicationRequest->getPaymentMode()->getValue()
                    : null;
            case 'Renewal Start Date':
                if (null !== $applicationRequest->getContract() && ApplicationRequestType::CONTRACT_RENEWAL === $applicationRequest->getType()->getValue()) {
                    return null !== $applicationRequest->getContract()->getStartDate()
                        ? $applicationRequest->getContract()->getStartDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                        : null;
                }

                return null;
            case 'Lock-In Date':
                return null !== $applicationRequest->getContract() && null !== $applicationRequest->getContract()->getLockInDate()
                    ? $applicationRequest->getContract()->getLockInDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Created Date/Time':
                if (null !== $applicationRequest->getDateCreated()) {
                    return $applicationRequest->getDateCreated()->setTimezone($this->timezone)->format('d-m-Y g:ia');
                }

                return null;
            case 'Promotion Code':
                return null !== $applicationRequest->getPromotion() ? $applicationRequest->getPromotion()->getPromotionNumber() : null;
            case 'Energy Rate':
                if (null !== $applicationRequest->getTariffRate()) {
                    if (null !== $applicationRequest->getTariffRate()->getTerms()) {
                        if ('STANDARD' === $applicationRequest->getTariffRate()->getTerms()->getPlanType()) {
                            return null !== $applicationRequest->getTariffRate()->getTerms()->getStandardPlan()
                            ? \strip_tags(\html_entity_decode($applicationRequest->getTariffRate()->getTerms()->getStandardPlan())) : null;
                        }

                        return null !== $applicationRequest->getTariffRate()->getTerms()->getNonStandardPlan()
                            ? \strip_tags(\html_entity_decode($applicationRequest->getTariffRate()->getTerms()->getNonStandardPlan())) : null;
                    }
                }

                return null;
            case 'Conditional Discounts':
                if (null !== $applicationRequest->getTariffRate()) {
                    return null !== $applicationRequest->getTariffRate()->getTerms() &&
                        null !== $applicationRequest->getTariffRate()->getTerms()->getDiscount()
                        ? \strip_tags(\html_entity_decode($applicationRequest->getTariffRate()->getTerms()->getDiscount())) : null;
                }

                return null;
            default:
                return null;
        }
    }

    private function mapCacheDbApplicationRequestData(ApplicationRequestReport $applicationRequest, string $header)
    {
        switch ($header) {
            case 'Application Request ID':
                return $applicationRequest->getApplicationRequestId();
            case 'Application Type':
                return $applicationRequest->getType();
            case 'Average Consumption':
                return  $applicationRequest->getAverageConsumption();
            case 'Customer ID':
                return null !== $applicationRequest->getCustomerDetails() ? $applicationRequest->getCustomerDetails()->getCustomerId() : null;
            case 'Customer Account':
                return $applicationRequest->getContract();
            case 'Contract Type':
                return $applicationRequest->getContractType();
            case 'Premise Type':
                return $applicationRequest->getPremiseType();
            case 'Industry':
                return $applicationRequest->getIndustry();
            case 'Tariff Rate Code':
                return $applicationRequest->getTariffRateCode();
            case 'Tariff Rate':
                return $applicationRequest->getTariffRate();
            case 'Referral Code':
                return $applicationRequest->getReferralCode();
            case 'Meter Option':
                return $applicationRequest->getMeterOption();
            case 'SP Account No.':
                return $applicationRequest->getSpAccountNumber();
            case 'Preferred Start Date':
                return null !== $applicationRequest->getPreferredStartDate()
                    ? $applicationRequest->getPreferredStartDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Preferred Turn Off Date':
                return null !== $applicationRequest->getPreferredEndDate()
                    ? $applicationRequest->getPreferredEndDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Self Read Option':
                return $applicationRequest->getSelfReadOption();
            case 'GIRO Application':
                return $applicationRequest->getGiroApplication();
            case 'Source':
                return $applicationRequest->getSource();
            case 'Category':
                return null !== $applicationRequest->getCustomerDetails() ? $applicationRequest->getCustomerDetails()->getCategory() : null;
            case 'Deposit':
                return $applicationRequest->getDeposit();
            case 'NRIC/FIN':
                return null !== $applicationRequest->getCustomerDetails() ? $applicationRequest->getCustomerDetails()->getIdentificationNumber() : null;
            case 'Salutation':
                return null !== $applicationRequest->getCustomerDetails() ? $applicationRequest->getCustomerDetails()->getSalutation() : null;
            case 'First Name':
                return null !== $applicationRequest->getCustomerDetails() ? $applicationRequest->getCustomerDetails()->getFirstName() : null;
            case 'Middle Name':
                return null !== $applicationRequest->getCustomerDetails() ? $applicationRequest->getCustomerDetails()->getMiddleName() : null;
            case 'Last Name':
                return null !== $applicationRequest->getCustomerDetails() ? $applicationRequest->getCustomerDetails()->getLastName() : null;
            case 'Full Name':
                return null !== $applicationRequest->getCustomerDetails() ? $applicationRequest->getCustomerDetails()->getFullName() : null;
            case 'Mobile No.':
                return null !== $applicationRequest->getCustomerDetails() ? $applicationRequest->getCustomerDetails()->getMobileNumber() : null;
            case 'Phone No.':
                return null !== $applicationRequest->getCustomerDetails() ? $applicationRequest->getCustomerDetails()->getPhoneNumber() : null;
            case 'Email':
                return null !== $applicationRequest->getCustomerDetails() ? $applicationRequest->getCustomerDetails()->getEmail() : null;
            case 'UEN':
                return $applicationRequest->getCorporateIdentificationNumber();
            case 'Company Name':
                return $applicationRequest->getCompanyName();
            case 'CP Customer ID':
                return null !== $applicationRequest->getContactPersonDetails() ? $applicationRequest->getContactPersonDetails()->getCustomerId() : null;
            case 'CP NRIC/FIN':
                return null !== $applicationRequest->getContactPersonDetails() ? $applicationRequest->getContactPersonDetails()->getIdentificationNumber() : null;
            case 'CP Salutation':
                return null !== $applicationRequest->getContactPersonDetails() ? $applicationRequest->getContactPersonDetails()->getSalutation() : null;
            case 'CP First Name':
                return null !== $applicationRequest->getContactPersonDetails() ? $applicationRequest->getContactPersonDetails()->getFirstName() : null;
            case 'CP Full Name':
                return null !== $applicationRequest->getContactPersonDetails() ? $applicationRequest->getContactPersonDetails()->getFullName() : null;
            case 'Premise Address Postal Code':
                return null !== $applicationRequest->getPremiseAddressDetails() ? $applicationRequest->getPremiseAddressDetails()->getPostalCode() : null;
            case 'Premise Address Unit No.':
                return null !== $applicationRequest->getPremiseAddressDetails() ? $applicationRequest->getPremiseAddressDetails()->getUnitNumber() : null;
            case 'Premise Address Floor':
                return null !== $applicationRequest->getPremiseAddressDetails() ? $applicationRequest->getPremiseAddressDetails()->getFloor() : null;
            case 'Premise Address House/Building No.':
                return null !== $applicationRequest->getPremiseAddressDetails() ? $applicationRequest->getPremiseAddressDetails()->getBuildingNumber() : null;
            case 'Premise Address Building Name':
                return null !== $applicationRequest->getPremiseAddressDetails() ? $applicationRequest->getPremiseAddressDetails()->getBuildingName() : null;
            case 'Premise Address Street':
                return null !== $applicationRequest->getPremiseAddressDetails() ? $applicationRequest->getPremiseAddressDetails()->getStreet() : null;
            case 'Premise Address City':
                return null !== $applicationRequest->getPremiseAddressDetails() ? $applicationRequest->getPremiseAddressDetails()->getCity() : null;
            case 'Premise Address Country':
                return null !== $applicationRequest->getPremiseAddressDetails() ? $applicationRequest->getPremiseAddressDetails()->getCountry() : null;
            case 'Mailing Address Postal Code':
                return null !== $applicationRequest->getMailingAddressDetails() ? $applicationRequest->getMailingAddressDetails()->getPostalCode() : null;
            case 'Mailing Address Unit No.':
                return null !== $applicationRequest->getMailingAddressDetails() ? $applicationRequest->getMailingAddressDetails()->getUnitNumber() : null;
            case 'Mailing Address Floor':
                return null !== $applicationRequest->getMailingAddressDetails() ? $applicationRequest->getMailingAddressDetails()->getFloor() : null;
            case 'Mailing Address House/Building No.':
                return null !== $applicationRequest->getMailingAddressDetails() ? $applicationRequest->getMailingAddressDetails()->getBuildingNumber() : null;
            case 'Mailing Address Building Name':
                return null !== $applicationRequest->getMailingAddressDetails() ? $applicationRequest->getMailingAddressDetails()->getBuildingName() : null;
            case 'Mailing Address Street':
                return null !== $applicationRequest->getMailingAddressDetails() ? $applicationRequest->getMailingAddressDetails()->getStreet() : null;
            case 'Mailing Address City':
                return null !== $applicationRequest->getMailingAddressDetails() ? $applicationRequest->getMailingAddressDetails()->getCity() : null;
            case 'Mailing Address Country':
                return null !== $applicationRequest->getMailingAddressDetails() ? $applicationRequest->getMailingAddressDetails()->getCountry() : null;
            case 'Refund Address Postal Code':
                return null !== $applicationRequest->getRefundAddressDetails() ? $applicationRequest->getRefundAddressDetails()->getPostalCode() : null;
            case 'Refund Address Unit No.':
                return null !== $applicationRequest->getRefundAddressDetails() ? $applicationRequest->getRefundAddressDetails()->getUnitNumber() : null;
            case 'Refund Address Floor':
                return null !== $applicationRequest->getRefundAddressDetails() ? $applicationRequest->getRefundAddressDetails()->getFloor() : null;
            case 'Refund Address House/Building No.':
                return null !== $applicationRequest->getRefundAddressDetails() ? $applicationRequest->getRefundAddressDetails()->getBuildingNumber() : null;
            case 'Refund Address Building Name':
                return null !== $applicationRequest->getRefundAddressDetails() ? $applicationRequest->getRefundAddressDetails()->getBuildingName() : null;
            case 'Refund Address Street':
                return null !== $applicationRequest->getRefundAddressDetails() ? $applicationRequest->getRefundAddressDetails()->getStreet() : null;
            case 'Refund Address City':
                return null !== $applicationRequest->getRefundAddressDetails() ? $applicationRequest->getRefundAddressDetails()->getCity() : null;
            case 'Refund Address Country':
                return null !== $applicationRequest->getRefundAddressDetails() ? $applicationRequest->getRefundAddressDetails()->getCountry() : null;
            case 'Remarks':
                return $applicationRequest->getRemarks();
            case 'Status':
                return $applicationRequest->getStatus();
            case 'Termination Reason':
                return $applicationRequest->getTerminationReason();
            case 'Referral Source':
                return $applicationRequest->getReferralSource();
            case 'Indicate':
                return $applicationRequest->getIndicate();
            case 'SP Account Holder':
                return $applicationRequest->getSelfApplication();
            case 'E-Billing':
                return $applicationRequest->getEBilling();
            case 'Agency':
                return $applicationRequest->getAgency();
            case 'Sales Representative':
                return $applicationRequest->getSalesRep();
            case 'Partner Code':
                return $applicationRequest->getPartnerCode();
            case 'Channel':
                return $applicationRequest->getChannel();
            case 'Location Code':
                return $applicationRequest->getLocationCode();
            case 'Payment Mode':
                return $applicationRequest->getPaymentMode();
            case 'Renewal Start Date':
                return null !== $applicationRequest->getRenewalStartDate()
                    ? $applicationRequest->getRenewalStartDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Lock-In Date':
                return null !== $applicationRequest->getLockInDate()
                    ? $applicationRequest->getLockInDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Created Date/Time':
                if (null !== $applicationRequest->getDateCreated()) {
                    return $applicationRequest->getDateCreated()->setTimezone($this->timezone)->format('d-m-Y g:ia');
                }

                return null;
            case 'Promotion Code':
                return $applicationRequest->getPromotionCode();
            default:
                return null;
        }
    }

    private function mapContractData(Contract $contract, string $header)
    {
        switch ($header) {
            case 'Customer ID':
                return $contract->getCustomer()->getAccountNumber();
            case 'Customer Name':
                if (null !== $contract->getCustomerType()) {
                    if (AccountType::INDIVIDUAL === $contract->getCustomerType()->getValue()) {
                        return null !== $contract->getCustomer()->getPersonDetails()
                            ? $contract->getCustomer()->getPersonDetails()->getName() : null;
                    } elseif (AccountType::CORPORATE === $contract->getCustomerType()->getValue()) {
                        return null !== $contract->getCustomer()->getCorporationDetails()
                            ? $contract->getCustomer()->getCorporationDetails()->getName() : null;
                    }
                }

                return null;
            case 'Customer Status':
                return $contract->getCustomer()->getStatus()->getValue();
            case 'Customer Account':
                return null === $contract->getContractNumber() ? $this->getRenewalContractNumber($contract) : $contract->getContractNumber();
            case 'Contract Status':
                if (null !== $contract->getEndDate()
                && (new \DateTime('now', new \DateTimeZone('UTC')) > $contract->getEndDate())) {
                    return ContractStatus::INACTIVE;
                }

                return $contract->getStatus()->getValue();
            case 'Contract Type':
                return $contract->getType()->getValue();
            case 'Contract Start Date':
                return null !== $contract->getStartDate()
                    ? $contract->getStartDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Contract End Date':
                return null !== $contract->getEndDate()
                    ? $contract->getEndDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Lock-In Date':
                return null !== $contract->getLockInDate()
                    ? $contract->getLockInDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Meter Type':
                return null !== $contract->getMeterType() ? $contract->getMeterType()->getValue() : null;
            case 'MSSL Number':
                return $contract->getMsslAccountNumber();
            case 'EBS Number':
                return $contract->getEbsAccountNumber();
            case 'Promotion Code':
                return null !== $contract->getTariffRate() ? $contract->getTariffRate()->getTariffRateNumber() : null;
            case 'Promotion Name':
                return null !== $contract->getTariffRate() ? $contract->getTariffRate()->getName() : null;
            case 'Category':
                return null !== $contract->getCustomerType() ? $contract->getCustomerType()->getValue() : null;
            case 'Premise Address Postal Code':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getPostalCode();
                    }
                }

                return null;
            case 'Premise Address Unit No.':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getUnitNumber();
                    }
                }

                return null;
            case 'Premise Address Floor':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getFloor();
                    }
                }

                return null;
            case 'Premise Address House/Building No.':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getHouseNumber();
                    }
                }

                return null;
            case 'Premise Address Building Name':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getBuildingName();
                    }
                }

                return null;
            case 'Premise Address Street':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getStreetAddress();
                    }
                }

                return null;
            case 'Premise Address City':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getAddressLocality();
                    }
                }

                return null;
            case 'Premise Address Country':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::PREMISE_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getAddressCountry();
                    }
                }

                return null;
            case 'Mailing Address Postal Code':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getPostalCode();
                    }
                }

                return null;
            case 'Mailing Address Unit No.':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getUnitNumber();
                    }
                }

                return null;
            case 'Mailing Address Floor':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getFloor();
                    }
                }

                return null;
            case 'Mailing Address House/Building No.':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getHouseNumber();
                    }
                }

                return null;
            case 'Mailing Address Building Name':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getBuildingName();
                    }
                }

                return null;
            case 'Mailing Address Street':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getStreetAddress();
                    }
                }

                return null;
            case 'Mailing Address City':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getAddressLocality();
                    }
                }

                return null;
            case 'Mailing Address Country':
                foreach ($contract->getAddresses() as $address) {
                    if (PostalAddressType::MAILING_ADDRESS === $address->getAddress()->getType()->getValue()) {
                        return $address->getAddress()->getAddressCountry();
                    }
                }

                return null;
            case 'Payment Method':
                return null !== $contract->getPaymentMode() ? $contract->getPaymentMode()->getValue() : null;
            case 'Created Date/Time':
                return null !== $contract->getDateCreated() ? $contract->getDateCreated()->setTimezone($this->timezone)->format('d-m-Y g:ia') : null;
            default:
                return null;
        }
    }

    private function mapCacheDbContractData(ContractReport $contract, string $header)
    {
        switch ($header) {
            case 'Customer ID':
                return null !== $contract->getCustomerDetails() ? $contract->getCustomerDetails()->getCustomerId() : null;
            case 'Customer Name':
                return null !== $contract->getCustomerDetails() ? $contract->getCustomerDetails()->getFullName() : null;
            case 'Customer Status':
                return null !== $contract->getCustomerDetails() ? $contract->getCustomerDetails()->getStatus() : null;
            case 'Customer Account':
                return $contract->getContractNumber();
            case 'Contract Status':
                return $contract->getStatus();
            case 'Contract Type':
                return $contract->getType();
            case 'Contract Start Date':
                return null !== $contract->getStartDate()
                    ? $contract->getStartDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Contract End Date':
                return null !== $contract->getEndDate()
                    ? $contract->getEndDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Lock-In Date':
                return null !== $contract->getLockInDate()
                    ? $contract->getLockInDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case 'Meter Type':
                return $contract->getMeterType();
            case 'MSSL Number':
                return $contract->getMsslNumber();
            case 'EBS Number':
                return $contract->getEbsNumber();
            case 'Promotion Code':
                return $contract->getPromotionCode();
            case 'Promotion Name':
                return $contract->getPromotionName();
            case 'Category':
                return $contract->getCategory();
            case 'Premise Address Postal Code':
                return null !== $contract->getPremiseAddressDetails() ? $contract->getPremiseAddressDetails()->getPostalCode() : null;
            case 'Premise Address Unit No.':
                return null !== $contract->getPremiseAddressDetails() ? $contract->getPremiseAddressDetails()->getUnitNumber() : null;
            case 'Premise Address Floor':
                return null !== $contract->getPremiseAddressDetails() ? $contract->getPremiseAddressDetails()->getFloor() : null;
            case 'Premise Address House/Building No.':
                return null !== $contract->getPremiseAddressDetails() ? $contract->getPremiseAddressDetails()->getBuildingNumber() : null;
            case 'Premise Address Building Name':
                return null !== $contract->getPremiseAddressDetails() ? $contract->getPremiseAddressDetails()->getBuildingName() : null;
            case 'Premise Address Street':
                return null !== $contract->getPremiseAddressDetails() ? $contract->getPremiseAddressDetails()->getStreet() : null;
            case 'Premise Address City':
                return null !== $contract->getPremiseAddressDetails() ? $contract->getPremiseAddressDetails()->getCity() : null;
            case 'Premise Address Country':
                return null !== $contract->getPremiseAddressDetails() ? $contract->getPremiseAddressDetails()->getCountry() : null;
            case 'Mailing Address Postal Code':
                return null !== $contract->getMailingAddressDetails() ? $contract->getMailingAddressDetails()->getPostalCode() : null;
            case 'Mailing Address Unit No.':
                return null !== $contract->getMailingAddressDetails() ? $contract->getMailingAddressDetails()->getUnitNumber() : null;
            case 'Mailing Address Floor':
                return null !== $contract->getMailingAddressDetails() ? $contract->getMailingAddressDetails()->getFloor() : null;
            case 'Mailing Address House/Building No.':
                return null !== $contract->getMailingAddressDetails() ? $contract->getMailingAddressDetails()->getBuildingNumber() : null;
            case 'Mailing Address Building Name':
                return null !== $contract->getMailingAddressDetails() ? $contract->getMailingAddressDetails()->getBuildingName() : null;
            case 'Mailing Address Street':
                return null !== $contract->getMailingAddressDetails() ? $contract->getMailingAddressDetails()->getStreet() : null;
            case 'Mailing Address City':
                return null !== $contract->getMailingAddressDetails() ? $contract->getMailingAddressDetails()->getCity() : null;
            case 'Mailing Address Country':
                return null !== $contract->getMailingAddressDetails() ? $contract->getMailingAddressDetails()->getCountry() : null;
            case 'Payment Method':
                return $contract->getPaymentMethod();
            case 'Created Date/Time':
                return null !== $contract->getDateCreated() ? $contract->getDateCreated()->setTimezone($this->timezone)->format('d-m-Y g:ia') : null;
            default:
                return null;
        }
    }

    private function mapCreditsActionReportData($creditsAction, string $header)
    {
        switch ($header) {
            case 'Customer ID':
                return $creditsAction->getObject()->getCustomer()->getAccountNumber();
            case 'Created Date':
                return null !== $creditsAction->getDateCreated()
                    ? $creditsAction->getDateCreated()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                    : null;
            case  'Transaction Type':
                if ($creditsAction instanceof RedeemCreditsAction) {
                    return 'RED';
                } elseif ($creditsAction instanceof EarnContractCreditsAction) {
                    return 'ACC';
                }

                return null;
            case 'Customer Name':
                if ($creditsAction instanceof RedeemCreditsAction || $creditsAction instanceof EarnContractCreditsAction) {
                    $customer = $creditsAction->getObject()->getCustomer();
                    if (AccountType::INDIVIDUAL === $customer->getType()->getValue()) {
                        if (null !== $customer->getPersonDetails()) {
                            return $customer->getPersonDetails()->getName();
                        }
                    } elseif (AccountType::CORPORATE === $customer->getType()->getValue()) {
                        if (null !== $customer->getCorporationDetails()) {
                            return $customer->getCorporationDetails()->getName();
                        }
                    }
                }

                return null;
            case 'Customer Account':
                if ($creditsAction instanceof RedeemCreditsAction) {
                    return $creditsAction->getObject()->getContractNumber();
                } elseif ($creditsAction instanceof EarnContractCreditsAction) {
                    return $creditsAction->getObject()->getContractNumber();
                }

                return null;
            case 'MSSL No':
                if ($creditsAction instanceof RedeemCreditsAction) {
                    return $creditsAction->getObject()->getMsslAccountNumber();
                } elseif ($creditsAction instanceof EarnContractCreditsAction) {
                    return $creditsAction->getObject()->getMsslAccountNumber();
                }

                return null;
            case 'EBS No':
                if ($creditsAction instanceof RedeemCreditsAction) {
                    return $creditsAction->getObject()->getEbsAccountNumber();
                } elseif ($creditsAction instanceof EarnContractCreditsAction) {
                    return $creditsAction->getObject()->getEbsAccountNumber();
                }

                return null;
            case 'Points':
                $isRedemption = $creditsAction instanceof CreditsSubtractionInterface;

                return  $isRedemption ? \sprintf('-%s', $creditsAction->getAmount()) : $creditsAction->getAmount();
            case 'Description':
                return $this->getCreditsActionDescription($creditsAction);
            default:
                return null;
        }
    }

    private function mapCustomerAccountRelationshipReportData(CustomerAccountRelationship $relationship, string $header, int $operationType)
    {
        switch ($header) {
            case 'Customer 1 ID':
                return 1 === $operationType ? $relationship->getFrom()->getAccountNumber()
                    : $relationship->getTo()->getAccountNumber();
            case 'Customer 1 Name':
                if (1 === $operationType) {
                    if (AccountType::INDIVIDUAL === $relationship->getFrom()->getType()->getValue()) {
                        return null !== $relationship->getFrom()->getPersonDetails()
                            ? $relationship->getFrom()->getPersonDetails()->getName()
                            : null;
                    } elseif (AccountType::CORPORATE === $relationship->getFrom()->getType()->getValue()) {
                        return null !== $relationship->getFrom()->getCorporationDetails()
                            ? $relationship->getFrom()->getCorporationDetails()->getName()
                            : null;
                    }
                } else {
                    if (AccountType::INDIVIDUAL === $relationship->getTo()->getType()->getValue()) {
                        return null !== $relationship->getTo()->getPersonDetails()
                            ? $relationship->getTo()->getPersonDetails()->getName()
                            : null;
                    } elseif (AccountType::CORPORATE === $relationship->getTo()->getType()->getValue()) {
                        return null !== $relationship->getTo()->getCorporationDetails()
                            ? $relationship->getTo()->getCorporationDetails()->getName()
                            : null;
                    }
                }

                return null;
            case 'Type':
                return 1 === $operationType ? 'Is a Contact Person' : 'Has a Contact Person';
            case 'Customer 2 ID':
                return 1 === $operationType ? $relationship->getTo()->getAccountNumber()
                    : $relationship->getFrom()->getAccountNumber();
            case 'Customer 2 Name':
                if (1 === $operationType) {
                    if (AccountType::INDIVIDUAL === $relationship->getTo()->getType()->getValue()) {
                        return null !== $relationship->getTo()->getPersonDetails()
                            ? $relationship->getTo()->getPersonDetails()->getName()
                            : null;
                    } elseif (AccountType::CORPORATE === $relationship->getTo()->getType()->getValue()) {
                        return null !== $relationship->getTo()->getCorporationDetails()
                            ? $relationship->getTo()->getCorporationDetails()->getName()
                            : null;
                    }
                } else {
                    if (AccountType::INDIVIDUAL === $relationship->getFrom()->getType()->getValue()) {
                        return null !== $relationship->getFrom()->getPersonDetails()
                            ? $relationship->getFrom()->getPersonDetails()->getName()
                            : null;
                    } elseif (AccountType::CORPORATE === $relationship->getFrom()->getType()->getValue()) {
                        return null !== $relationship->getFrom()->getCorporationDetails()
                            ? $relationship->getFrom()->getCorporationDetails()->getName()
                            : null;
                    }
                }

                return null;
            case 'Relating To Account':
                $contractNumbers = \array_map(function (Contract $contract) {return $contract->getContractNumber(); }, $relationship->getContracts());

                return \implode(', ', $contractNumbers);
            case 'Valid From':
                return null !== $relationship->getValidFrom()
                    ? $relationship->getValidFrom()->setTimezone($this->timezone)->format('d-m-Y')
                    : null;
            case 'Valid To':
                return null !== $relationship->getValidThrough()
                    ? $relationship->getValidThrough()->setTimezone($this->timezone)->format('d-m-Y')
                    : null;

            default:
                return null;
        }
    }

    private function mapCustomerAccountReportData(CustomerAccount $customerAccount, string $header)
    {
        switch ($header) {
            case 'Customer ID':
                return $customerAccount->getAccountNumber();
            case 'Customer Category':
                return $customerAccount->getType()->getValue();
            case 'Customer Type':
                $customerCategories = \array_filter($customerAccount->getCategories(), function ($c) {return \in_array($c, ['CONTACT_PERSON', 'CUSTOMER', 'NONCUSTOMER'], true); });
                $customerCategories = \array_map(function ($c) {return \str_replace('_', ' ', \ucwords(\strtolower($c), '_')); }, $customerCategories);

                return \implode(', ', $customerCategories);
            case 'Source':
                return null !== $customerAccount->getSource()
                    ? \str_replace('_', ' ', \ucwords(\strtolower($customerAccount->getSource()), '_'))
                    : null;
            case 'Company Name':
                if (AccountType::CORPORATE === $customerAccount->getType()->getValue()) {
                    return null !== $customerAccount->getCorporationDetails()
                        ? $customerAccount->getCorporationDetails()->getName()
                        : null;
                }

                return null;
            case 'Salutation':
                if (AccountType::INDIVIDUAL === $customerAccount->getType()->getValue()) {
                    return null !== $customerAccount->getPersonDetails()
                        ? $customerAccount->getPersonDetails()->getHonorificPrefix()
                        : null;
                }

                return null;
            case 'First Name':
                if (AccountType::INDIVIDUAL === $customerAccount->getType()->getValue()) {
                    return null !== $customerAccount->getPersonDetails()
                        ? $customerAccount->getPersonDetails()->getGivenName()
                        : null;
                }

                return null;
            case 'Middle Name':
                if (AccountType::INDIVIDUAL === $customerAccount->getType()->getValue()) {
                    return null !== $customerAccount->getPersonDetails()
                        ? $customerAccount->getPersonDetails()->getAdditionalName()
                        : null;
                }

                return null;
            case 'Last Name':
                if (AccountType::INDIVIDUAL === $customerAccount->getType()->getValue()) {
                    return null !== $customerAccount->getPersonDetails()
                        ? $customerAccount->getPersonDetails()->getFamilyName()
                        : null;
                }

                return null;
            case 'Full Name':
                if (AccountType::INDIVIDUAL === $customerAccount->getType()->getValue()) {
                    return null !== $customerAccount->getPersonDetails()
                        ? $customerAccount->getPersonDetails()->getName()
                        : null;
                }

                return null;
            case 'UEN / NRIC':
                if (AccountType::INDIVIDUAL === $customerAccount->getType()->getValue()) {
                    return null !== $customerAccount->getPersonDetails()
                        ? ReportHelper::mapIdentifiers($customerAccount->getPersonDetails()->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD)
                        : null;
                } elseif (AccountType::CORPORATE === $customerAccount->getType()->getValue()) {
                    return null !== $customerAccount->getCorporationDetails()
                        ? ReportHelper::mapIdentifiers($customerAccount->getCorporationDetails()->getIdentifiers(), IdentificationName::UNIQUE_ENTITY_NUMBER)
                        : null;
                }

                return null;
            case 'Preferred Contact Method':
                return null !== $customerAccount->getPreferredContactMethod()
                    ? $customerAccount->getPreferredContactMethod()->getValue()
                    : null;
            case 'Mobile No':
                if (null !== $customerAccount->getPersonDetails()) {
                    $mobileNumber = ReportHelper::mapContactPoints($customerAccount->getPersonDetails()->getContactPoints(), 'mobilePhoneNumbers');
                    if (!empty($mobileNumber)) {
                        return $this->phoneNumberUtil->format($mobileNumber, PhoneNumberFormat::E164);
                    }
                }

                return null;
            case 'Phone No':
                if (null !== $customerAccount->getPersonDetails()) {
                    $telephoneNumber = ReportHelper::mapContactPoints($customerAccount->getPersonDetails()->getContactPoints(), 'telephoneNumbers');
                    if (!empty($telephoneNumber)) {
                        return $this->phoneNumberUtil->format($telephoneNumber, PhoneNumberFormat::E164);
                    }
                }

                return null;
            case 'Fax No':
                if (null !== $customerAccount->getPersonDetails()) {
                    $faxNumber = ReportHelper::mapContactPoints($customerAccount->getPersonDetails()->getContactPoints(), 'faxNumbers');
                    if (!empty($faxNumber)) {
                        return $this->phoneNumberUtil->format($faxNumber, PhoneNumberFormat::E164);
                    }
                }

                return null;
            case 'Email':
                if (AccountType::CORPORATE === $customerAccount->getType()->getValue()) {
                    return null !== $customerAccount->getCorporationDetails()
                        ? ReportHelper::mapContactPoints($customerAccount->getCorporationDetails()->getContactPoints(), 'emails')
                        : null;
                }

                return null !== $customerAccount->getPersonDetails()
                    ? ReportHelper::mapContactPoints($customerAccount->getPersonDetails()->getContactPoints(), 'emails')
                    : null;
            case 'Referral Code':
                return null !== $customerAccount->getReferralCode() ? $customerAccount->getReferralCode() : null;
            case 'Gender':
                if (null !== $customerAccount->getPersonDetails()) {
                    return null !== $customerAccount->getPersonDetails()->getGender()
                        ? $customerAccount->getPersonDetails()->getGender()->getValue()
                        : null;
                }

                return null;
            case 'Marital Status':
                if (null !== $customerAccount->getPersonDetails()) {
                    return null !== $customerAccount->getPersonDetails()->getMaritalStatus()
                        ? $customerAccount->getPersonDetails()->getMaritalStatus()->getValue()
                        : null;
                }

                return null;
            case 'Date of Birth':
                if (null !== $customerAccount->getPersonDetails()) {
                    return null !== $customerAccount->getPersonDetails()->getBirthDate()
                        ? $customerAccount->getPersonDetails()->getBirthDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                        : null;
                }

                return null;
            case 'Place of Birth':
                return null !== $customerAccount->getPersonDetails()
                    ? $customerAccount->getPersonDetails()->getBirthPlace()
                    : null;
            case 'Date of Death':
                if (null !== $customerAccount->getPersonDetails()) {
                    return null !== $customerAccount->getPersonDetails()->getDeathDate()
                        ? $customerAccount->getPersonDetails()->getDeathDate()->setTimezone($this->timezone)->format('d-m-Y g:ia')
                        : null;
                }

                return null;
            case 'Nationality':
                return null !== $customerAccount->getPersonDetails()
                    ? $customerAccount->getPersonDetails()->getNationality()
                    : null;
            case 'Race':
                return null !== $customerAccount->getPersonDetails()
                    ? $customerAccount->getPersonDetails()->getNationality()
                    : null;
            case 'Preferred Language':
                return null !== $customerAccount->getPersonDetails() ? $customerAccount->getPersonDetails()->getPreferredLanguage() : null;
            case 'Languages':
                return null !== $customerAccount->getPersonDetails()
                    ? \implode(', ', $customerAccount->getPersonDetails()->getKnowsLanguages()) : null;
            case 'Status':
                return $customerAccount->getStatus()->getValue();
            case 'Blacklisted':
                return null !== $customerAccount->getDateBlacklisted() ? 'Yes' : 'NO';
            case 'Created Date/Time':
                return null !== $customerAccount->getDateCreated() ?
                    $customerAccount->getDateCreated()->setTimezone($this->timezone)->format('d-m-Y g:ia') : null;
            default:
                return null;
        }
    }

    private function mapLeadReportData(Lead $lead, string $header)
    {
        switch ($header) {
            case 'Lead ID':
                return $lead->getLeadNumber();
            case 'Contract Type':
                return (null !== $lead->getContractType()) ? $lead->getContractType()->getValue() : null;
            case 'Industry':
                if (null !== $lead->getContractType() && ContractType::COMMERCIAL === $lead->getContractType()->getValue()) {
                    return null !== $lead->getContractSubtype()
                        ? \str_replace('_', ' ', \ucwords(\strtolower($lead->getContractSubtype()), '_'))
                        : null;
                }

                return null;
            case 'Premise Type':
                return null !== $lead->getContractType()
                && ContractType::RESIDENTIAL === $lead->getContractType()->getValue()
                    ? $this->dataMapper->mapContractSubtype($lead->getContractSubtype()) : null;
            case 'Tariff Rate Code':
                return null !== $lead->getTariffRate() ? $lead->getTariffRate()->getTariffRateNumber() : null;
            case 'Tariff Rate':
                return null !== $lead->getTariffRate() ? $lead->getTariffRate()->getName() : null;
            case 'Meter Type':
                return (null !== $lead->getMeterType()) ? $lead->getMeterType()->getValue() : null;
            case 'Average Consumption':
                return $lead->getAverageConsumption()->getValue();
            case 'Average Consumption UOM':
                return ReportHelper::mapUnitCodes($lead->getAverageConsumption()->getUnitCode());
            case 'Purchase Time Frame':
                return (string) \floor($lead->getPurchaseTimeFrame()->getValue());
            case 'Score':
                return (null !== $lead->getScore()) ? $lead->getScore()->getValue() : null;
            case 'Source':
                return null !== $lead->getSource()
                    ? \str_replace('_', ' ', \ucwords(\strtolower($lead->getSource()), '_'))
                    : null;
            case 'Assignee':
                return (null !== $lead->getAssignee()) ? $lead->getAssignee()->getCustomerName() : null;
            case 'Category':
                return $lead->getType()->getValue();
            case 'NRIC/FIN':
                if (AccountType::INDIVIDUAL === $lead->getType()->getValue()) {
                    if (null !== $lead->getPersonDetails()) {
                        return ReportHelper::mapIdentifiers($lead->getPersonDetails()->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
                    }
                }

                return null;
            case 'Designation':
                return (null !== $lead->getPersonDetails() && AccountType::INDIVIDUAL === $lead->getType()->getValue()) ? $lead->getPersonDetails()->getJobTitle() : null;
            case 'Salutation':
                return (null !== $lead->getPersonDetails() && AccountType::INDIVIDUAL === $lead->getType()->getValue()) ? $lead->getPersonDetails()->getHonorificPrefix() : null;
            case 'Full Name':
                if (AccountType::CORPORATE === $lead->getType()->getValue()) {
                    if (null !== $lead->getCorporationDetails()) {
                        return null !== $lead->getCorporationDetails()->getName()
                            ? $lead->getCorporationDetails()->getName()
                            : $lead->getCorporationDetails()->getLegalName();
                    }

                    return null;
                }

                return null !== $lead->getPersonDetails() && AccountType::INDIVIDUAL === $lead->getType()->getValue() ? $lead->getPersonDetails()->getName() : null;
            case 'UEN':
                if (AccountType::CORPORATE === $lead->getType()->getValue()) {
                    if (null !== $lead->getCorporationDetails()) {
                        return ReportHelper::mapIdentifiers($lead->getCorporationDetails()->getIdentifiers(), IdentificationName::UNIQUE_ENTITY_NUMBER);
                    }
                }

                return null;
            case 'Company Name':
                return (null !== $lead->getCorporationDetails() && AccountType::CORPORATE === $lead->getType()->getValue()) ? $lead->getCorporationDetails()->getName() : null;
            case 'Legal Name':
                return (null !== $lead->getCorporationDetails() && AccountType::CORPORATE === $lead->getType()->getValue()) ? $lead->getCorporationDetails()->getLegalName() : null;
            case 'Website':
                return (null !== $lead->getCorporationDetails() && AccountType::CORPORATE === $lead->getType()->getValue()) ? $lead->getCorporationDetails()->getUrl() : null;
            case 'Contact Person NRIC/FIN':
                if (AccountType::CORPORATE === $lead->getType()->getValue()) {
                    if (null !== $lead->getPersonDetails()) {
                        return ReportHelper::mapIdentifiers($lead->getPersonDetails()->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
                    }
                }

                return null;
            case 'Contact Person Designation':
                return (null !== $lead->getPersonDetails() && AccountType::CORPORATE === $lead->getType()->getValue()) ? $lead->getPersonDetails()->getJobTitle() : null;
            case 'Contact Person Salutation':
                return (null !== $lead->getPersonDetails() && AccountType::CORPORATE === $lead->getType()->getValue()) ? $lead->getPersonDetails()->getHonorificPrefix() : null;
            case 'Contact Person Full Name':
                return (null !== $lead->getPersonDetails() && AccountType::CORPORATE === $lead->getType()->getValue()) ? $lead->getPersonDetails()->getName() : null;
            case 'Preferred Contact Method':
                return (null !== $lead->getPreferredContactMethod()) ? $lead->getPreferredContactMethod()->getValue() : null;
            case 'Mobile No':
                if (null !== $lead->getPersonDetails()) {
                    $mobileNumber = ReportHelper::mapContactPoints($lead->getPersonDetails()->getContactPoints(), 'mobilePhoneNumbers');
                    if (!empty($mobileNumber)) {
                        return $this->phoneNumberUtil->format($mobileNumber, PhoneNumberFormat::E164);
                    }
                }

                return null;
            case 'Phone No':
                if (null !== $lead->getPersonDetails()) {
                    $telephoneNumber = ReportHelper::mapContactPoints($lead->getPersonDetails()->getContactPoints(), 'telephoneNumbers');
                    if (!empty($telephoneNumber)) {
                        return $this->phoneNumberUtil->format($telephoneNumber, PhoneNumberFormat::E164);
                    }
                }

                return null;
            case 'Fax No':
                if (null !== $lead->getPersonDetails()) {
                    $faxNumber = ReportHelper::mapContactPoints($lead->getPersonDetails()->getContactPoints(), 'faxNumbers');
                    if (!empty($faxNumber)) {
                        return $this->phoneNumberUtil->format($faxNumber, PhoneNumberFormat::E164);
                    }
                }

                return null;
            case 'Email':
                if (null !== $lead->getPersonDetails()) {
                    return ReportHelper::mapContactPoints($lead->getPersonDetails()->getContactPoints(), 'emails');
                }

                return null;
            case 'Social Media Account':
                $urls = '';

                if (null !== $lead->getPersonDetails()) {
                    foreach ($lead->getPersonDetails()->getSameAsUrls() as $url) {
                        $urls .= $url.'\n';
                    }
                }

                return $urls;
            case 'Do Not Contact':
                if (null !== $lead->isDoNotContact()) {
                    if (true === $lead->isDoNotContact()) {
                        return 'Yes';
                    }

                    return 'No';
                }

                return null;
            case 'Is Existing Customer ?':
                if (null !== $lead->isExistingCustomer()) {
                    if (true === $lead->isExistingCustomer()) {
                        return 'Yes';
                    }

                    return 'No';
                }

                return null;
            case 'Are You A LPG User?':
                if (null !== $lead->isLpgUser()) {
                    if (true === $lead->isLpgUser()) {
                        return 'Yes';
                    }

                    return 'No';
                }

                return null;
            case 'Are You A Tenant?':
                if (null !== $lead->isTenant()) {
                    if (true === $lead->isTenant()) {
                        return 'Yes';
                    }

                    return 'No';
                }

                return null;
            case 'Address Type':
                foreach ($lead->getAddresses() as $address) {
                    return \str_replace('_', ' ', \ucwords(\strtolower($address->getType()->getValue()), '_'));
                }

                return null;
            case 'Postal Code':
                foreach ($lead->getAddresses() as $address) {
                    return $address->getPostalCode();
                }

                return null;
            case 'Floor':
                foreach ($lead->getAddresses() as $address) {
                    return $address->getFloor();
                }

                return null;
            case 'Unit No':
                foreach ($lead->getAddresses() as $address) {
                    return $address->getUnitNumber();
                }

                return null;
            case 'Building No':
                foreach ($lead->getAddresses() as $address) {
                    return $address->getHouseNumber();
                }

                return null;
            case 'Building Name':
                foreach ($lead->getAddresses() as $address) {
                    return $address->getBuildingName();
                }

                return null;
            case 'Street Address':
                foreach ($lead->getAddresses() as $address) {
                    return $address->getStreetAddress();
                }

                return null;
            case 'City':
                foreach ($lead->getAddresses() as $address) {
                    return $address->getAddressLocality();
                }

                return null;
            case 'State':
                foreach ($lead->getAddresses() as $address) {
                    return $address->getAddressRegion();
                }

                return null;
            case 'Country':
                foreach ($lead->getAddresses() as $address) {
                    return $address->getAddressCountry();
                }

                return null;
            case 'Note Type':
                foreach ($lead->getNotes() as $note) {
                    return $note->getType()->getValue();
                }

                return null;
            case 'Note':
                $allNotes = [];
                foreach ($lead->getNotes() as $note) {
                    $allNotes[] = $note->getText();
                }

                if (\count($allNotes) > 1) {
                    return \implode(', ', $allNotes);
                } elseif (\count($allNotes) > 0) {
                    return $allNotes[0];
                }

                return null;
            case 'Status':
                return $lead->getStatus()->getValue();
            case 'Created By':
                if (null !== $lead->getCreator()) {
                    return $lead->getCreator()->getCustomerName();
                }

                return null;
            case 'Created Date / Time':
                if (null !== $lead->getDateCreated()) {
                    return $lead->getDateCreated()->setTimezone($this->timezone)->format('d-m-Y g:ia');
                }

                return null;
            case 'Updated By':
                if (null !== $lead->getAgent()) {
                    return $lead->getAgent()->getCustomerName();
                }

                return null;
            case 'Updated Date / Time':
                if (null !== $lead->getDateModified()) {
                    return $lead->getDateModified()->setTimezone($this->timezone)->format('d-m-Y g:ia');
                }

                return null;
            case 'Followed Up Date / Time':
                if (null !== $lead->getDateFollowedUp()) {
                    return $lead->getDateFollowedUp()->setTimezone($this->timezone)->format('d-m-Y g:ia');
                }

                return null;
            case 'Referral Source':
                return null !== $lead->getReferralSource()
                    ? \str_replace('_', ' ', \ucwords(\strtolower($lead->getReferralSource()->getValue()), '_'))
                    : null;
            case 'Indicate':
                return $lead->getSpecifiedReferralSource();
            default:
                return null;
        }
    }

    private function mapOrderReportData(Order $order, string $header, OrderItem $orderItem = null)
    {
        switch ($header) {
            case 'Redemption Order Number':
                return $order->getOrderNumber();
            case 'Redemption Date':
                return null !== $order->getOrderDate() ? $order->getOrderDate()->setTimezone($this->timezone)
                    ->format('d-m-Y g:ia') : null;
            case 'Customer Name':
                if (AccountType::INDIVIDUAL === $order->getCustomer()->getType()->getValue()) {
                    if (null !== $order->getCustomer()->getPersonDetails()) {
                        return $order->getCustomer()->getPersonDetails()->getName();
                    }
                } elseif (AccountType::CORPORATE === $order->getCustomer()->getType()->getValue()) {
                    if (null !== $order->getCustomer()->getCorporationDetails()) {
                        return $order->getCustomer()->getCorporationDetails()->getName();
                    }
                }

                return null;
            case 'Customer Account':
                return $order->getObject()->getContractNumber();
            case 'MSSL No':
                return $order->getObject()->getMsslAccountNumber();
            case 'EBS No':
                return $order->getObject()->getEbsAccountNumber();
            case 'Total Points Redeemed':
                return null !== $order->getTotalPrice()->getPrice() ? $order->getTotalPrice()->getPrice() : null;
            case 'Product':
                if (null !== $orderItem) {
                    return $orderItem->getOfferListItem()->getItem()->getSku();
                }

                return null;
            case 'Points':
                if (null !== $orderItem) {
                    return $orderItem->getUnitPrice()->getPrice();
                }

                return null;
            case 'Quantity':
                if (null !== $orderItem) {
                    return $orderItem->getOrderQuantity()->getValue();
                }

                return null;
            default:
                return null;
        }
    }

    private function mapTicketReportData(Ticket $ticket, string $header)
    {
        $timer = $this->serviceLevelAgreementTimerCalculator->calculate($ticket);
        switch ($header) {
            case 'Case ID':
                return $ticket->getTicketNumber();
            case 'Customer ID':
                return null !== $ticket->getCustomer() ? $ticket->getCustomer()->getAccountNumber() : null;
            case 'Anonymous':
                return null === $ticket->getCustomer() ? 'YES' : 'NO';
            case 'Customer Account':
                return null !== $ticket->getContract() ? $ticket->getContract()->getContractNumber() : null;
            case 'Customer Name':
                if (null === $ticket->getCustomer()) {
                    return null !== $ticket->getPersonDetails() ? $ticket->getPersonDetails()->getName() : null;
                }

                return null !== $ticket->getCustomer()->getPersonDetails() ? $ticket->getCustomer()->getPersonDetails()->getName() : null;
            case 'MSSL No':
                return null !== $ticket->getContract() ? $ticket->getContract()->getMsslAccountNumber() : null;
            case 'EBS No':
                return null !== $ticket->getContract() ? $ticket->getContract()->getEbsAccountNumber() : null;
            case 'Channel':
                return (null !== $ticket->getChannel()) ? \str_replace('_', ' ', \ucwords(\strtolower($ticket->getChannel()), '_')) : '';
            case 'Source':
                return null !== $ticket->getSource()
                    ? \str_replace('_', ' ', \ucwords(\strtolower($ticket->getSource()), '_'))
                    : null;
            case 'Case Type':
                return $ticket->getType()->getName();
            case 'Main Category':
                return $ticket->getCategory()->getName();
            case 'Sub Category':
                return $ticket->getSubcategory()->getName();
            case 'Description':
                return $ticket->getDescription();
            case 'Status':
                return $ticket->getStatus()->getValue();
            case 'Priority':
                return $ticket->getPriority()->getValue();
            case 'SLA Breach':
                if (null !== $timer['timeLeft']) {
                    return \round($timer['timeLeft'], 2) < 0 ? 'YES' : 'NO';
                }

                return null;
            case 'Assigned To':
                return null !== $ticket->getAssignee() ? $ticket->getAssignee()->getCustomerName() : null;
            case 'Resolution Officer':
                return $ticket->getResolutionOfficer();
            case 'Incident Date':
                return  $ticket->getStartDate()->setTimezone($this->timezone)->format('d-m-Y g:ia');
            case 'Created At':
                if (null !== $ticket->getDateCreated()) {
                    return $ticket->getDateCreated()->setTimezone($this->timezone)->format('d-m-Y g:ia');
                }

                return null;
            default:
                return null;
        }
    }

    private function mapUserReportData(User $user, string $header)
    {
        switch ($header) {
            case 'Customer ID':
                return $user->getCustomerAccount()->getAccountNumber();
            case 'Customer Name':
                return $user->getCustomerName();
            case 'Email':
                return $user->getEmail();
            case 'Last Login':
                return null !== $user->getDateLastLogon() ? $user->getDateLastLogon()->setTimezone($this->timezone)->format('d-m-Y g:ia') : null;
            case 'Mobile':
                return true === $user->hasMobileDeviceLogin() ? 'YES' : 'NO';
            case 'Mobile No':
                if (null !== $user->getCustomerAccount()->getPersonDetails()) {
                    $mobilePhoneNumber = ReportHelper::mapContactPoints($user->getCustomerAccount()->getPersonDetails()->getContactPoints(), 'mobilePhoneNumbers');
                    if (!empty($mobilePhoneNumber)) {
                        return $this->phoneNumberUtil->format($mobilePhoneNumber, PhoneNumberFormat::E164);
                    }
                }

                return null;
            case 'Date Registered':
                return null !== $user->getDateCreated() ? $user->getDateCreated()->setTimezone($this->timezone)->format('d-m-Y g:ia') : null;
            default:
                return null;
        }
    }

    private function mapOfferListItemData(OfferListItem $offerListItem, string $header)
    {
        switch ($header) {
            case 'Partner Name':
                if (null !== $offerListItem->getItem()->getSeller()) {
                    return $offerListItem->getItem()->getSeller()->getName();
                }

                return null;
            case 'Product Name':
                return $offerListItem->getItem()->getName();
            case 'Product Category':
                return $offerListItem->getItem()->getCategory()->getName();
            case 'Product Type':
                return $offerListItem->getItem()->getType()->getValue();
            case 'SKU':
                return $offerListItem->getItem()->getSku();
            case 'Valid From':
                return $offerListItem->getItem()->getValidFrom();
            case 'Valid To':
                return $offerListItem->getItem()->getValidThrough();
            case 'Points':
                return $offerListItem->getPriceSpecification()->getPrice();
            case 'Amount':
                return $offerListItem->getMonetaryExchangeValue()->getValue();
            default:
                return null;
        }
    }

    private function getCreditsActionDescription(UpdateCreditsAction $creditsAction)
    {
        if ($creditsAction instanceof RedeemCreditsAction) {
            return "Redemption Order {$creditsAction->getInstrument()->getOrderNumber()}";
        } elseif ($creditsAction instanceof EarnContractCreditsAction) {
            $creditsScheme = $creditsAction->getScheme();

            if (null !== $creditsScheme) {
                switch ($creditsScheme->getSchemeId()) {
                    case 'GI':
                        return null !== $creditsAction->getDateCreated()
                            ? "GIRO deducted on {$creditsAction->getDateCreated()->setTimezone($this->timezone)->format('d-m-Y')}"
                            : null;
                    case 'RF':
                    case 'RC':
                        return null !== $creditsAction->getInstrument() && null !== $creditsAction->getInstrument()->getContactPerson()->getPersonDetails()
                            ? "Referral for {$creditsAction->getInstrument()->getContactPerson()->getPersonDetails()->getName()}"
                            : null;
                    case 'RN':
                        return null !== $creditsAction->getObject()->getContractNumber()
                            ? "Renewal Contract for {$creditsAction->getObject()->getContractNumber()}"
                            : null;
                    case 'NA':
                        return null !== $creditsAction->getObject()->getContractNumber()
                            ? "New Application for {$creditsAction->getObject()->getContractNumber()}"
                            : null;
                    case 'ES':
                        return 'GDollars transferred from ES Power';
                    default:
                        return null;
                }
            }
        }
    }

    private function convertIritoId(string $value)
    {
        $iriParts = \explode('/', $value);

        return $iriParts[\count($iriParts) - 1];
    }

    private function getAllObjectIdsFromIris(array $objectIris)
    {
        return \array_map([$this, 'convertIritoId'], $objectIris);
    }

    private function getRenewalContractNumber(Contract $contract): ?string
    {
        $updateAction = $this->entityManager->getRepository(UpdateContractAction::class)
            ->createQueryBuilder('u')->where('u.object = ?1')
            ->setParameter(1, $contract->getId())
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (null !== $updateAction && \count($updateAction) > 0) {
            $updateAction = $updateAction[0];

            if (null !== $updateAction && $updateAction instanceof UpdateContractAction) {
                return null !== $updateAction->getResult() ? $updateAction->getResult()->getContractNumber() : null;
            }

            return null;
        }

        return null;
    }
}
