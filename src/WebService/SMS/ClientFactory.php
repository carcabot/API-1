<?php

declare(strict_types=1);

namespace App\WebService\SMS;

use App\WebService\SMS\Provider\FortDigital\Client as FortDigitalClient;
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
    public function create(array $config, LoggerInterface $logger, string $timezone): ClientInterface
    {
        $errors = [];
        $webServiceClient = null;
        $providerName = $config['provider'] ?? null;

        $requiredParams = [
            'api_url' => 'APP_WEB_SERVICE_SMS_URL',
            'api_username' => 'APP_WEB_SERVICE_SMS_USERNAME',
            'api_password' => 'APP_WEB_SERVICE_SMS_PASSWORD',
            'origin_number' => 'APP_WEB_SERVICE_SMS_ORIGIN_NUMBER',
        ];

        foreach ($requiredParams as $key => $param) {
            if (empty($config[$key])) {
                $errors[] = $param;
            }
        }

        switch ($providerName) {
            case 'fortdigital':
                $webServiceClient = new FortDigitalClient($config['api_url'], $config['api_username'], $config['api_password'], $config['origin_number'], $config['test_number'] ?? null, $logger);
                break;
            default:
                break;
        }

        if (\count($errors) > 0) {
            $logger->warning('Missing required env variables: '.\implode(', ', $errors));
        }

        if (null === $webServiceClient) {
            $webServiceClient = new DummyClient();
        }

        return $webServiceClient;
    }
}
