<?php

declare(strict_types=1);

namespace App\WebService\Billing;

use App\WebService\Billing\Provider\Anacle\Client as AnacleClient;
use App\WebService\Billing\Provider\Enworkz\Client as EnworkzClient;
use Aws\S3\S3Client;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;

class WebServiceFactory
{
    /**
     * Creates a webservice client.
     *
     * @param array           $config
     * @param CommandBus      $commandBus
     * @param LoggerInterface $logger
     * @param S3Client        $s3Client
     * @param string          $timezone
     *
     * @return ClientInterface
     */
    public function create(array $config, CommandBus $commandBus, LoggerInterface $logger, S3Client $s3Client, string $timezone): ClientInterface
    {
        $webServiceClient = null;
        $errors = [];
        $requiredParams = [
            'url' => 'APP_WEB_SERVICE_URL',
            'ftp_url' => 'APP_WEB_SERVICE_FTP_URL',
            'ftp_username' => 'APP_WEB_SERVICE_FTP_USERNAME',
            'ftp_password' => 'APP_WEB_SERVICE_FTP_PASSWORD',
        ];

        if (empty($timezone)) {
            $timezone = 'UTC';
        }

        $dateTimezone = new \DateTimeZone($timezone);

        if (!empty($config['bucket_name'])) {
            if (false === $s3Client->doesBucketExist($config['bucket_name'])) {
                $s3Client->createBucket([
                    'ACL' => 'private',
                    'Bucket' => $config['bucket_name'],
                ]);
            }
        }

        if (!empty($config['provider'])) {
            switch ($config['provider']) {
                case 'anacle':
                    $logger->info('Setting up Anacle Web Service client.');

                    $requiredParams += [
                        'username' => 'APP_WEB_SERVICE_USERNAME',
                        'password' => 'APP_WEB_SERVICE_PASSWORD',
                    ];

                    foreach ($requiredParams as $key => $param) {
                        if (empty($config[$key])) {
                            $errors[] = $param;
                        }
                    }

                    if (0 === \count($errors)) {
                        $webServiceClient = new AnacleClient($config, $dateTimezone, $commandBus, $s3Client, $logger);
                    }
                    break;
                case 'enworkz':
                    $logger->info('Setting up Enworkz Web Service client.');

                    $requiredParams += [
                        'auth_token' => 'APP_WEB_SERVICE_AUTH_TOKEN',
                    ];

                    foreach ($requiredParams as $key => $param) {
                        if (empty($config[$key])) {
                            $errors[] = $param;
                        }
                    }

                    if (0 === \count($errors)) {
                        $webServiceClient = new EnworkzClient($config, $dateTimezone, $commandBus, $s3Client, $logger);
                    }
                    break;
                default:
                    break;
            }
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
