<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle;

use App\Disque\JobType;
use App\Entity\AccountsReceivableInvoice;
use App\Entity\ApplicationRequest;
use App\Entity\ApplicationRequestStatusHistory;
use App\Entity\Contract;
use App\Entity\ContractArrearsHistory;
use App\Entity\ContractBillingSummary;
use App\Entity\ContractConsumptionHistory;
use App\Entity\ContractEmailHistory;
use App\Entity\ContractFinancialHistory;
use App\Entity\ContractGiroHistory;
use App\Entity\ContractPostalAddress;
use App\Entity\ContractRccsHistory;
use App\Entity\ContractWelcomePackage;
use App\Entity\CustomerAccount;
use App\Entity\MonetaryAmount;
use App\Entity\QuantitativeValue;
use App\Entity\Ticket;
use App\Entity\WithdrawCreditsAction;
use App\Enum\ApplicationRequestType;
use App\WebService\Billing\ClientInterface as WebServiceClientInterface;
use App\WebService\Billing\Constants;
use App\WebService\Billing\Enum\DownloadFileType;
use App\WebService\Billing\Enum\UploadFileType;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildAccountClosureApplicationRequestData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildContractApplicationRequestData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildContractRenewalApplicationRequestData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildGiroTerminationApplicationRequestData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildRCCSTerminationApplicationRequestData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildTransferOutApplicationRequestData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\Contract\ConvertContractBillingSummary;
use App\WebService\Billing\Provider\Anacle\Domain\Command\Contract\ConvertContractGiroHistory;
use App\WebService\Billing\Provider\Anacle\Domain\Command\Contract\ConvertContractRCCSHistory;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ContractPostalAddress\BuildMailingAddressData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\CustomerAccount\BuildContactUpdateData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\RedeemCreditsAction\BuildRedeemCreditsActionData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\Ticket\BuildCreateTaskData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\WithdrawCreditsAction\BuildWithdrawCreditsTransactionData;
use Aws\S3\S3Client;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;

class Client implements WebServiceClientInterface
{
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
     * @var string
     */
    private $soapAccountInfoAlternativeUrl;

    /**
     * @var string
     */
    private $soapApplicationRequestAlternativeUrl;

    /**
     * @var string
     */
    private $soapAttachmentAlternativeUrl;

    /**
     * @var string
     */
    private $soapUrl;

    /**
     * @var string
     */
    private $soapUsername;

    /**
     * @var string
     */
    private $soapPassword;

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
     * @var string
     */
    private $soapNamespace;

    /**
     * @var \SoapClient
     */
    private $soapClient;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var bool
     */
    private $ftpUploadEnabled;

    public function __construct(array $config, \DateTimeZone $timezone, CommandBus $commandBus, S3Client $s3Client, LoggerInterface $logger)
    {
        if (!empty($config['bucket_name'])) {
            $this->bucketName = $config['bucket_name'];
        }

        $this->ftpUrl = $config['ftp_url'];
        $this->ftpUsername = $config['ftp_username'];
        $this->ftpPassword = $config['ftp_password'];
        $this->soapAccountInfoAlternativeUrl = $config['account_info_alternative_url'];
        $this->soapApplicationRequestAlternativeUrl = $config['application_request_alternative_url'];
        $this->soapAttachmentAlternativeUrl = $config['attachment_alternative_url'];
        $this->soapUrl = $config['url'];
        $this->soapUsername = $config['username'];
        $this->soapPassword = $config['password'];
        $this->commandBus = $commandBus;
        $this->s3Client = $s3Client;
        $this->logger = $logger;
        $this->timezone = $timezone;
        $this->ftpUploadEnabled = 'true' === $config['ftp_upload_enabled'] ? true : false;

        $this->soapNamespace = 'mybill.sg';
    }

    public function downloadXML(\DateTime $date, string $type)
    {
        $tempFile = \tmpfile();

        switch ($type) {
            case DownloadFileType::ACCOUNT_CLOSURE:
                $filenamePrefix = 'CRM_FRCClosure_STATUS_';
                break;
            case DownloadFileType::CONTRACT_APPLICATION:
                $filenamePrefix = 'CRM_FRCAPP_STATUS_';
                break;
            case DownloadFileType::CONTRACT_APPLICATION_RETURN:
                $filenamePrefix = 'CRM_FRCAPP_RETURN_';
                break;
            case DownloadFileType::CONTRACT_RENEWAL_APPLICATION:
                $filenamePrefix = 'CRM_FRC_RENEW_STATUS_';
                break;
            case DownloadFileType::CUSTOMER_BLACKLIST:
                $filenamePrefix = 'CRMCustomer_Blacklist_';
                break;
            case DownloadFileType::EVENT_ACTIVITY:
                $filenamePrefix = 'CRM_Event_Activity_';
                break;
            case DownloadFileType::EXISTING_CUSTOMER_REFUND_RETURN:
                $filenamePrefix = 'CRM_FRCExistingCustomerRefund_RETURN_';
                break;
            case DownloadFileType::GIRO_TERMINATION:
                $filenamePrefix = 'CRM_GIRO_Termination_Status_';
                break;
            case DownloadFileType::MASS_ACCOUNT_CLOSURE:
                $filenamePrefix = 'CRM_FRCClosure_CRM_';
                break;
            case DownloadFileType::MASS_ACCOUNT_TRANSFER_OUT:
                $filenamePrefix = 'CRM_FRCTO_CRM_';
                break;
            case DownloadFileType::MASS_CONTRACT_APPLICATION:
                $filenamePrefix = 'CRM_FRCAPP_CRM_';
                break;
            case DownloadFileType::MASS_CONTRACT_APPLICATION_RENEWAL:
                $filenamePrefix = 'CRM_FRC_RENEW_CRM_';
                break;
            case DownloadFileType::NONEXISTING_CUSTOMER_REFUND_RETURN:
                $filenamePrefix = 'CRM_FRCNonExistingCustomerRefund_RETURN_';
                break;
            case DownloadFileType::PROMOTION_CODE:
                $filenamePrefix = 'CRM_Promo_Update_';
                break;
            case DownloadFileType::RCCS_TERMINATION:
                $filenamePrefix = 'CRM_RCCS_Termination_Status_';
                break;
            case DownloadFileType::TRANSFER_OUT:
                $filenamePrefix = 'CRM_FRCTO_STATUS_';
                break;
            default:
                $this->logger->error('Unknown/unsupported type: '.$type);

                return $tempFile;
        }

        $ftp = \ftp_connect($this->ftpUrl);
        $dateSuffix = $date->format('Ymd');
        $path = 'Outgoing';

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
        $action = 'GetActivityHistory';
        $data = [];

        $transactionNumber = $applicationRequest->getApplicationRequestNumber();
        $transactionType = Constants::APPLICATION_REQUEST_TYPE_MAP[$applicationRequest->getType()->getValue()];

        try {
            $soapVar = new \SoapVar([
                'TransactionNumber' => $transactionNumber,
                'TransactionType' => $transactionType,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);

            if (!empty($result->GetActivityHistoryResult) && 'Success.' === $result->GetActivityHistoryResult->Message) {
                if (\is_array($result->GetActivityHistoryResult->ActivityHisotryList->ActivityHistory)) {
                    $activityHistories = $result->GetActivityHistoryResult->ActivityHisotryList->ActivityHistory;
                } else {
                    $activityHistories = $result->GetActivityHistoryResult->ActivityHisotryList;
                }

                foreach ($activityHistories as $activityHistory) {
                    $dateCreated = $this->getConvertedDate($activityHistory->DateTime);
                    $data[] = new ApplicationRequestStatusHistory($dateCreated, $activityHistory->Status);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }

        return $data;
    }

    public function getARInvoice(string $invoiceNumber, ?Contract $contract = null, ?ApplicationRequest $applicationRequest = null)
    {
        $action = 'GetARInvoice';
        $data = null;

        if (!empty($this->soapAttachmentAlternativeUrl)) {
            $this->soapUrl = $this->soapAttachmentAlternativeUrl;
        }

        $this->logger->info(\sprintf('Submitting %s', $action));

        try {
            $soapVar = new \SoapVar([
                'invoiceNumber' => $invoiceNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);

            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);

            if (!empty($result->GetARInvoiceResult) && !empty($result->GetARInvoiceResult->Attachment)) {
                $attachment = $result->GetARInvoiceResult->Attachment;
                $data = new AccountsReceivableInvoice($invoiceNumber, $attachment->FileName, $attachment->ContentType,
                    (int) (\strlen(\rtrim($attachment->FileBytes, '=')) * 3 / 4));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }

        return $data;
    }

    public function getARInvoiceAttachment(string $invoiceNumber, ?Contract $contract = null, ?ApplicationRequest $applicationRequest = null)
    {
        $action = 'GetARInvoice';
        $data = null;

        if (!empty($this->soapAttachmentAlternativeUrl)) {
            $this->soapUrl = $this->soapAttachmentAlternativeUrl;
        }

        $this->logger->info(\sprintf('Submitting %s', $action));

        try {
            $soapVar = new \SoapVar([
                'invoiceNumber' => $invoiceNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);

            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);

            if (!empty($result->GetARInvoiceResult) && !empty($result->GetARInvoiceResult->Attachment)) {
                $attachment = $result->GetARInvoiceResult->Attachment;
                $now = new \DateTime();
                $s3FileNameKey = $now->format('Ymd').'_Invoice-'.$invoiceNumber;

                if (false === $this->s3Client->doesObjectExist($this->bucketName, $s3FileNameKey)) {
                    $this->s3Client->putObject([
                        'Bucket' => $this->bucketName,
                        'Body' => \base64_decode($attachment->FileBytes, true),
                        'Key' => $s3FileNameKey,
                        'ContentType' => $attachment->ContentType,
                    ]);
                }

                $command = $this->s3Client->getCommand('GetObject', [
                    'Bucket' => $this->bucketName,
                    'Key' => $s3FileNameKey,
                ]);
                $request = $this->s3Client->createPresignedRequest($command, '+10 minutes');
                // Get the actual presigned-url
                $presignedUrl = (string) $request->getUri();

                $data = new AccountsReceivableInvoice($invoiceNumber, $attachment->FileName, $attachment->ContentType,
                    (int) (\strlen(\rtrim($attachment->FileBytes, '=')) * 3 / 4), $presignedUrl);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }

        return $data;
    }

    public function getContractApplicationXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::CONTRACT_APPLICATION);
    }

    public function getContractBillingInformation(Contract $contract)
    {
        if (!empty($this->soapAccountInfoAlternativeUrl)) {
            $this->soapUrl = $this->soapAccountInfoAlternativeUrl;
        }

        $data = null;
        $giroAccountStatus = null;
        $invoiceDate = null;
        $outstandingBalance = null;
        $depositAmount = new MonetaryAmount();
        $paymentMode = null;
        $arrearsStatus = 0;
        $contractNumber = $contract->getContractNumber();

        try {
            $action = 'GetCustomerAccountInfoBasic';
            $soapVar = new \SoapVar([
                'AccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);

            if (!empty($result->GetCustomerAccountInfoBasicResult)) {
                $info = $result->GetCustomerAccountInfoBasicResult;
                $giroAccountStatus = $info->CurrentGIROAccountStatus;

                $depositAmount = new MonetaryAmount($info->DepositAmount, 'SGD');
                try {
                    $invoiceDate = $this->getConvertedDate($info->LatestInvoicePrintOutDate ?? null);
                } catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                    $invoiceDate = null;
                }
            }

            //Arrears Status
            $action = 'GetCustomerCurrentArrearsStatus';
            $soapVar = new \SoapVar([
                'CustomerCode' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);

            if (!empty($result->GetCustomerCurrentArrearsStatusResult)) {
                $arrearsStatus = $result->GetCustomerCurrentArrearsStatusResult;
            }

            //Payment Mode.
            $action = 'GetCustomerAccountPaymentMode';
            $soapVar = new \SoapVar([
                'customerAccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $paymentModeResult = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $paymentModeResult);

            if (!empty($paymentModeResult->GetCustomerAccountPaymentModeResult)) {
                $paymentMode = $paymentModeResult->GetCustomerAccountPaymentModeResult->PaymentMode;
            }

            //Outstanding Balance
            $action = 'GetCustomerAccountInfoOutstandingBalance';
            $soapVar = new \SoapVar([
                'AccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);

            if (!empty($result->GetCustomerAccountInfoOutstandingBalanceResult)) {
                $outstandingBalance = new MonetaryAmount($result->GetCustomerAccountInfoOutstandingBalanceResult, 'SGD');
            } else {
                $outstandingBalance = new MonetaryAmount('0.00', 'SGD');
            }

            $billingSummary = new ContractBillingSummary((string) $arrearsStatus, $giroAccountStatus, $depositAmount, $invoiceDate, $outstandingBalance, $paymentMode);

            $data = $this->commandBus->handle(new ConvertContractBillingSummary($billingSummary));
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
        }

        return $data;
    }

    public function getContractBasicBillingSummary(Contract $contract)
    {
        $action = 'GetCustomerAccountInfoBasic';
        $data = null;

        $contractNumber = $contract->getContractNumber();
        try {
            $soapVar = new \SoapVar([
                'AccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);

            if (!empty($result->GetCustomerAccountInfoBasicResult)) {
                $info = $result->GetCustomerAccountInfoBasicResult;

                $depositAmount = new MonetaryAmount($info->DepositAmount, 'SGD');
                try {
                    $invoiceDate = $this->getConvertedDate($info->LatestInvoicePrintOutDate ?? null);
                } catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                    $invoiceDate = null;
                }

                $billingSummary = new ContractBillingSummary($info->CurrentArrearsStatus ?? null, $info->CurrentGIROAccountStatus, $depositAmount, $invoiceDate, null);

                $data = $this->commandBus->handle(new ConvertContractBillingSummary($billingSummary));
            }
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
        }

        return $data;
    }

    public function getContractArrearsHistory(Contract $contract)
    {
        if (!empty($this->soapAccountInfoAlternativeUrl)) {
            $this->soapUrl = $this->soapAccountInfoAlternativeUrl;
        }
        $action = 'GetCustomerAccountInfoArrearHistories';
        $data = null;

        $contractNumber = $contract->getContractNumber();

        try {
            $soapVar = new \SoapVar([
                'AccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $info = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $info);

            if (!empty($info->ArrearHistories)) {
                if (\is_array($info->ArrearHistories->ArrearHistory)) {
                    $arrearHistories = $info->ArrearHistories->ArrearHistory;
                } else {
                    $arrearHistories = $info->ArrearHistories;
                }

                $data = [];
                foreach ($arrearHistories as $arrearHistory) {
                    $date = $this->getConvertedDate($arrearHistory->Date ?? null);
                    $data[] = new ContractArrearsHistory($date, $arrearHistory->ArrearStatus);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $data;
    }

    public function getContractFinancialHistory(Contract $contract)
    {
        if (!empty($this->soapAccountInfoAlternativeUrl)) {
            $this->soapUrl = $this->soapAccountInfoAlternativeUrl;
        }
        $action = 'GetCustomerAccountInfoFinancialHistories';
        $data = null;

        $contractNumber = $contract->getContractNumber();

        try {
            $soapVar = new \SoapVar([
                'AccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $info = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $info);

            if (!empty($info->FinancialHistories)) {
                $data = [];
                if (\is_array($info->FinancialHistories->FinancialHistory)) {
                    $financialHistories = $info->FinancialHistories->FinancialHistory;
                } else {
                    $financialHistories = $info->FinancialHistories;
                }

                foreach ($financialHistories as $financialHistory) {
                    $amount = new MonetaryAmount($financialHistory->Amount, 'SGD');
                    $dateCreated = $this->getConvertedDate($financialHistory->DocumentDate ?? null);
                    $totalBilledConsumption = new QuantitativeValue($financialHistory->TotalBilledConsumption, null, null, 'KWH');

                    $data[] = new ContractFinancialHistory($amount, $dateCreated, $financialHistory->PaymentMode, $financialHistory->PaymentStatus, $financialHistory->ReferenceNumber, $financialHistory->Status, $totalBilledConsumption, $financialHistory->DocumentType);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $data;
    }

    public function getContractGiroHistory(Contract $contract)
    {
        if (!empty($this->soapAccountInfoAlternativeUrl)) {
            $this->soapUrl = $this->soapAccountInfoAlternativeUrl;
        }
        $action = 'GetCustomerAccountInfoGIROHistories';
        $data = null;

        $contractNumber = $contract->getContractNumber();

        try {
            $soapVar = new \SoapVar([
                'AccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $info = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $info);

            if (!empty($info->GIROHistories)) {
                if (\is_array($info->GIROHistories->GIROHistory)) {
                    $giroHistories = $info->GIROHistories->GIROHistory;
                } else {
                    $giroHistories = $info->GIROHistories;
                }

                foreach ($giroHistories as $giroHistory) {
                    $endDate = $this->getConvertedDate($giroHistory->GIROTerminateDate ?? null);
                    $startDate = $this->getConvertedDate($giroHistory->GIROEffectiveDate ?? null);

                    $data[] = $this->commandBus->handle(new ConvertContractGiroHistory(new ContractGiroHistory($giroHistory->BankAccount, $giroHistory->Bank, $endDate, $startDate, $giroHistory->Status)));
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $data;
    }

    public function getContractConsumptionsByBillingPeriod(Contract $contract)
    {
        if (!empty($this->soapAccountInfoAlternativeUrl)) {
            $this->soapUrl = $this->soapAccountInfoAlternativeUrl;
        }
        $action = 'GetCustomerAccountInfoConsumptionsByBillingPeriod';
        $data = null;

        $contractNumber = $contract->getContractNumber();

        try {
            $soapVar = new \SoapVar([
                'AccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $info = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $info);

            if (!empty($info->ConsumptionsByBillingPeriod)) {
                $data = [];
                if (\is_array($info->ConsumptionsByBillingPeriod->ConsumptionByBillingPeriod)) {
                    $consumptionSummaries = $info->ConsumptionsByBillingPeriod->ConsumptionByBillingPeriod;
                } else {
                    $consumptionSummaries = $info->ConsumptionsByBillingPeriod;
                }

                foreach ($consumptionSummaries as $consumptionSummary) {
                    $consumptionValue = new QuantitativeValue($consumptionSummary->Consumption, null, null, 'KWH');
                    $endDate = $this->getConvertedDate($consumptionSummary->PeriodTo ?? null);
                    $startDate = $this->getConvertedDate($consumptionSummary->PeriodFrom ?? null);

                    $data[] = new ContractConsumptionHistory($consumptionValue, $endDate, $startDate, null);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $data;
    }

    public function getContractEmailMessageHistories(Contract $contract)
    {
        if (!empty($this->soapAccountInfoAlternativeUrl)) {
            $this->soapUrl = $this->soapAccountInfoAlternativeUrl;
        }
        $action = 'GetCustomerAccountInfoEmailMessageHistories';
        $data = null;

        $contractNumber = $contract->getContractNumber();

        try {
            $soapVar = new \SoapVar([
                'AccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $info = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $info);

            if (!empty($info->EmailMessageHistories)) {
                $data = $this->getEmailHistories($info->EmailMessageHistories);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $data;
    }

    public function getContractRenewalApplicationXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::CONTRACT_RENEWAL_APPLICATION);
    }

    public function getContractBillingSummary(Contract $contract, ?ApplicationRequest $applicationRequest = null)
    {
        if (!empty($this->soapAccountInfoAlternativeUrl)) {
            $this->soapUrl = $this->soapAccountInfoAlternativeUrl;
        }
        $action = 'GetCustomerAccountInfo';
        $data = null;

        $contractNumber = $contract->getContractNumber();

        try {
            $soapVar = new \SoapVar([
                'AccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);

            $action = 'GetCustomerAccountPaymentMode';
            $soapVar = new \SoapVar([
                'customerAccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $paymentModeResult = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $paymentModeResult);

            if (!empty($result->GetCustomerAccountInfoResult)) {
                $info = $result->GetCustomerAccountInfoResult;

                $depositAmount = new MonetaryAmount($info->DepositAmount, 'SGD');
                $outstandingBalance = new MonetaryAmount($info->OutstandingBalance, 'SGD');

                try {
                    $invoiceDate = $this->getConvertedDate($info->LatestInvoicePrintOutDate ?? null);
                } catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                    $invoiceDate = null;
                }

                $paymentMode = null;
                if (!empty($paymentModeResult->GetCustomerAccountPaymentModeResult)) {
                    $paymentMode = $paymentModeResult->GetCustomerAccountPaymentModeResult;
                }
                $billingSummary = new ContractBillingSummary($info->CurrentArrearsStatus ?? null, $info->CurrentGIROAccountStatus, $depositAmount, $invoiceDate, $outstandingBalance, $paymentMode->PaymentMode);

                if (!empty($info->ArrearHistories)) {
                    if (\is_array($info->ArrearHistories->ArrearHistory)) {
                        $arrearHistories = $info->ArrearHistories->ArrearHistory;
                    } else {
                        $arrearHistories = $info->ArrearHistories;
                    }

                    foreach ($arrearHistories as $arrearHistory) {
                        $date = $this->getConvertedDate($arrearHistory->Date ?? null);

                        $billingSummary->addArrearHistory(
                            new ContractArrearsHistory($date, $arrearHistory->ArrearStatus)
                        );
                    }
                }

                if (!empty($info->ConsumptionsByBillingPeriod)) {
                    if (\is_array($info->ConsumptionsByBillingPeriod->ConsumptionByBillingPeriod)) {
                        $consumptionSummaries = $info->ConsumptionsByBillingPeriod->ConsumptionByBillingPeriod;
                    } else {
                        $consumptionSummaries = $info->ConsumptionsByBillingPeriod;
                    }

                    foreach ($consumptionSummaries as $consumptionSummary) {
                        $consumptionValue = new QuantitativeValue($consumptionSummary->Consumption, null, null, 'KWH');
                        $endDate = $this->getConvertedDate($consumptionSummary->PeriodTo ?? null);
                        $startDate = $this->getConvertedDate($consumptionSummary->PeriodFrom ?? null);

                        $billingSummary->addConsumptionHistory(
                            new ContractConsumptionHistory($consumptionValue, $endDate, $startDate, null)
                        );
                    }
                }

                if (!empty($info->EmailMessageHistories)) {
                    $emailHistories = $this->getEmailHistories($info->EmailMessageHistories);

                    foreach ($emailHistories as $emailHistory) {
                        $billingSummary->addEmailHistory($emailHistory);
                    }
                }

                if (!empty($info->FinancialHistories)) {
                    if (\is_array($info->FinancialHistories->FinancialHistory)) {
                        $financialHistories = $info->FinancialHistories->FinancialHistory;
                    } else {
                        $financialHistories = $info->FinancialHistories;
                    }

                    foreach ($financialHistories as $financialHistory) {
                        $amount = new MonetaryAmount($financialHistory->Amount, 'SGD');
                        $dateCreated = $this->getConvertedDate($financialHistory->DocumentDate ?? null);
                        $totalBilledConsumption = new QuantitativeValue($financialHistory->TotalBilledConsumption, null, null, 'KWH');

                        $billingSummary->addFinancialHistory(
                            new ContractFinancialHistory($amount, $dateCreated, $financialHistory->PaymentMode, $financialHistory->PaymentStatus, $financialHistory->ReferenceNumber, $financialHistory->Status, $totalBilledConsumption, $financialHistory->DocumentType)
                        );
                    }
                }

                if (!empty($info->GIROHistories)) {
                    if (\is_array($info->GIROHistories->GIROHistory)) {
                        $giroHistories = $info->GIROHistories->GIROHistory;
                    } else {
                        $giroHistories = $info->GIROHistories;
                    }

                    foreach ($giroHistories as $giroHistory) {
                        $endDate = $this->getConvertedDate($giroHistory->GIROTerminateDate ?? null);
                        $startDate = $this->getConvertedDate($giroHistory->GIROEffectiveDate ?? null);

                        $billingSummary->addGiroHistory(
                            $this->commandBus->handle(new ConvertContractGiroHistory(new ContractGiroHistory($giroHistory->BankAccount, $giroHistory->Bank, $endDate, $startDate, $giroHistory->Status)))
                        );
                    }
                }

                $data = $this->commandBus->handle(new ConvertContractBillingSummary($billingSummary));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }

        return $data;
    }

    public function getContractEmailHistory(Contract $contract, ?string $id = null)
    {
        if (!empty($this->soapAccountInfoAlternativeUrl)) {
            $this->soapUrl = $this->soapAccountInfoAlternativeUrl;
        }
        $action = 'GetCustomerAccountInfo';

        // return array by default
        $data = [];

        // if $id is specified only return 1, so null by default
        if (null !== $id) {
            $data = null;
        }

        $contractNumber = $contract->getContractNumber();

        try {
            $soapVar = new \SoapVar([
                'AccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);

            if (!empty($result->GetCustomerAccountInfoResult)) {
                $info = $result->GetCustomerAccountInfoResult;

                if (!empty($info->EmailMessageHistories)) {
                    $data = $this->getEmailHistories($info->EmailMessageHistories, $id);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }

        return $data;
    }

    public function getContractRCCSHistory(Contract $contract)
    {
        if (!empty($this->soapAccountInfoAlternativeUrl)) {
            $this->soapUrl = $this->soapAccountInfoAlternativeUrl;
        }
        $action = 'GetCustomerAccountInfoRCCSHistories';
        $data = [];
        $contractNumber = $contract->getContractNumber();

        try {
            $soapVar = new \SoapVar([
                'AccountNumber' => $contractNumber,
            ], SOAP_ENC_OBJECT, $this->soapNamespace);
            $info = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $info);

            if (!empty($info->RCCSHistories)) {
                $data = $this->getRCCSHistories($info->RCCSHistories);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }

        return $data;
    }

    public function getContractWelcomePackage(Contract $contract)
    {
        $action = 'GetContractWelcomePackage';
        $data = [];

        if (!empty($this->soapAttachmentAlternativeUrl)) {
            $this->soapUrl = $this->soapAttachmentAlternativeUrl;
        }

        try {
            $soapVar = new \SoapVar([
                'contractNumber' => $contract->getContractNumber(),
            ], SOAP_ENC_OBJECT, $this->soapNamespace);

            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);
            $attachments = [];

            if (!empty($result->GetContractWelcomePackageResult) && !empty($result->GetContractWelcomePackageResult->AttachmentList->Attachment)) {
                if (\is_array($result->GetContractWelcomePackageResult->AttachmentList->Attachment)) {
                    $attachments = $result->GetContractWelcomePackageResult->AttachmentList->Attachment;
                } else {
                    $attachments[] = $result->GetContractWelcomePackageResult->AttachmentList->Attachment;
                }

                foreach ($attachments as $key => $attachment) {
                    $data[] = new ContractWelcomePackage((int) (($key) + 1), $attachment->FileName,
                        $attachment->ContentType, (int) (\strlen(\rtrim($attachment->FileBytes, '=')) * 3 / 4), $this->getConvertedDate($attachment->CreatedDateTime));
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }

        return $data;
    }

    public function getContractWelcomePackageAttachment(Contract $contract, int $fileKey)
    {
        $action = 'GetContractWelcomePackage';
        $data = null;

        if (!empty($this->soapAttachmentAlternativeUrl)) {
            $this->soapUrl = $this->soapAttachmentAlternativeUrl;
        }

        try {
            $soapVar = new \SoapVar([
                'contractNumber' => $contract->getContractNumber(),
            ], SOAP_ENC_OBJECT, $this->soapNamespace);

            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);
            $attachments = [];

            if (!empty($result->GetContractWelcomePackageResult) && !empty($result->GetContractWelcomePackageResult->AttachmentList->Attachment)) {
                if (\is_array($result->GetContractWelcomePackageResult->AttachmentList->Attachment)) {
                    $attachments = $result->GetContractWelcomePackageResult->AttachmentList->Attachment;
                } else {
                    $attachments[] = $result->GetContractWelcomePackageResult->AttachmentList->Attachment;
                }

                if (!empty($attachments[$fileKey - 1])) {
                    $attachment = $attachments[$fileKey - 1];
                    $now = new \DateTime();
                    $s3FileNameKey = $now->format('Ymd').'_WelcomePackage-'.$contract->getContractNumber().'-'.$fileKey;

                    if (false === $this->s3Client->doesObjectExist($this->bucketName, $s3FileNameKey)) {
                        $this->s3Client->putObject([
                            'Bucket' => $this->bucketName,
                            'Body' => \base64_decode($attachment->FileBytes, true),
                            'Key' => $s3FileNameKey,
                            'ContentType' => $attachment->ContentType,
                        ]);
                    }

                    $command = $this->s3Client->getCommand('GetObject', [
                        'Bucket' => $this->bucketName,
                        'Key' => $s3FileNameKey,
                    ]);
                    $request = $this->s3Client->createPresignedRequest($command, '+10 minutes');
                    // Get the actual presigned-url
                    $presignedUrl = (string) $request->getUri();

                    $data = new ContractWelcomePackage($fileKey, $attachment->FileName, $attachment->ContentType,
                        (int) (\strlen(\rtrim($attachment->FileBytes, '=')) * 3 / 4), $this->getConvertedDate($attachment->CreatedDateTime), $presignedUrl);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }

        return $data;
    }

    public function getCustomerBlackListXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::CUSTOMER_BLACKLIST);
    }

    public function getEventActivityXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::EVENT_ACTIVITY);
    }

    public function getFtpSchedule(string $type)
    {
        switch ($type) {
            case JobType::CRON_DOWNLOAD_CONTRACT_APPLICATION_RETURN:
                return [[3, 05, 0]];
            case JobType::CRON_ORDER_BILL_REBATE_SUBMIT:
                return [[21, 0, 0]];
            case JobType::CRON_PROCESS_ACCOUNT_CLOSURE_APPLICATION:
                return [[8, 05, 0]];
            case JobType::CRON_PROCESS_CONTRACT_APPLICATION:
                return [[8, 05, 0]];
            case JobType::CRON_PROCESS_CONTRACT_RENEWAL_APPLICATION:
                return [[8, 05, 0]];
            case JobType::CRON_PROCESS_CREDITS_WITHDRAWAL_RETURN:
                return [[2, 45, 0], [7, 50, 0], [20, 05, 0]];
            case JobType::CRON_PROCESS_EVENT_ACTIVITY:
                return [[3, 05, 0]];
            case JobType::CRON_PROCESS_MASS_ACCOUNT_CLOSURE:
                return [[20, 15, 0]];
            case JobType::CRON_PROCESS_MASS_CONTRACT_APPLICATION:
                return [[20, 15, 0]];
            case JobType::CRON_PROCESS_MASS_CONTRACT_RENEWAL_APPLICATION:
                return [[20, 15, 0]];
            case JobType::CRON_PROCESS_MASS_TRANSFER_OUT:
                return [[20, 15, 0]];
            case JobType::CRON_PROCESS_RCCS_TERMINATION:
                return [[23, 45, 0]];
            case JobType::CRON_PROCESS_TARIFF_RATE_RECONCILIATION:
                return [[22, 05, 0]];
            case JobType::CRON_PROCESS_TRANSFER_OUT_APPLICATION:
                return [[8, 05, 0]];
            case JobType::CRON_UPLOAD_CONTRACT_APPLICATION_RECONCILIATION:
                $schedules = [];
                $hour = 0;
                while ($hour <= 23) {
                    $schedules[] = [$hour, 10, 0];
                    ++$hour;
                }

                return $schedules;
            case JobType::CRON_UPLOAD_RCCS_TERMINATION_APPLICATION_RECONCILIATION:
                return [[21, 15, 0]];
            default:
                return null;
        }
    }

    public function getGiroTerminationXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::GIRO_TERMINATION);
    }

    public function getPromotionCodeXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::PROMOTION_CODE);
    }

    public function getMassContractApplicationRequestRenewalXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::MASS_CONTRACT_APPLICATION_RENEWAL);
    }

    public function getMassContractApplicationRequestXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::MASS_CONTRACT_APPLICATION);
    }

    public function getMassContractClosureApplicationRequestXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::MASS_ACCOUNT_CLOSURE);
    }

    public function getMassContractTransferOutApplicationRequestXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::MASS_ACCOUNT_TRANSFER_OUT);
    }

    public function getProviderName()
    {
        return 'Anacle';
    }

    public function getRCCSTerminationXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::RCCS_TERMINATION);
    }

    public function getThirdPartyChargeConfiguration()
    {
        $action = 'GetThirdParyChargesTemplates';
        $data = [];

        try {
            $soapVar = new \SoapVar([], SOAP_ENC_OBJECT, $this->soapNamespace);
            $data = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $data);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $data;
        }

        return $data;
    }

    public function getTransferOutApplicationXMLFile(\DateTime $date)
    {
        return $this->downloadXML($date, DownloadFileType::TRANSFER_OUT);
    }

    public function submitApplicationRequest(ApplicationRequest $applicationRequest, ?string $date = null, bool $fail = false)
    {
        $action = null;
        $applicationRequestData = null;
        $applicationRequestObject = null;
        $failed = false;
        $result = new \stdClass();
        $resultNode = null;

        if (ApplicationRequestType::ACCOUNT_CLOSURE === $applicationRequest->getType()->getValue()) {
            $action = 'CreateFRCContractClosure';

            $applicationRequestData = $this->commandBus->handle(new BuildAccountClosureApplicationRequestData($applicationRequest));
            $applicationRequestObject = \json_decode(\json_encode(['ContractClosure' => $applicationRequestData]));
            $resultNode = 'CreateFRCContractClosureResult';
        } elseif (ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequest->getType()->getValue()) {
            $action = 'CreateFRCContractApplication';
            if (!empty($this->soapApplicationRequestAlternativeUrl)) {
                $this->soapUrl = $this->soapApplicationRequestAlternativeUrl;
            }

            $applicationRequestData = $this->commandBus->handle(new BuildContractApplicationRequestData($applicationRequest));
            $applicationRequestObject = \json_decode(\json_encode(['obj' => $applicationRequestData]));
            $resultNode = 'CreateFRCContractApplicationResult';
        } elseif (ApplicationRequestType::GIRO_TERMINATION === $applicationRequest->getType()->getValue()) {
            $action = 'CreateGiroTermination';

            $applicationRequestData = $this->commandBus->handle(new BuildGiroTerminationApplicationRequestData($applicationRequest));
            $applicationRequestObject = \json_decode(\json_encode(['CreateGiroTermination' => $applicationRequestData]));
            $resultNode = 'CreateGiroTerminationResult';
        } elseif (ApplicationRequestType::TRANSFER_OUT === $applicationRequest->getType()->getValue()) {
            $action = 'CreateFRCTransferOut';

            $applicationRequestData = $this->commandBus->handle(new BuildTransferOutApplicationRequestData($applicationRequest));
            $applicationRequestObject = \json_decode(\json_encode(['ContractTransferOut' => $applicationRequestData]));
            $resultNode = 'CreateFRCTransferOutResult';
        } elseif (ApplicationRequestType::RCCS_TERMINATION === $applicationRequest->getType()->getValue()) {
            $action = 'CreateRCCSTermination';

            $applicationRequestData = $this->commandBus->handle(new BuildRCCSTerminationApplicationRequestData($applicationRequest));
            $applicationRequestObject = \json_decode(\json_encode($applicationRequestData));
            $resultNode = 'CreateRCCSTerminationResult';
        } elseif (ApplicationRequestType::CONTRACT_RENEWAL === $applicationRequest->getType()->getValue()) {
            $action = 'CreateReContract';

            $applicationRequestData = $this->commandBus->handle(new BuildContractRenewalApplicationRequestData($applicationRequest));
            $applicationRequestObject = \json_decode(\json_encode(['obj' => $applicationRequestData]));
            $resultNode = 'CreateReContractResult';
        }

        if (null !== $action && false === $fail && null !== $resultNode) {
            $this->logger->info(\sprintf('Submitting %s', $action));

            try {
                $soapVar = new \SoapVar($applicationRequestObject, SOAP_ENC_OBJECT, $this->soapNamespace);
                $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
                $this->logSoapCallResult($action, $result);

                // @todo
                if (1 !== $result->$resultNode->ProcessStatus) {
                    $failed = true;
                }
            } catch (\Exception $e) {
                $failed = true;
                $this->logger->error($e->getMessage());
                $this->addToReconciliationXML($applicationRequestData, $action, $date);

                return $e->getMessage();
            }
        }

        if (true === $failed || true === $fail) {
            $this->addToReconciliationXML($applicationRequestData, $action, $date);
        }

        return \json_encode($result);
    }

    public function submitRedeemCreditsActions(array $redeemedCreditsActions, bool $upload = true)
    {
        $billRebateRedeemedCreditsActionData = $this->commandBus->handle(new BuildRedeemCreditsActionData($redeemedCreditsActions));

        $this->generateXMLFile($billRebateRedeemedCreditsActionData, 'CreateBillRedemption');

        if (true === $upload) {
            $now = new \DateTime();
            $now->setTimezone($this->timezone);
            $this->uploadXML($now, UploadFileType::BILL_REDEMPTION);
        }
    }

    public function submitWithdrawCreditsAction(WithdrawCreditsAction $withdrawCreditsAction, bool $fail = false)
    {
        $withdrawCreditsActionData = null;
        $result = new \stdClass();

        $withdrawCreditsActionData = $this->commandBus->handle(new BuildWithdrawCreditsTransactionData($withdrawCreditsAction));
        $withdrawCreditsActionObject = \json_decode(\json_encode(['obj' => $withdrawCreditsActionData]));

        if (\array_key_exists('ToRefundImmediately', $withdrawCreditsActionData)) {
            $action = 'CreateExistingCustomerRefund';
            $resultNode = 'CreateExistingCustomerRefundResult';
        } else {
            $action = 'CreateNonExistingCustomerRefund';
            $resultNode = 'CreateNonExistingCustomerRefundResult';
        }

        $this->logger->info(\sprintf('Submitting %s', $action));

        if (false === $fail) {
            try {
                $soapVar = new \SoapVar($withdrawCreditsActionObject, SOAP_ENC_OBJECT, $this->soapNamespace);
                $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
                $this->logSoapCallResult($action, $result);

                if (1 !== $result->$resultNode->ProcessStatus) {
                    $this->addToReconciliationXML($withdrawCreditsActionData, $action);
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $this->addToReconciliationXML($withdrawCreditsActionData, $action);

                return $e->getMessage();
            }
        } else {
            $this->addToReconciliationXML($withdrawCreditsActionData, $action);
        }

        return \json_encode($result);
    }

    public function createTask(Ticket $ticket, $fail = false)
    {
        $action = 'CreateTask';
        $failed = false;
        $result = new \stdClass();

        $createTaskData = $this->commandBus->handle(new BuildCreateTaskData($ticket));
        $createTaskObject = \json_decode(\json_encode(['obj' => $createTaskData]));

        if (false === $fail) {
            $this->logger->info(\sprintf('Submitting %s', $action));

            try {
                $soapVar = new \SoapVar($createTaskObject, SOAP_ENC_OBJECT, $this->soapNamespace);
                $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
                $this->logSoapCallResult($action, $result);

                $failed = true;

                // @todo
                if (1 !== $result->CreateTaskResult->ProcessStatus) {
                    $failed = true;
                }
            } catch (\Exception $e) {
                $failed = true;
                $this->logger->error($e->getMessage());
                $this->addToReconciliationXML($createTaskData, $action);

                return $e->getMessage();
            }
        }

        if (true === $failed || true === $fail) {
            $this->addToReconciliationXML($createTaskData, $action);
        }

        return \json_encode($result);
    }

    public function updateContractMailingAddress(ContractPostalAddress $contractPostalAddress)
    {
        $action = 'UpdateCustomerAccountMaillingAdd';
        $this->logger->info(\sprintf('Submitting %s', $action));

        $addressData = $this->commandBus->handle(new BuildMailingAddressData($contractPostalAddress));
        $addressObject = \json_decode(\json_encode(['obj' => $addressData]));

        try {
            $soapVar = new \SoapVar($addressObject, SOAP_ENC_OBJECT, $this->soapNamespace);
            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $e->getMessage();
        }

        return \json_encode($result);
    }

    public function updateCustomerContact(CustomerAccount $customerAccount, ?string $previousName = null)
    {
        $action = 'UpdateCustomerContact';
        $this->logger->info(\sprintf('Submitting %s', $action));

        $customerContactData = $this->commandBus->handle(new BuildContactUpdateData($customerAccount, $previousName));
        $customerContactObject = \json_decode(\json_encode(['obj' => $customerContactData]));

        try {
            $soapVar = new \SoapVar($customerContactObject, SOAP_ENC_OBJECT, $this->soapNamespace);
            $result = $this->getSoapClient()->__soapCall($action, [$soapVar]);
            $this->logSoapCallResult($action, $result);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $e->getMessage();
        }

        return \json_encode($result);
    }

    public function uploadFailedApplicationRequestStatusUpdate(array $failedApplicationRequests, string $action)
    {
        $url = $this->createXML($failedApplicationRequests, $action);

        return $url;
    }

    public function uploadCustomerBlacklistUpdateReturnFile(array $customerBlacklistData)
    {
        $url = $this->createXML($customerBlacklistData, 'CreateFailedCustomerBlacklistUpdate');

        return $url;
    }

    public function uploadReturnFile(array $data, \DateTime $date, string $type, bool $upload = true)
    {
        switch ($type) {
            case UploadFileType::ACCOUNT_CLOSURE_RETURN:
                $this->addToXML($data, 'CreateFRCContractClosure');

                if ($upload) {
                    $this->uploadXML($date, $type);
                }
                break;
            case UploadFileType::CONTRACT_APPLICATION_RETURN:
                $this->addToXML($data, 'CreateFRCContractApplication');

                if ($upload) {
                    $this->uploadXML($date, $type);
                }
                break;
            case UploadFileType::CONTRACT_RENEWAL_APPLICATION_RETURN:
                $this->addToXML($data, 'CreateFRCReContract');

                if ($upload) {
                    $this->uploadXML($date, $type);
                }
                break;
            case UploadFileType::TRANSFER_OUT_RETURN:
                $this->addToXML($data, 'CreateFRCTransferOut');

                if ($upload) {
                    $this->uploadXML($date, $type);
                }
                break;
            default:
                $this->logger->error('Weird Type');

                return;
        }
    }

    public function uploadXML(\DateTime $date, string $type)
    {
        if (false === $this->ftpUploadEnabled) {
            return 'FTP Upload has been disabled.';
        }

        $dateSuffixFormat = 'Ymd';

        switch ($type) {
            case UploadFileType::ACCOUNT_CLOSURE:
                $filenamePrefix = 'CRM_FRCClosure_';
                break;
            case UploadFileType::ACCOUNT_CLOSURE_RETURN:
                $filenamePrefix = 'CRM_FRCClosure_RETURN_CRM_';
                break;
            case UploadFileType::BILL_REDEMPTION:
                $filenamePrefix = 'CRM_BillRebateRedemption_';
                break;
            case UploadFileType::CONTRACT_APPLICATION:
                $filenamePrefix = 'CRM_FRCAPP_';
                $dateSuffixFormat = 'YmdH';
                break;
            case UploadFileType::CONTRACT_APPLICATION_RETURN:
                $filenamePrefix = 'CRM_FRCAPP_CRM_RETURN_';
                break;
            case UploadFileType::CONTRACT_RENEWAL_APPLICATION_RETURN:
                $filenamePrefix = 'CRM_FRC_RENEW_CRM_RETURN_';
                break;
            case UploadFileType::GIRO_TERMINATION:
                $filenamePrefix = 'CRM_GIRO_Termination_Request_';
                break;
            case UploadFileType::RCCS_TERMINATION:
                $filenamePrefix = 'CRM_RCCS_Termination_Request_';
                break;
            case UploadFileType::TRANSFER_OUT:
                $filenamePrefix = 'CRM_FRCTO_';
                break;
            case UploadFileType::TRANSFER_OUT_RETURN:
                $filenamePrefix = 'CRM_FRCTO_RETURN_CRM_';
                break;
            case UploadFileType::EXISTING_CUSTOMER_REFUND:
                $filenamePrefix = 'CRM_FRCExistingCustomerRefund_';
                break;
            case UploadFileType::NON_EXISTING_CUSTOMER_REFUND:
                $filenamePrefix = 'CRM_FRCNonExistingCustomerRefund_';
                break;
            default:
                return 'Unknown/unsupported type: '.$type;
        }

        $dateSuffix = $date->format($dateSuffixFormat);
        $filename = \sprintf('%s%s.xml', $filenamePrefix, $dateSuffix);

        if (true === $this->s3Client->doesObjectExist($this->bucketName, $filename)) {
            $path = 'Incoming';

            $result = $this->s3Client->getObject([
                'Bucket' => $this->bucketName,
                'Key' => $filename,
            ]);

            if (!empty($result['Body'])) {
                $tempFile = \tmpfile();
                \fwrite($tempFile, $result['Body']->getContents());
                $tempPath = \stream_get_meta_data($tempFile)['uri'];
                $ftp = \ftp_connect($this->ftpUrl);

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

    private function generateXMLFile(array $data, ?string $action, ?string $dateSuffix = null)
    {
        $this->addToXML($data, $action, $dateSuffix);
    }

    private function createXML(array $data, ?string $action, ?string $dateSuffix = null)
    {
        switch ($action) {
            case 'CreateFailedAccountClosureApplicationUpdateStatus':
                $filenamePrefix = 'CRM_FailedAccountClosureApplicationUpdateStatus_';
                $objectNode = 'FailedAccountClosureApplicationUpdateStatus';
                $rootNode = \sprintf('%ses', $objectNode);
                break;
            case 'CreateFailedContractApplicationUpdateStatus':
                $filenamePrefix = 'CRM_FailedContractApplicationUpdateStatus_';
                $objectNode = 'FailedContractApplicationUpdateStatus';
                $rootNode = \sprintf('%ses', $objectNode);
                break;
            case 'CreateFailedContractRenewalApplicationUpdateStatus':
                $filenamePrefix = 'CRM_FailedContractRenewalApplicationUpdateStatus_';
                $objectNode = 'FailedContractRenewalApplicationUpdateStatus';
                $rootNode = \sprintf('%ses', $objectNode);
                break;
            case 'CreateFailedCustomerBlacklistUpdate':
                $filenamePrefix = 'CRM_FailedCustomerAccountBlacklistUpdate_';
                $objectNode = 'FailedCustomerAccountBlacklistUpdate';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateFailedTransferOutApplicationUpdateStatus':
                $filenamePrefix = 'CRM_FailedTransferOutApplicationUpdateStatus_';
                $objectNode = 'FailedTransferOutApplicationUpdateStatus';
                $rootNode = \sprintf('%ses', $objectNode);
                break;
            default:
                $this->logger->error(\sprintf('Weird action: %s', $action));

                return null;
        }

        if (null !== $this->bucketName) {
            if (null === $dateSuffix) {
                $now = new \DateTime();
                $now->setTimezone($this->timezone);
                $dateSuffix = $now->format('YmdHis');
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

            foreach ($data as $datum) {
                $xmlNode = new \SimpleXMLElement(\sprintf('<%s></%s>', $objectNode, $objectNode));
                $this->addArrayToXML($datum, $xmlNode);

                $baseDom = \dom_import_simplexml($baseXML);
                $xmlDom = \dom_import_simplexml($xmlNode);

                $baseDom->appendChild($baseDom->ownerDocument->importNode($xmlDom, true));
            }

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

            return $result['ObjectURL'];
        }
    }

    private function addToReconciliationXML(array $data, ?string $action, ?string $dateSuffix = null)
    {
        $nextHourSuffix = false;

        switch ($action) {
            case 'CreateTask':
                $filenamePrefix = 'CRM_TASK_Creation_';
                $objectKey = 'CRMTaskNumber';
                $objectNode = 'TaskCreation';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateFRCContractApplication':
                $filenamePrefix = 'CRM_FRCAPP_';
                $objectKey = 'CRMContractApplicationNumber';
                $objectNode = 'ContractApplication';
                $rootNode = \sprintf('%ss', $objectNode);
                $nextHourSuffix = true;
                break;
            case 'CreateFRCReContract':
                $filenamePrefix = 'CRM_FRC_RENEW_';
                $objectKey = 'CRMFRCReContractNumber';
                $objectNode = 'RenewContract';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateFRCContractClosure':
                $filenamePrefix = 'CRM_FRCClosure_';
                $objectKey = 'CRMContractClosureNumber';
                $objectNode = 'ContractClosure';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateFRCTransferOut':
                $filenamePrefix = 'CRM_FRCTO_';
                $objectKey = 'CRMContractTransferOutNumber';
                $objectNode = 'ContractTransferOut';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateGiroTermination':
                $filenamePrefix = 'CRM_GIRO_Termination_Request_';
                $objectKey = 'CRMGIROTerminationRequestNumber';
                $objectNode = 'GiroTermination';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateRCCSTermination':
                $filenamePrefix = 'CRM_RCCS_Termination_Request_';
                $objectKey = 'CRMRCCSTerminationRequestNumber';
                $objectNode = 'RCCSTermination';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateExistingCustomerRefund':
                $filenamePrefix = 'CRM_FRCExistingCustomerRefund_';
                $objectKey = 'CRMReferenceNumber';
                $objectNode = 'ExistingCustomerRefund';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateNonExistingCustomerRefund':
                $filenamePrefix = 'CRM_FRCNonExistingCustomerRefund_';
                $objectKey = 'CRMReferenceNumber';
                $objectNode = 'NonExistingCustomerRefund';
                $rootNode = \sprintf('%ss', $objectNode);
                break;
            case 'CreateFailedApplicationRequests':
                $filenamePrefix = 'CRM_FailedApplicationRequest_';
                $objectKey = '';
                $objectNode = 'FailedApplicationRequest';
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

                if (true === $nextHourSuffix) {
                    $now->modify('+1 hour');
                    $dateSuffix = $now->format('YmdH');
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
                if ($data[$objectKey] === $existingFailed->$objectKey->__toString()) {
                    $exists = true;
                }
            }

            if (false === $exists) {
                $xmlNode = new \SimpleXMLElement(\sprintf('<%s></%s>', $objectNode, $objectNode));
                $this->addArrayToXML($data, $xmlNode);

                $baseDom = \dom_import_simplexml($baseXML);
                $xmlDom = \dom_import_simplexml($xmlNode);

                $baseDom->appendChild($baseDom->ownerDocument->importNode($xmlDom, true));
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

                return $result['ObjectURL'];
            }
            $this->logger->info('Object exists in XML file.');

            return null;
        }
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
            case 'CreateFRCContractApplication':
                $filenamePrefix = 'CRM_FRCAPP_CRM_RETURN_';
                $objectKey = 'FRCContractApplicationNumber';
                $objectNode = 'ContractApplicationReturn';
                $rootNode = 'ContractApplicationsReturn';
                break;
            case 'CreateFRCContractClosure':
                $filenamePrefix = 'CRM_FRCClosure_RETURN_CRM_';
                $objectKey = 'FRCContractClosureNumber';
                $objectNode = 'ContractClosureReturn';
                $rootNode = 'ContractClosuresReturn';
                break;
            case 'CreateFRCReContract':
                $filenamePrefix = 'CRM_FRC_RENEW_CRM_RETURN_';
                $objectKey = 'FRCReContractNumber';
                $objectNode = 'RenewContractReturn';
                $rootNode = 'RenewContractsReturn';
                break;
            case 'CreateFRCTransferOut':
                $filenamePrefix = 'CRM_FRCTO_RETURN_CRM_';
                $objectKey = 'CRMContractTransferOutNumber';
                $objectNode = 'ContractTransferOutReturn';
                $rootNode = 'ContractTransferOutsReturn';
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

    private function getCleanXMLString(\SimpleXMLElement $xml)
    {
        $xml = \str_replace('<multiItemSeparator>', '', $xml->asXML());
        $xml = \str_replace('</multiItemSeparator>', '', $xml);

        return \str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="utf-8"?>', $xml);
    }

    private function getConvertedDate(?string $dateString)
    {
        if (null === $dateString) {
            return null;
        }

        try {
            $date = new \DateTime($dateString);
        } catch (\Exception $e) {
            $date = \DateTime::createFromFormat('d/n/Y h:i:s A', $dateString);
        }

        $utcTimezone = new \DateTimeZone('UTC');
        $convertedDate = new \DateTime($date->format('Y-m-d H:i:s'), $this->timezone);
        $convertedDate->setTimezone($utcTimezone);

        return $convertedDate;
    }

    private function getEmailHistories($data, ?string $id = null)
    {
        $result = [];

        if (null !== $id) {
            $result = null;
        }

        if (\is_array($data->EmailMessageHistory)) {
            $emailHistories = $data->EmailMessageHistory;
        } else {
            $emailHistories = $data;
        }

        foreach ($emailHistories as $emailHistory) {
            if ('EMAIL' === $emailHistory->DocumentType) {
                $emailHistoryId = \md5(\json_encode($emailHistory));
                $emailHistory = new ContractEmailHistory($emailHistoryId, $emailHistory->SubjectTitle ?? null, $emailHistory->Recipients ?? null);

                if (null === $id) {
                    $result[] = $emailHistory;
                } elseif ($id === $emailHistoryId) {
                    $result = $emailHistory;
                    break;
                }
            }
        }

        return $result;
    }

    private function getRCCSHistories($data)
    {
        $result = [];

        if (\is_array($data->RCCSHistory)) {
            $rccsHistories = $data->RCCSHistory;
        } else {
            $rccsHistories = [$data->RCCSHistory];
        }

        foreach ($rccsHistories as $rccsHistory) {
            $dateEffective = null !== $rccsHistory->EffectiveDate ? new \DateTime($rccsHistory->EffectiveDate) : null;
            $dateExpired = null !== $rccsHistory->CardExpiryDate ? new \DateTime($rccsHistory->CardExpiryDate) : null;
            $dateTerminated = null !== $rccsHistory->TerminateDate ? new \DateTime($rccsHistory->TerminateDate) : null;

            $rccsHistory = new ContractRccsHistory($rccsHistory->AccountNumber ?? null, $rccsHistory->CardNo ?? null,
                $dateEffective, $dateExpired, $dateTerminated, $rccsHistory->CustomerAccountGroupNumber ?? null, $rccsHistory->Status ?? null);

            $rccsContractHistory = $this->commandBus->handle(new ConvertContractRCCSHistory($rccsHistory));
            $result[] = $rccsContractHistory;
        }

        return $result;
    }

    private function getSoapClient()
    {
        \ini_set('default_socket_timeout', '10');

        $soapClientOptions = [
            'connection_timeout' => 10,
            'keep_alive' => false,
            'soap_version' => SOAP_1_1,
            'trace' => 1,
        ];

        $soapClient = new AnacleSoapClient($this->soapUrl, $soapClientOptions, $this->logger);

        $headers = new \SoapHeader($this->soapNamespace, 'AuthHeader', [
            'Username' => $this->soapUsername,
            'Password' => $this->soapPassword,
        ]);
        $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }

    private function logSoapCallResult($action, $result)
    {
        $pattern = '/"FileBytes":.*"/';
        $replacement = '"FileBytes": "base64 encoded string"';
        $this->logger->info('Result from '.$action.' : '.\preg_replace($pattern, $replacement, \json_encode($result, JSON_PRETTY_PRINT)));
    }
}
