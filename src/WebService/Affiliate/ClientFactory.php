<?php

declare(strict_types=1);

namespace App\WebService\Affiliate;

use App\Enum\AffiliateWebServicePartner;
use App\WebService\Affiliate\Provider\HasOffers\Client as LazadaHasOffersClient;
use App\WebService\Affiliate\Provider\InvolveAsia\Client as InvolveAsiaClient;
use App\WebService\Affiliate\Provider\TheAffiliateGateway\Client as TAGClient;
use Psr\Log\LoggerInterface;

class ClientFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @param array           $config
     * @param LoggerInterface $logger
     * @param string          $timezone
     */
    public function __construct(array $config, LoggerInterface $logger, string $timezone)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->timezone = $timezone;
    }

    public function getClient(string $providerName): ClientInterface
    {
        $webServiceClient = null;

        $theAffiliateGatewayAliases = [
            'TAG',
            'The Affiliate Gateway',
            AffiliateWebServicePartner::TAG,
        ];

        $involveAsiaAliases = [
            'INVOLVEASIA',
            'Involve Asia',
            AffiliateWebServicePartner::INVOLVE_ASIA,
        ];

        $hasOffersAliases = [
            'HASOFFERS',
            'Has Offers',
            AffiliateWebServicePartner::LAZADA_HASOFFERS,
        ];

        if (\in_array($providerName, $theAffiliateGatewayAliases, true)) {
            if (!empty($this->config['tag_api_url']) && !empty($this->config['tag_api_username']) && !empty($this->config['tag_api_key'])) {
                $webServiceClient = new TAGClient($this->config['tag_api_url'], $this->config['tag_api_username'], $this->config['tag_api_key'], $this->logger);
            }
        } elseif (\in_array($providerName, $involveAsiaAliases, true)) {
            if (!empty($this->config['involve_api_key']) && !empty($this->config['involve_api_secret']) && !empty($this->config['involve_api_url'])) {
                $webServiceClient = new InvolveAsiaClient($this->config['involve_api_url'], $this->config['involve_api_key'], $this->config['involve_api_secret'], $this->logger);
            }
        } elseif (\in_array($providerName, $hasOffersAliases, true)) {
            if (!empty($this->config['has_offers_api_key']) && !empty($this->config['has_offers_api_secret']) && !empty($this->config['has_offers_api_url'])) {
                $webServiceClient = new LazadaHasOffersClient($this->config['has_offers_api_url'], $this->config['has_offers_api_key'], $this->config['has_offers_api_secret'], $this->logger);
            }
        }

        if (null === $webServiceClient) {
            $webServiceClient = new DummyClient();
        }

        return $webServiceClient;
    }
}
