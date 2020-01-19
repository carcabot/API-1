<?php

declare(strict_types=1);

namespace App\WebService\Affiliate\Provider\HasOffers;

use App\Enum\AffiliateCommissionStatus;
use App\Enum\AffiliateWebServicePartner;
use App\WebService\Affiliate\ClientInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Components\Query;
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
        $data = [];
        $page = 1;
        $queryParameters = [
            'api_key' => $this->apiSecret,
            'Method' => 'getConversions',
            'Target' => 'Affiliate_Report',
            'fields' => [
                'Stat.currency',
                'Stat.id',
                'Stat.datetime',
                'Stat.sale_amount',
                'Stat.conversion_status',
                'Stat.approved_payout',
                'Stat.offer_id',
                'Stat.affiliate_info1',
            ],
            'filters' => [
                'Stat.datetime' => [
                    'conditional' => 'BETWEEN',
                    'values' => [
                        $startDate->format('Y-m-d H:i:s'),
                        $endDate->format('Y-m-d H:i:s'),
                    ],
                ],
            ],
        ];

        while (null !== $page) {
            $queryParameters['page'] = $page;
            $query = Query::createFromParams($queryParameters);
            $modifier = new MergeQuery($query->__toString());
            $uri = $modifier->process($this->baseUri);

            $this->logger->info('Sending GET to '.$uri);

            $conversionDataRequest = new GuzzlePsr7Request('GET', $uri);
            $conversionDataResponse = $this->client->send($conversionDataRequest);
            $conversionDataResult = \json_decode((string) $conversionDataResponse->getBody(), true);

            $this->logger->info('Result from GET to '.$uri);
            $this->logger->info(\json_encode($conversionDataResult, JSON_PRETTY_PRINT));

            $response = $conversionDataResult['response'];

            ++$page;
            if (1 === $response['status'] && 200 === $response['httpStatus']) {
                $data = \array_merge($data, $response['data']['data']);

                if ($page > $response['data']['pageCount']) {
                    $page = null;
                }
            } else {
                $page = null;
            }
        }

        return $data;
    }

    public function generateTrackingUrl(string $baseUrl, array $params)
    {
        $trackingUrl = '';

        if (!empty($params['customerAccountNumber']) && !empty($params['programNumber'])) {
            $query = Query::createFromParams([
                'api_key' => $this->apiSecret,
                'offer_id' => $params['programNumber'],
                'Target' => 'Affiliate_Offer',
                'Method' => 'generateTrackingLink',
                'params' => [
                    'aff_sub' => $params['customerAccountNumber'],
                ],
            ]);
            $modifier = new MergeQuery($query->__toString());
            $uri = $modifier->process($this->baseUri);

            $this->logger->info('Sending GET to '.$uri);

            try {
                $generateUrlRequest = new GuzzlePsr7Request('GET', $uri);
                $generateUrlResponse = $this->client->send($generateUrlRequest);
                $generateUrlResult = \json_decode((string) $generateUrlResponse->getBody(), true);

                $this->logger->info('Result from GET to '.$uri);
                $this->logger->info(\json_encode($generateUrlResult, JSON_PRETTY_PRINT));

                if ((200 === $generateUrlResponse->getStatusCode() || 200 === $generateUrlResult['response']['httpStatus']) && 1 === $generateUrlResult['response']['status']) {
                    $trackingUrl = $generateUrlResult['response']['data']['click_url'];
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $trackingUrl;
    }

    public function getProviderName()
    {
        return AffiliateWebServicePartner::LAZADA_HASOFFERS;
    }

    public function normalizeConversionData(array $data)
    {
        $affiliateProgramTransactions = [];

        foreach ($data as $transaction) {
            $stat = $transaction['Stat'];
            $transactionDate = new \DateTime($stat['datetime'], $this->timezone);
            $transactionDate->setTimezone(new \DateTimeZone('UTC'));

            $affiliateProgramTransactions[] = [
                'affiliateProgram' => [
                    'programNumber' => $stat['offer_id'],
                ],
                'commissionAmount' => [
                    'currency' => 'SGD',
                    'value' => $stat['approved_payout@SGD'],
                ],
                'commissionStatus' => $this->mapCommissionStatus($stat['conversion_status']),
                'customer' => [
                    'accountNumber' => $stat['affiliate_info1'],
                ],
                'orderAmount' => [
                    'currency' => 'SGD',
                    'value' => $stat['sale_amount@SGD'],
                ],
                'provider' => AffiliateWebServicePartner::LAZADA_HASOFFERS,
                'transactionDate' => $transactionDate->format('r'),
                'transactionNumber' => $stat['id'],
            ];
        }

        return $affiliateProgramTransactions;
    }

    private function mapCommissionStatus(string $appovalStatus)
    {
        switch ($appovalStatus) {
            case 'pending':
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::PENDING);
            case 'rejected':
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::DECLINED);
            default:
                return new AffiliateCommissionStatus(AffiliateCommissionStatus::APPROVED);
        }
    }
}
