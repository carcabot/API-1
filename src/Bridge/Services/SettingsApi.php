<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Entity\BridgeUser;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;

final class SettingsApi
{
    /**
     * @var string
     */
    private $bridgeApiUrl;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string|null
     */
    private $authToken;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @param string                 $bridgeApiUrl
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(string $bridgeApiUrl, EntityManagerInterface $entityManager)
    {
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->entityManager = $entityManager;
        $this->client = new GuzzleClient();
        $this->baseUri = HttpUri::createFromString($this->bridgeApiUrl);

        $qb = $this->entityManager->getRepository(BridgeUser::class)->createQueryBuilder('bu');

        $bridgeUser = $qb->select('bu')
            ->where($qb->expr()->isNotNull('bu.authToken'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null !== $bridgeUser) {
            $this->authToken = $bridgeUser->getAuthToken();
        } else {
            $this->authToken = null;
        }
    }

    /**
     * Gets appplication request configuration by specified key or return all configurations.
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function getApplicationRequestConfiguration(?string $key = null)
    {
        $applicationRequestConfiguration = null;

        if (null !== $this->authToken) {
            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
                'x-access-token' => $this->authToken,
            ];

            $modifier = new AppendSegment('application/config');
            $applicationConfigUri = $modifier->process($this->baseUri);

            $getApplicationConfigurationRequest = new GuzzlePsr7Request('GET', $applicationConfigUri, $headers);
            $getApplicationConfigurationResponse = $this->client->send($getApplicationConfigurationRequest);
            $getApplicationConfigurationResult = \json_decode((string) $getApplicationConfigurationResponse->getBody(), true);

            if (200 === $getApplicationConfigurationResult['status'] && 1 === $getApplicationConfigurationResult['flag']) {
                $applicationRequestConfiguration = $getApplicationConfigurationResult['data'];
            }

            if (null !== $key) {
                return $applicationRequestConfiguration[$key] ?? null;
            }
        }

        return $applicationRequestConfiguration;
    }

    /**
     * Gets constant by specified key or return all constants.
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function getConstant(?string $key = null)
    {
        $constants = null;

        if (null !== $this->authToken) {
            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
                'x-access-token' => $this->authToken,
            ];

            $modifier = new AppendSegment('constant');
            $constantsUri = $modifier->process($this->baseUri);

            $constantsRequest = new GuzzlePsr7Request('GET', $constantsUri, $headers);
            $constantsResponse = $this->client->send($constantsRequest);
            $constantsResult = \json_decode((string) $constantsResponse->getBody(), true);

            if (200 === $constantsResult['status'] && 1 === $constantsResult['flag']) {
                $constants = $constantsResult['data'];
            }

            if (null !== $key) {
                return $constants[$key] ?? null;
            }
        }

        return $constants;
    }

    /**
     * Gets global configuration by specified key or return all global configurations.
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function getGlobalConfiguration(?string $key = null)
    {
        $globalConfiguration = null;

        if (null !== $this->authToken) {
            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
                'x-access-token' => $this->authToken,
            ];

            $modifier = new AppendSegment('global/global-config');
            $globalConfigUri = $modifier->process($this->baseUri);

            $getGlobalConfigurationRequest = new GuzzlePsr7Request('GET', $globalConfigUri, $headers);
            $getGlobalConfigurationResponse = $this->client->send($getGlobalConfigurationRequest);
            $getGlobalConfigurationResult = \json_decode((string) $getGlobalConfigurationResponse->getBody(), true);

            if (200 === $getGlobalConfigurationResult['status'] && 1 === $getGlobalConfigurationResult['flag']) {
                $globalConfiguration = $getGlobalConfigurationResult['data'];
            }

            if (null !== $key) {
                return $globalConfiguration[$key] ?? null;
            }
        }

        return $globalConfiguration;
    }
}
