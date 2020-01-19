<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Enworkz;

use App\Disque\JobType;
use App\Entity\AccountsReceivableInvoice;
use App\Entity\ApplicationRequest;
use App\Entity\ApplicationRequestStatusHistory;
use App\Entity\Contract;
use App\Entity\ContractBillingSummary;
use App\Entity\ContractConsumptionHistory;
use App\Entity\ContractFinancialHistory;
use App\Entity\ContractGiroHistory;
use App\Entity\ContractPostalAddress;
use App\Entity\CustomerAccount;
use App\Entity\MonetaryAmount;
use App\Entity\QuantitativeValue;
use App\Entity\Ticket;
use App\Entity\WithdrawCreditsAction;
use App\Enum\ApplicationRequestType;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use App\WebService\Billing\Constants;
use App\WebService\Billing\Enum\DownloadFileType;
use App\WebService\Billing\Enum\UploadFileType;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest\BuildAccountClosureApplicationRequestData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest\BuildContractApplicationRequestData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest\BuildContractRenewalApplicationRequestData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest\BuildGiroTerminationApplicationRequestData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest\BuildTransferOutApplicationRequestData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ContractPostalAddress\BuildMailingAddressData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\CustomerAccount\BuildContactUpdateData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\RedeemCreditsAction\BuildRedeemCreditsActionData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\Ticket\BuildCreateTaskData;
use Aws\S3\S3Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Tactician\CommandBus;
use League\Uri\Components\Query;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Modifiers\MergeQuery;
use League\Uri\Schemes\Ftp as FtpUri;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;

class Client implements WebServiceClient
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $authToken;

    /**
     * @var string|null
     */
    private $bucketName = null;

    /**
     * @var string
     */
    private $ftpUrl;

    /**
     * @var string
     */
    private $ftpUsername;

    /**
     * @var string
     */
    private $ftpPassword;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var S3Client
     */
    private $s3Client;

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
     * @var int
     */
    private $cutOffHour;

    public function __construct(array $config, \DateTimeZone $timezone, CommandBus $commandBus, S3Client $s3Client, LoggerInterface $logger)
    {
        $this->apiUrl = $config['url'];
        $this->authToken = $config['auth_token'];

        if (!empty($config['bucket_name'])) {
            $this->bucketName = $config['bucket_name'];
        }

        $this->ftpUrl = $config['ftp_url'];
        $this->ftpUsername = $config['ftp_username'];
        $this->ftpPassword = $config['ftp_password'];
        $this->timezone = $timezone;
        $this->commandBus = $commandBus;
        $this->s3Client = $s3Client;
        $this->logger = $logger;
        $this->baseUri = HttpUri::createFromString($this->apiUrl);
        $this->client = new GuzzleClient(['http_errors' => false]);

        // @todo CONFIG
        $this->cutOffHour = 21;
    }

    public function downloadXML(\DateTime $date, string $type)
    {
        $tempFile = \tmpfile();

        switch ($type) {
            case DownloadFileType::ACCOUNT_CLOSURE:
                $filenamePrefix = 'CRM_FRCClosure_STATUS_';
                $path = 'UpdateClosureStatus';
                break;
            case DownloadFileType::CONTRACT_APPLICATION:
                $filenamePrefix = 'CRM_FRCAPP_STATUS_';
                $path = 'UpdateApplicationStatus';
                break;
            case DownloadFileType::EVENT_ACTIVITY:
                $filenamePrefix = 'CRM_Event_Activity_';
                $path = 'EventActivity';
                break;
            case DownloadFileType::PROMOTION_CODE:
                $filenamePrefix = 'CRM_Promo_Update_';
                $path = 'PromoCode';
                break;
            case DownloadFileType::TRANSFER_OUT:
                $filenamePrefix = 'CRM_FRCTO_STATUS_';
                $path = 'UpdateTransferOutStatus';
                break;
            default:
                $this->logger->error('Unknown/unsupported type: '.$type);

                return $tempFile;
        }

        $ftpUri = FtpUri::createFromString($this->ftpUrl);
        $ftp = \ftp_ssl_connect($ftpUri->getHost(), $ftpUri->getPort() ?? 21);
        $dateSuffix = $date->format('Ymd');

        if (true === \ftp_login($ftp, $this->ftpUsername, $this->ftpPassword)) {
            \ftp_pasv($ftp, true);

            $filename = \sprintf('%s%s.xml', $filenamePrefix, $dateSuffix);
            $this->logger->info($filename);

            if (\ftp_size($ftp, \sprintf('%s/%s', $path, $filename)) < 1) {
                $this->logger->info('Filesize is less than 1.');
                \ftp_close($ftp);

                return $tempFile;
            }

            \ftp_get($ftp, \stream_get_meta_data($tempFile)['uri'], \sprintf('%s/%s', $path, $filename), FTP_BINARY);

            $now = new \DateTime();
            $now->setTimezone($this->timezone);
            $keyPrefix = \sprintf('%s/%s-', 'FTP-Incoming', $now->format('Ymd_Hi'));

            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucketName,
                'Body' => $tempFile,
                'Key' => \sprintf('%s%s', $keyPrefix, $filename),
                'ContentType' => 'text/xml; charset=utf8',
            ]);

            $this->logger->info($result);
        }
        \ftp_close($ftp);

        return $tempFile;
    }

    public function getAccountClosureStatusXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::ACCOUNT_CLOSURE);
    }

    public function getApplicationRequestStatusHistory(ApplicationRequest $applicationRequest)
    {
        $modifier = new AppendSegment('api/AppRequestStatus/RetrieveAppRequestStatus');
        $uri = $modifier->process($this->baseUri);
        $utcTimezone = new \DateTimeZone('UTC');
        $data = [];

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'auth-token' => $this->authToken,
        ];

        $transactionNumber = $applicationRequest->getApplicationRequestNumber();
        $transactionType = Constants::APPLICATION_REQUEST_TYPE_MAP[$applicationRequest->getType()->getValue()];

        $transactionData = [
            'TransactionNumber' => $transactionNumber,
            'TransactionType' => $transactionType,
        ];

        $this->logger->info('Sending POST to '.$uri.' : '.\json_encode($transactionData, JSON_PRETTY_PRINT));

        $submitRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($transactionData));
        $submitResponse = $this->client->send($submitRequest);
        $submitResult = \json_decode((string) $submitResponse->getBody(), true);

        if (!empty($submitResult['ProcessStatus'])) {
            foreach ($submitResult['Status'] as $statusData) {
                $date = new \DateTime($statusData['Date']);
                $dateCreated = new \DateTime($date->format('Y-m-d'), $this->timezone);
                $dateCreated->setTimezone($utcTimezone);

                $data[] = new ApplicationRequestStatusHistory($dateCreated, $statusData['Description']);
            }
        }

        $this->logger->info('Result from POST to '.$uri.' : '.\json_encode($submitResult, JSON_PRETTY_PRINT));

        return $data;
    }

    public function getARInvoice(string $invoiceNumber, ?Contract $contract = null, ?ApplicationRequest $applicationRequest = null)
    {
        $action = 'GetCustomerAccountInfo';
        $accountNumber = null;
        $data = null;
        $depositAmount = null;

        if (null !== $applicationRequest) {
            $accountNumber = $applicationRequest->getMsslAccountNumber();
            if (null === $accountNumber) {
                $accountNumber = $applicationRequest->getEbsAccountNumber();
            }
        }

        if (null === $accountNumber && null !== $contract) {
            $accountNumber = $contract->getMsslAccountNumber();
            if (null === $accountNumber) {
                $accountNumber = $contract->getEbsAccountNumber();
            }
        }

        try {
            $modifier = new AppendSegment('api/CustomerAccount/GetCustomerAccountInformation');
            $uri = $modifier->__invoke($this->baseUri);
            $query = Query::createFromPairs(['accountNumber' => $accountNumber]);
            $modifier = new MergeQuery($query->__toString());
            $uri = $modifier->__invoke($uri);

            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
                'auth-token' => $this->authToken,
            ];

            $this->logger->info('Sending GET to '.$uri);

            $billSumaryRequest = new GuzzlePsr7Request('GET', $uri, $headers);
            $billSumaryResponse = $this->client->send($billSumaryRequest);
            $billSumaryResult = \json_decode((string) $billSumaryResponse->getBody(), true);
            $info = $billSumaryResult;

            $pattern = '/"FileBytes":.*"/';
            $replacement = '"FileBytes": "base64 encoded string"';
            $this->logger->info('Result from GET to '.$uri.' : '.\preg_replace($pattern, $replacement, \json_encode($billSumaryResult, JSON_PRETTY_PRINT)));

            if (\count($info) > 0) {
                if (!empty($info['FinancialHistories'])) {
                    $financialHistories = $info['FinancialHistories'];

                    foreach ($financialHistories as $financialHistory) {
                        $amount = new MonetaryAmount((string) $financialHistory['Amount'], 'SGD');
                        $dateCreated = $this->getConvertedDate($financialHistory['DocumentDate']);
                        $totalBilledConsumption = new QuantitativeValue((string) $financialHistory['TotalBilledConsumption'], null, null, 'KWH');

                        if ('Invoice' === $financialHistory['DocumentType'] && $invoiceNumber === $financialHistory['ReferenceNumber']) {
                            if (!empty($financialHistory['Attachment'])) {
                                $attachment = $financialHistory['Attachment'];

                                $data = new AccountsReceivableInvoice($invoiceNumber, $attachment['FileName'], $attachment['ContentType'],
                                    (int) (\strlen(\rtrim($attachment['FileBytes'], '=')) * 3 / 4));
                            }
                        }
                    }
                }
            }

            return $data;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }
    }

    public function getARInvoiceAttachment(string $invoiceNumber, ?Contract $contract = null, ?ApplicationRequest $applicationRequest = null)
    {
        $action = 'GetCustomerAccountInfo';
        $accountNumber = null;
        $data = null;
        $depositAmount = null;

        if (null !== $applicationRequest) {
            $accountNumber = $applicationRequest->getMsslAccountNumber();
            if (null === $accountNumber) {
                $accountNumber = $applicationRequest->getEbsAccountNumber();
            }
        }

        if (null === $accountNumber && null !== $contract) {
            $accountNumber = $contract->getMsslAccountNumber();
            if (null === $accountNumber) {
                $accountNumber = $contract->getEbsAccountNumber();
            }
        }

        try {
            $modifier = new AppendSegment('api/CustomerAccount/GetCustomerAccountInformation');
            $uri = $modifier->__invoke($this->baseUri);
            $query = Query::createFromPairs(['accountNumber' => $accountNumber]);
            $modifier = new MergeQuery($query->__toString());
            $uri = $modifier->__invoke($uri);

            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
                'auth-token' => $this->authToken,
            ];

            $this->logger->info('Sending GET to '.$uri);

            $billSumaryRequest = new GuzzlePsr7Request('GET', $uri, $headers);
            $billSumaryResponse = $this->client->send($billSumaryRequest);
            $billSumaryResult = \json_decode((string) $billSumaryResponse->getBody(), true);
            $info = $billSumaryResult;

            $pattern = '/"FileBytes":.*"/';
            $replacement = '"FileBytes": "base64 encoded string"';
            $this->logger->info('Result from GET to '.$uri.\preg_replace($pattern, $replacement, \json_encode($billSumaryResult, JSON_PRETTY_PRINT)));

            if (\count($info) > 0) {
                if (!empty($info['FinancialHistories'])) {
                    $financialHistories = $info['FinancialHistories'];

                    foreach ($financialHistories as $financialHistory) {
                        $amount = new MonetaryAmount((string) $financialHistory['Amount'], 'SGD');
                        $dateCreated = $this->getConvertedDate($financialHistory['DocumentDate']);
                        $totalBilledConsumption = new QuantitativeValue((string) $financialHistory['TotalBilledConsumption'], null, null, 'KWH');

                        if ('Invoice' === $financialHistory['DocumentType'] && $invoiceNumber === $financialHistory['ReferenceNumber']) {
                            if (!empty($financialHistory['Attachment'])) {
                                $attachment = $financialHistory['Attachment'];
                                $result = $this->s3Client->putObject([
                                    'Bucket' => $this->bucketName,
                                    'Body' => \base64_decode($attachment['FileBytes'], true),
                                    'Key' => $attachment['FileName'],
                                    'ContentType' => $attachment['ContentType'],
                                ]);

                                $command = $this->s3Client->getCommand('GetObject', [
                                    'Bucket' => $this->bucketName,
                                    'Key' => $attachment['FileName'],
                                ]);
                                $request = $this->s3Client->createPresignedRequest($command, '+10 minutes');
                                // Get the actual presigned-url
                                $presignedUrl = (string) $request->getUri();

                                $data = new AccountsReceivableInvoice($invoiceNumber, $attachment['FileName'], $attachment['ContentType'],
                                    (int) (\strlen(\rtrim($attachment['FileBytes'], '=')) * 3 / 4), $presignedUrl);
                            }
                        }
                    }
                }
            }

            return $data;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }
    }

    public function getContractApplicationXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::CONTRACT_APPLICATION);
    }

    public function getContractArrearsHistory(Contract $contract)
    {
    }

    public function getContractBasicBillingSummary(Contract $contract)
    {
    }

    public function getContractBillingInformation(Contract $contract)
    {
    }

    public function getContractConsumptionsByBillingPeriod(Contract $contract)
    {
    }

    public function getContractEmailMessageHistories(Contract $contract)
    {
    }

    public function getContractFinancialHistory(Contract $contract)
    {
    }

    public function getContractGiroHistory(Contract $contract)
    {
    }

    public function getContractRenewalApplicationXMLFile(\DateTime $date)
    {
    }

    public function getContractBillingSummary(Contract $contract, ?ApplicationRequest $applicationRequest = null)
    {
        $action = 'GetCustomerAccountInfo';
        $accountNumber = null;
        $data = null;
        $depositAmount = null;

        if (null !== $applicationRequest) {
            $accountNumber = $applicationRequest->getMsslAccountNumber();
            if (null === $accountNumber) {
                $accountNumber = $applicationRequest->getEbsAccountNumber();
            }
        }

        if (null === $accountNumber) {
            $accountNumber = $contract->getMsslAccountNumber();
            if (null === $accountNumber) {
                $accountNumber = $contract->getEbsAccountNumber();
            }
        }

        try {
            $modifier = new AppendSegment('api/CustomerAccount/GetCustomerAccountInformation');
            $uri = $modifier->__invoke($this->baseUri);
            $query = Query::createFromPairs(['accountNumber' => $accountNumber]);
            $modifier = new MergeQuery($query->__toString());
            $uri = $modifier->__invoke($uri);

            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
                'auth-token' => $this->authToken,
            ];

            $this->logger->info('Sending GET to '.$uri);

            $billSumaryRequest = new GuzzlePsr7Request('GET', $uri, $headers);
            $billSumaryResponse = $this->client->send($billSumaryRequest);
            $billSumaryResult = \json_decode((string) $billSumaryResponse->getBody(), true);
            $info = $billSumaryResult;

            $pattern = '/"FileBytes":.*"/';
            $replacement = '"FileBytes": "base64 encoded string"';
            $this->logger->info('Result from GET to '.$uri.' : '.\preg_replace($pattern, $replacement, \json_encode($billSumaryResult, JSON_PRETTY_PRINT)));

            if (\count($info) > 0) {
                $depositAmount = new MonetaryAmount((string) $info['DepositAmount'], 'SGD');
                $outstandingBalance = new MonetaryAmount((string) $info['OutstandingBalance'], 'SGD');
                $invoiceDate = $this->getConvertedDate($info['LatestInvoicePrintOutDate']);

                //check current Giro Status
                $currentGiroAccountStatus = null;
                if (!empty($info['CurrentGIROAccountStatus'])) {
                    $currentGiroAccountStatus = $info['CurrentGIROAccountStatus'];
                }

                $billingSummary = new ContractBillingSummary((string) $info['CurrentArrearsStatus'], $currentGiroAccountStatus, $depositAmount, $invoiceDate, $outstandingBalance);

                if (!empty($info['ConsumptionSummaries'])) {
                    $consumptionSummaries = $info['ConsumptionSummaries'];

                    foreach ($consumptionSummaries as $consumptionHistory) {
                        $consumptionValue = new QuantitativeValue((string) $consumptionHistory['ConsumptionValue'], null, null, 'KWH');
                        $date = $this->getConvertedDate($consumptionHistory['ReadingDate']);
                        $meterNumber = null;

                        if (!empty($consumptionHistory['MeterNumber'])) {
                            $meterNumber = $consumptionHistory['MeterNumber'];
                        }

                        $billingSummary->addConsumptionHistory(
                            new ContractConsumptionHistory($consumptionValue, $date, null, $meterNumber)
                        );
                    }
                }

                if (!empty($info['FinancialHistories'])) {
                    $financialHistories = $info['FinancialHistories'];

                    foreach ($financialHistories as $financialHistory) {
                        $amount = new MonetaryAmount((string) $financialHistory['Amount'], 'SGD');
                        $dateCreated = $this->getConvertedDate($financialHistory['DocumentDate']);
                        $totalBilledConsumption = new QuantitativeValue((string) $financialHistory['TotalBilledConsumption'], null, null, 'KWH');

                        if ('Invoice' === $financialHistory['DocumentType']) {
                            $documentType = 'OARInvoice';
                        } else {
                            $documentType = 'OARCollection';
                        }

                        $billingSummary->addFinancialHistory(
                            new ContractFinancialHistory($amount, $dateCreated, $financialHistory['PaymentMode'], $financialHistory['PaymentStatus'], $financialHistory['ReferenceNumber'], $financialHistory['Status'], $totalBilledConsumption, $documentType)
                        );
                    }
                }

                if (!empty($info['GIROHistories'])) {
                    $giroHistories = $info['GIROHistories'];

                    foreach ($giroHistories as $giroHistory) {
                        $endDate = $this->getConvertedDate($giroHistory['GIROTerminateDate']);
                        $startDate = $this->getConvertedDate($giroHistory['GIROEffectiveDate']);

                        $billingSummary->addGiroHistory(
                            new ContractGiroHistory($giroHistory['BankAccount'], $giroHistory['Bank'], $endDate, $startDate, $giroHistory['Status'])
                        );
                    }
                }

                $this->logger->info(\json_encode($billingSummary, JSON_PRETTY_PRINT));
                $this->logger->info(\preg_replace($pattern, $replacement, \json_encode($billingSummary, JSON_PRETTY_PRINT)));
                $data = $billingSummary;
            }

            return $data;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }
    }

    public function getContractEmailHistory(Contract $contract, ?string $id = null)
    {
        if (null === $id) {
            return [];
        }

        return null;
    }

    public function getContractRCCSHistory(Contract $contract)
    {
        return null;
    }

    public function getContractWelcomePackage(Contract $contract)
    {
        return [];
    }

    public function getContractWelcomePackageAttachment(Contract $contract, int $fileKey)
    {
    }

    public function getCustomerBlackListXMLFile(\DateTime $date)
    {
    }

    public function getEventActivityXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::EVENT_ACTIVITY);
    }

    public function getFtpSchedule(string $type)
    {
        switch ($type) {
            case JobType::CRON_PROCESS_CONTRACT_APPLICATION:
                // extra 5 mins in case upload too slow on their end
                return [[22, 05, 0]];
            case JobType::CRON_UPLOAD_CONTRACT_APPLICATION_RECONCILIATION:
                return [[21, 0, 0]];
            case JobType::CRON_PROCESS_TARIFF_RATE_RECONCILIATION:
                // extra 5 mins in case upload too slow on their end
                return [[22, 10, 0]];
            default:
                return null;
        }
    }

    public function getGiroTerminationXMLFile(\DateTime $date)
    {
    }

    public function getMassContractApplicationRequestRenewalXMLFile(\DateTime $date)
    {
    }

    public function getMassContractApplicationRequestXMLFile(\DateTime $date)
    {
    }

    public function getMassContractClosureApplicationRequestXMLFile(\DateTime $date)
    {
    }

    public function getMassContractTransferOutApplicationRequestXMLFile(\DateTime $date)
    {
    }

    public function getPromotionCodeXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::PROMOTION_CODE);
    }

    public function getProviderName()
    {
        return 'Enworkz';
    }

    public function getRCCSTerminationXMLFile(\DateTime $date)
    {
    }

    public function getThirdPartyChargeConfiguration()
    {
    }

    public function createTask(Ticket $ticket, $fail = false)
    {
        $action = 'CreateTask';

        $modifier = new AppendSegment('TBC');
        $uri = $modifier->process($this->baseUri);

        $createTaskData = $this->commandBus->handle(new BuildCreateTaskData($ticket));

        if (false === $fail) {
            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
                'auth-token' => $this->authToken,
            ];

            $this->logger->info('Sending POST to '.$uri.' : '.\json_encode($createTaskData, JSON_PRETTY_PRINT));

            $submitRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($createTaskData));
            $submitResponse = $this->client->send($submitRequest);
            $submitResult = \json_decode((string) $submitResponse->getBody(), true);

            $this->logger->info('Result from POST to '.$uri.' : '.\json_encode($submitResult, JSON_PRETTY_PRINT));

            $this->addToReconciliationXML($createTaskData, $action);

            return \json_encode($submitResult);
        }

        $this->addToReconciliationXML($createTaskData, $action);

        return 'Force fail.';
    }

    public function getTransferOutApplicationXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::TRANSFER_OUT);
    }

    public function submitApplicationRequest(ApplicationRequest $applicationRequest, ?string $date = null, bool $fail = false)
    {
        $applicationRequestData = null;
        $uri = null;

        if (ApplicationRequestType::ACCOUNT_CLOSURE === $applicationRequest->getType()->getValue()) {
            $action = 'CreateFRCContractClosure';
            $modifier = new AppendSegment('api/ContractApplication/ContractClosure');
            $applicationRequestData = $this->commandBus->handle(new BuildAccountClosureApplicationRequestData($applicationRequest));
        } elseif (ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequest->getType()->getValue()) {
            $action = 'CreateFRCContractApplication';
            $modifier = new AppendSegment('api/ContractApplication/NewContractApplication');
            $applicationRequestData = $this->commandBus->handle(new BuildContractApplicationRequestData($applicationRequest));
        } elseif (ApplicationRequestType::GIRO_TERMINATION === $applicationRequest->getType()->getValue()) {
            $action = 'CreateGiroTermination';
            $modifier = new AppendSegment('api/ContractApplication/GiroTermination');
            $applicationRequestData = $this->commandBus->handle(new BuildGiroTerminationApplicationRequestData($applicationRequest));
        } elseif (ApplicationRequestType::TRANSFER_OUT === $applicationRequest->getType()->getValue()) {
            $action = 'CreateFRCTransferOut';
            $modifier = new AppendSegment('api/ContractApplication/TransferOutApplication');
            $applicationRequestData = $this->commandBus->handle(new BuildTransferOutApplicationRequestData($applicationRequest));
        } elseif (ApplicationRequestType::CONTRACT_RENEWAL === $applicationRequest->getType()->getValue()) {
            $action = 'CreateFRCReContract';
            $modifier = new AppendSegment('TBC');
            $applicationRequestData = $this->commandBus->handle(new BuildContractRenewalApplicationRequestData($applicationRequest));
        } else {
            return 'Unknown/unsupported type.';
        }

        $uri = $modifier->process($this->baseUri);

        if (false === $fail) {
            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
                'auth-token' => $this->authToken,
            ];

            $this->logger->info('Sending POST to '.$uri.' : '.\json_encode($applicationRequestData, JSON_PRETTY_PRINT));

            $submitRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($applicationRequestData));
            $submitResponse = $this->client->send($submitRequest);
            $submitResult = \json_decode((string) $submitResponse->getBody(), true);

            $this->logger->info('Result from POST to '.$uri.' : '.\json_encode($submitResult, JSON_PRETTY_PRINT));

            $this->addToReconciliationXML($applicationRequestData, $action, $date);

            return \json_encode($submitResult);
        }

        $this->addToReconciliationXML($applicationRequestData, $action, $date);

        return 'Force fail.';
    }

    public function submitRedeemCreditsActions(array $redeemedCreditsActions, bool $upload = true)
    {
        $billRebateRedeemedCreditsActionData = $this->commandBus->handle(new BuildRedeemCreditsActionData($redeemedCreditsActions));

        $this->addToXML($billRebateRedeemedCreditsActionData, 'CreateBillRedemption');

        if (true === $upload) {
            $now = new \DateTime();
            $now->setTimezone($this->timezone);

            $this->uploadXML($now, UploadFileType::BILL_REDEMPTION);
        }
    }

    public function submitWithdrawCreditsAction(WithdrawCreditsAction $withdrawCreditsAction, bool $fail = false)
    {
    }

    public function updateCustomerContact(CustomerAccount $customerAccount, ?string $previousName = null)
    {
        $modifier = new AppendSegment('TBC');
        $uri = $modifier->process($this->baseUri);
        $customerContactData = $this->commandBus->handle(new BuildContactUpdateData($customerAccount));

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'auth-token' => $this->authToken,
        ];

        $this->logger->info('Sending POST to '.$uri.' : '.\json_encode($customerContactData, JSON_PRETTY_PRINT));

        $submitRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($customerContactData));
        $submitResponse = $this->client->send($submitRequest);
        $submitResult = \json_decode((string) $submitResponse->getBody(), true);

        $this->logger->info('Result from POST to '.$uri.' : '.\json_encode($submitResult, JSON_PRETTY_PRINT));

        return $submitResult;
    }

    public function updateContractMailingAddress(ContractPostalAddress $contractPostalAddress)
    {
        $modifier = new AppendSegment('TBC');
        $uri = $modifier->process($this->baseUri);
        $contractMailingData = $this->commandBus->handle(new BuildMailingAddressData($contractPostalAddress));

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'auth-token' => $this->authToken,
        ];

        $this->logger->info('Sending POST to '.$uri.' : '.\json_encode($contractMailingData, JSON_PRETTY_PRINT));

        $submitRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($contractMailingData));
        $submitResponse = $this->client->send($submitRequest);
        $submitResult = \json_decode((string) $submitResponse->getBody(), true);

        $this->logger->info('Result from POST to '.$uri.' : '.\json_encode($submitResult, JSON_PRETTY_PRINT));

        return $submitResult;
    }

    public function uploadFailedApplicationRequestStatusUpdate(array $failedApplicationRequests, string $action)
    {
    }

    public function uploadCustomerBlacklistUpdateReturnFile(array $customerAccountsBlackListData)
    {
    }

    public function uploadReturnFile(array $data, \DateTime $date, string $type, bool $upload = true)
    {
    }

    public function uploadXML(\DateTime $date, string $type)
    {
        $dateSuffix = $date->format('Ymd');

        switch ($type) {
            case UploadFileType::CONTRACT_APPLICATION:
                $filenamePrefix = 'CRM_FRCAPP_';
                $path = 'ApplicationRequest';
                break;
            case UploadFileType::CONTRACT_APPLICATION_CUTOFF_LEFTOVER:
                $filenamePrefix = 'CRM_FRCAPP_';
                $path = 'ApplicationRequest';
                $dateSuffix .= '_'.$this->cutOffHour.'-00';
                break;
            case UploadFileType::BILL_REDEMPTION:
                $filenamePrefix = 'CRM_BillRebateRedemption_';
                $path = 'BillRebateRedemption';
                break;
            default:
                return 'Unknown/unsupported type: '.$type;
        }

        $filename = \sprintf('%s%s.xml', $filenamePrefix, $dateSuffix);

        if (true === $this->s3Client->doesObjectExist($this->bucketName, $filename)) {
            $result = $this->s3Client->getObject([
                'Bucket' => $this->bucketName,
                'Key' => $filename,
            ]);

            if (!empty($result['Body'])) {
                $tempFile = \tmpfile();
                \fwrite($tempFile, $result['Body']->getContents());
                $tempPath = \stream_get_meta_data($tempFile)['uri'];

                $ftpUri = FtpUri::createFromString($this->ftpUrl);
                $ftp = \ftp_ssl_connect($ftpUri->getHost(), $ftpUri->getPort() ?? 21);

                if (true === \ftp_login($ftp, $this->ftpUsername, $this->ftpPassword)) {
                    \ftp_pasv($ftp, true);
                    \ftp_put($ftp, \sprintf('%s/%s', $path, $filename), $tempPath, FTP_BINARY);
                }
                \ftp_close($ftp);
            }

            return null;
        }

        return \sprintf('File not found: %s', $filename);
    }

    private function addToReconciliationXML(array $applicationRequestData, ?string $action, ?string $dateSuffix = null)
    {
        switch ($action) {
            case 'CreateFRCContractApplication':
                $filenamePrefix = 'CRM_FRCAPP_';
                $applicationRequestNumberKey = 'CRMContractApplicationNumber';
                $objectNode = 'ContractApplication';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateFRCContractClosure':
                $filenamePrefix = 'CRM_FRCClosure_';
                $applicationRequestNumberKey = 'CRMContractClosureNumber';
                $objectNode = 'ContractClosure';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateFRCTransferOut':
                $filenamePrefix = 'CRM_FRCTO_';
                $applicationRequestNumberKey = 'CRMContractTransferOutNumber';
                $objectNode = 'ContractTransferOut';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateGiroTermination':
                $filenamePrefix = 'CRM_GIRO_Termination_Request_';
                $applicationRequestNumberKey = 'CRMGIROTerminationRequestNumber';
                $objectNode = 'GiroTermination';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateFRCReContract':
                $filenamePrefix = 'CRM_FRC_RENEW_';
                $applicationRequestNumberKey = 'CRMFRCReContractNumber';
                $objectNode = 'RenewContract';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            default:
                $this->logger->error(\sprintf('Weird action: %s', $action));

                return;
        }

        // for now only works with s3 buckets
        if (null !== $this->bucketName) {
            if (null === $dateSuffix) {
                $now = new \DateTime();
                $now->setTimezone($this->timezone);
                $dateSuffix = $now->format('Ymd');

                // @todo NO HARDCODE
                if ($now->format('H') >= $this->cutOffHour) {
                    $dateSuffix .= '_'.$this->cutOffHour.'-00';
                }
            }

            $filename = $filenamePrefix.$dateSuffix.'.xml';

            if (false === $this->s3Client->doesObjectExist($this->bucketName, $filename)) {
                $baseXML = new \SimpleXMLElement(\sprintf('<%s></%s>', $rootNode, $rootNode));
            } else {
                $baseXML = $this->s3Client->getObject([
                    'Bucket' => $this->bucketName,
                    'Key' => $filename,
                ]);

                $baseXML = new \SimpleXMLElement((string) $baseXML['Body']->getContents());
            }

            $exists = false;
            foreach ($baseXML->$objectNode as $existingFailed) {
                if ($applicationRequestData[$applicationRequestNumberKey] === $existingFailed->$applicationRequestNumberKey->__toString()) {
                    $exists = true;
                }
            }

            if (false === $exists) {
                $applicationRequestXMLNode = new \SimpleXMLElement(\sprintf('<%s></%s>', $objectNode, $objectNode));
                $this->addArrayToXML($applicationRequestData, $applicationRequestXMLNode);

                $baseDom = \dom_import_simplexml($baseXML);
                $applicationRequestDom = \dom_import_simplexml($applicationRequestXMLNode);

                $baseDom->appendChild($baseDom->ownerDocument->importNode($applicationRequestDom, true));
                $bodyString = $this->getCleanXMLString($baseXML);

                $doc = new \DOMDocument('1.0', 'UTF-8');
                $doc->preserveWhiteSpace = false;
                $doc->formatOutput = true;
                $doc->loadXML($bodyString);
                $xml = $doc->saveXML();

                $result = $this->s3Client->putObject([
                    'Bucket' => $this->bucketName,
                    'Body' => $xml,
                    'Key' => $filename,
                    'ContentType' => 'text/xml; charset=utf8',
                ]);

                $this->logger->info($result);
            } else {
                $this->logger->info('Application Request already in XML file.');
            }
        }
    }

    private function addArrayToXML(array $array, \SimpleXMLElement $xml)
    {
        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                if (!\is_numeric($key)) {
                    $subnode = $xml->addChild($key);
                    $this->addArrayToXML($value, $subnode);
                } else {
                    $subnode = $xml->addChild('multiItemSeparator');
                    $this->addArrayToXML($value, $subnode);
                }
            } else {
                $xml->addChild($key, \htmlspecialchars((string) $value));
            }
        }

        return $xml;
    }

    private function addToXML(array $data, ?string $action, ?string $dateSuffix = null)
    {
        switch ($action) {
            case 'CreateBillRedemption':
                $filenamePrefix = 'CRM_BillRebateRedemption_';
                $objectKey = 'RedemptionOrderNumber';
                $objectNode = 'BillRedemption';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            default:
                $this->logger->error(\sprintf('Weird action: %s', $action));

                return;
        }

        if (null !== $this->bucketName) {
            if (null === $dateSuffix) {
                $now = new \DateTime();
                $now->setTimezone($this->timezone);
                $dateSuffix = $now->format('Ymd');
            }

            $filename = $filenamePrefix.$dateSuffix.'.xml';

            if (false === $this->s3Client->doesObjectExist($this->bucketName, $filename)) {
                $baseXML = new \SimpleXMLElement(\sprintf('<%s></%s>', $rootNode, $rootNode));
                $bodyString = '';
            } else {
                $baseXML = $this->s3Client->getObject([
                    'Bucket' => $this->bucketName,
                    'Key' => $filename,
                ]);

                $baseXML = new \SimpleXMLElement((string) $baseXML['Body']->getContents());
                $bodyString = $this->getCleanXMLString($baseXML);
            }

            foreach ($data as $datum) {
                $exists = false;
                foreach ($baseXML->$objectNode as $existing) {
                    if ($datum[$objectKey] === $existing->$objectKey->__toString()) {
                        $exists = true;
                        break;
                    }
                }

                if (true === $exists) {
                    continue;
                }

                $xmlNode = new \SimpleXMLElement(\sprintf('<%s></%s>', $objectNode, $objectNode));
                $this->addArrayToXML($datum, $xmlNode);

                $baseDom = \dom_import_simplexml($baseXML);
                $xmlDom = \dom_import_simplexml($xmlNode);

                $baseDom->appendChild($baseDom->ownerDocument->importNode($xmlDom, true));
                $bodyString = $this->getCleanXMLString($baseXML);
            }

            $doc = new \DOMDocument('1.0', 'UTF-8');
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = true;
            $doc->loadXML($bodyString);
            $xml = $doc->saveXML();

            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucketName,
                'Body' => $xml,
                'Key' => $filename,
                'ContentType' => 'text/xml; charset=utf8',
            ]);

            $this->logger->info($result);
        }
    }

    private function getCleanXMLString(\SimpleXMLElement $xml)
    {
        $xml = \str_replace('<multiItemSeparator>', '', $xml->asXML());
        $xml = \str_replace('</multiItemSeparator>', '', $xml);

        return \str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="utf-8"?>', $xml);
    }

    private function getConvertedDate(?string $dateString)
    {
        if (null === $dateString) {
            return $dateString;
        }

        $date = new \DateTime($dateString);
        $utcTimezone = new \DateTimeZone('UTC');
        $convertedDate = new \DateTime($date->format('Y-m-d'), $this->timezone);
        $convertedDate->setTimezone($utcTimezone);

        return $convertedDate;
    }
}
