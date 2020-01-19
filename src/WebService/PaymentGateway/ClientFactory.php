<?php

declare(strict_types=1);

namespace App\WebService\PaymentGateway;

use App\WebService\PaymentGateway\Provider\AXS\Client as AXSClient;
use App\WebService\PaymentGateway\Provider\Wirecard\Client as WirecardClient;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;

class ClientFactory
{
    /**
     * Creates a webservice client.
     *
     * @param array           $config
     * @param CommandBus      $commandBus
     * @param LoggerInterface $logger
     * @param string          $timezone
     *
     * @return ClientInterface
     */
    public function create(array $config, CommandBus $commandBus, LoggerInterface $logger, string $timezone): ClientInterface
    {
        $paymentGatewayClient = null;
        $errors = [];
        $requiredParams = [
            'merchant_url' => 'PAYMENT_GATEWAY_BASE_URL',
        ];

        if (empty($timezone)) {
            $timezone = 'UTC';
        }

        $dateTimezone = new \DateTimeZone($timezone);

        if (!empty($config['provider'])) {
            switch ($config['provider']) {
                case 'wirecard':
                    $logger->info('Setting up Wirecard Payment Gateway client.');

                    $requiredParams += [
                        'merchant_id' => 'PAYMENT_GATEWAY_MERCHANT_ID',
                        'merchant_secret' => 'PAYMENT_GATEWAY_MERCHANT_SECRET',
                    ];

                    foreach ($requiredParams as $key => $param) {
                        if (empty($config[$key])) {
                            $errors[] = $param;
                        }
                    }

                    if (0 === \count($errors)) {
                        $paymentGatewayClient = new WirecardClient($config, $dateTimezone, $commandBus, $logger);
                    }
                    break;
                case 'axs':
                    $logger->info('Setting up AXS client.');

                    foreach ($requiredParams as $key => $param) {
                        if (empty($config[$key])) {
                            $errors[] = $param;
                        }
                    }

                    if (0 === \count($errors)) {
                        $paymentGatewayClient = new AXSClient($config, $dateTimezone, $logger);
                    }
                    break;
                default:
                    break;
            }
        }

        if (\count($errors) > 0) {
            $logger->warning('Missing required env variables: '.\implode(', ', $errors));
        }

        if (null === $paymentGatewayClient) {
            $paymentGatewayClient = new DummyClient();
        }

        return $paymentGatewayClient;
    }
}
