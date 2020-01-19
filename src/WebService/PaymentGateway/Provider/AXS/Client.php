<?php

declare(strict_types=1);

namespace App\WebService\PaymentGateway\Provider\AXS;

use App\Entity\Contract;
use App\Entity\Payment;
use App\Enum\PaymentMode;
use App\WebService\PaymentGateway\ClientInterface;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;

class Client implements ClientInterface
{
    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HttpUri
     */
    private $baseUri;

    public function __construct(array $config, \DateTimeZone $timezone, LoggerInterface $logger)
    {
        $this->timezone = $timezone;
        $this->logger = $logger;
        $this->baseUri = HttpUri::createFromString($config['merchant_url']);
    }

    public function getPaymentUrl(Payment $payment, Contract $contract, PaymentMode $paymentMode = null)
    {
        return $this->baseUri->__toString();
    }
}
