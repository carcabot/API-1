<?php

declare(strict_types=1);

namespace App\WebService\Affiliate\Provider\TheAffiliateGateway;

use App\Enum\AffiliateCommissionStatus;
use App\Enum\AffiliateWebServicePartner;
use App\WebService\Affiliate\ClientInterface;
use League\Uri\Components\Query;
use League\Uri\Modifiers\MergeQuery;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;

class Client implements ClientInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $soapNamespace;

    /**
     * @var string
     */
    private $soapUrl;

    /**
     * @var array
     */
    private $soapClientOptions;

    /**
     * @var array
     */
    private $authentication;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    public function __construct(string $url, string $username, string $key, LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->soapNamespace = 'http://theaffiliategateway.com/data/schemas';
        $this->soapUrl = $url;

        $this->soapClientOptions = [
            'soap_version' => SOAP_1_1,
            'trace' => 1,
        ];

        $this->authentication = [
            'username' => $username,
            'apikey' => $key,
        ];

        $this->timezone = new \DateTimeZone('Asia/Singapore');
    }

    public function getConversionDataByDate(\DateTime $startDate, \DateTime $endDate)
    {
        $action = 'GetSalesData';
        $this->logger->info(\sprintf('Submitting %s', $action));

        $soapObject = [
            'Authentication' => $this->authentication,
            'Criteria' => [
                'StartDateTime' => $startDate->format('Y-m-d H:i:s'),
                'EndDateTime' => $endDate->format('Y-m-d H:i:s'),
            ],
        ];

        $soapClient = new \SoapClient($this->soapUrl, $this->soapClientOptions);

        try {
            $result = $soapClient->__soapCall($action, [$soapObject]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return $e->getMessage();
        }

        $this->logger->info(\sprintf('Result from %s', $action));
        $this->logger->info(\json_encode($result, JSON_PRETTY_PRINT));

        return \json_decode(\json_encode($result), true);
    }

    public function generateTrackingUrl(string $baseUrl, array $params)
    {
        $trackingUrl = '';

        if (!empty($params['customerAccountNumber'])) {
            $baseUri = HttpUri::createFromString($baseUrl);
            $query = Query::createFromPairs(['SUBID' => $params['customerAccountNumber']]);
            $modifier = new MergeQuery($query->__toString());
            $trackingUrl = $modifier->__invoke($baseUri)->__toString();
        }

        return $trackingUrl;
    }

    public function getProviderName()
    {
        return AffiliateWebServicePartner::TAG;
    }

    public function normalizeConversionData(array $data)
    {
        $affiliateProgramTransactions = [];

        if (empty($data['Transactions']) || empty($data['Transactions']['Transaction'])) {
            return $affiliateProgramTransactions;
        }

        // this means more than 1, cater for weird behavior from soap return
        if (isset($data['Transactions']['Transaction'][0])) {
            $transactions = $data['Transactions']['Transaction'];
        } else {
            $transactions = $data['Transactions'];
        }

        // hard code default customer number for now.
        foreach ($transactions as $key => $transaction) {
            $transactionDate = \DateTime::createFromFormat('d/m/Y H:i:s', $transaction['TransactionDateTime'], $this->timezone);
            $transactionDate->setTimezone(new \DateTimeZone('UTC'));

            $affiliateProgramTransactions[] = [
                'affiliateProgram' => [
                    'programNumber' => (string) $transaction['ProgramId'],
                ],
                'commissionAmount' => [
                    'currency' => 'SGD',
                    'value' => $transaction['AffiliateCommissionAmount'],
                ],
                'commissionStatus' => $this->mapCommissionStatus((int) $transaction['ApprovalStatusId']),
                'customer' => [
                    'accountNumber' => !empty($transaction['AffiliateSubId']) ? $transaction['AffiliateSubId'] : 'U-1806000002',
                ],
                'orderAmount' => [
                    'currency' => 'SGD',
                    'value' => $transaction['OrderAmount'],
                ],
                'provider' => AffiliateWebServicePartner::TAG,
                'transactionDate' => $transactionDate->format('r'),
                'transactionNumber' => (string) $transaction['TransactionId'],
            ];
        }

        return $affiliateProgramTransactions;
    }

    private function mapCommissionStatus(int $approvalStatusId)
    {
        switch ($approvalStatusId) {
            case 1:
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::PENDING);
            case 2:
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::APPROVED);
            default:
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::DECLINED);
        }
    }
}
