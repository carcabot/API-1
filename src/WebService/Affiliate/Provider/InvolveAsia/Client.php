<?php

declare(strict_types=1);

namespace App\WebService\Affiliate\Provider\InvolveAsia;

use App\Enum\AffiliateCommissionStatus;
use App\Enum\AffiliateWebServicePartner;
use App\WebService\Affiliate\ClientInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Components\Query;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Modifiers\MergeQuery;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;

class Client implements ClientInterface
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiSecret;

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
     * @var \DateTimeZone
     */
    private $timezone;

    public function __construct(string $url, string $key, string $secret, LoggerInterface $logger)
    {
        $this->apiKey = $key;
        $this->apiSecret = $secret;
        $this->logger = $logger;

        $this->baseUri = HttpUri::createFromString($url);
        $this->client = new GuzzleClient();
        $this->timezone = new \DateTimeZone('Asia/Singapore');
    }

    public function getConversionDataByDate(\DateTime $startDate, \DateTime $endDate)
    {
        $authToken = $this->getAuthenticationToken();

        if (null === $authToken) {
            return [];
        }

        $data = [];
        $page = 1;

        $modifier = new AppendSegment('conversions/data-range');
        $uri = $modifier->process($this->baseUri);

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'Authorization' => \sprintf('Bearer %s', $authToken),
        ];

        $filterData = [
            'limit' => 30,
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
        ];

        while (null !== $page) {
            $filterData['page'] = $page;

            $this->logger->info('Sending POST to '.$uri);
            $this->logger->info(\json_encode($filterData, JSON_PRETTY_PRINT));

            $conversionDataRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($filterData));
            $conversionDataResponse = $this->client->send($conversionDataRequest);
            $conversionDataResult = \json_decode((string) $conversionDataResponse->getBody(), true);

            $this->logger->info('Result from POST to '.$uri);
            $this->logger->info(\json_encode($conversionDataResult, JSON_PRETTY_PRINT));

            if ('success' === $conversionDataResult['status'] && 'Success' === $conversionDataResult['message']) {
                $data = \array_merge($data, $conversionDataResult['data']['data']);
                $page = $conversionDataResult['data']['nextPage'];
            } else {
                break;
            }
        }

        return $data;
    }

    public function generateTrackingUrl(string $baseUrl, array $params)
    {
        $trackingUrl = '';

        if (!empty($params['customerAccountNumber'])) {
            $baseUri = HttpUri::createFromString($baseUrl);
            $query = Query::createFromPairs(['aff_sub' => $params['customerAccountNumber']]);
            $modifier = new MergeQuery($query->__toString());
            $trackingUrl = $modifier->__invoke($baseUri)->__toString();
        }

        return $trackingUrl;
    }

    public function getProviderName()
    {
        return AffiliateWebServicePartner::INVOLVE_ASIA;
    }

    public function normalizeConversionData(array $data)
    {
        $affiliateProgramTransactions = [];

        foreach ($data as $transaction) {
            $transactionDate = new \DateTime($transaction['datetime_conversion'], $this->timezone);
            $transactionDate->setTimezone(new \DateTimeZone('UTC'));

            $affiliateProgramTransactions[] = [
                'affiliateProgram' => [
                    'programNumber' => (string) $transaction['offer_id'],
                ],
                'commissionAmount' => [
                    'currency' => 'SGD',
                    'value' => $transaction['payout'],
                ],
                'commissionStatus' => $this->mapCommissionStatus($transaction['conversion_status']),
                'customer' => [
                    'accountNumber' => $transaction['aff_sub1'],
                ],
                'orderAmount' => [
                    'currency' => 'SGD',
                    'value' => $transaction['sale_amount'],
                ],
                'provider' => AffiliateWebServicePartner::INVOLVE_ASIA,
                'transactionNumber' => (string) $transaction['conversion_id'],
                'transactionDate' => $transactionDate->format('r'),
                'groupId' => $transaction['adv_sub1'],
            ];
        }

        return $affiliateProgramTransactions;
    }

    private function getAuthenticationToken()
    {
        $modifier = new AppendSegment('authenticate');
        $uri = $modifier->process($this->baseUri);

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
        ];

        $authenticationData = [
            'secret' => $this->apiSecret,
            'key' => $this->apiKey,
        ];

        $this->logger->info('Sending POST to '.$uri);
        $this->logger->info(\json_encode($authenticationData, JSON_PRETTY_PRINT));

        $authenticationRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($authenticationData));
        $authenticationResponse = $this->client->send($authenticationRequest);
        $authenticationResult = \json_decode((string) $authenticationResponse->getBody(), true);

        $this->logger->info('Result from POST to '.$uri);
        $this->logger->info(\json_encode($authenticationResult, JSON_PRETTY_PRINT));

        if ('success' === $authenticationResult['status']) {
            return $authenticationResult['data']['token'];
        }

        return null;
    }

    private function mapCommissionStatus(string $approvalStatus)
    {
        switch ($approvalStatus) {
            case 'pending':
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::PENDING);
            case 'rejected':
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::DECLINED);
            default:
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::APPROVED);
        }
    }
}
