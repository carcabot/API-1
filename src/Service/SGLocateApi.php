<?php

declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Zend\Diactoros\Response\JsonResponse;

/**
 * @ref https://www.sglocate.com/developer.aspx
 */
class SGLocateApi
{
    const SGLOCATE_POSTAL_CODE_API_ENDPOINT = 'https://www.sglocate.com/api/json/searchwithpostcode.aspx';

    /**
     * @var string
     */
    private $sgLocateApiKey;

    /**
     * @var string
     */
    private $sgLocateApiSecret;

    /**
     * @param string $sgLocateApiKey
     * @param string $sgLocateApiSecret
     */
    public function __construct(string $sgLocateApiKey, string $sgLocateApiSecret)
    {
        $this->sgLocateApiKey = $sgLocateApiKey;
        $this->sgLocateApiSecret = $sgLocateApiSecret;
    }

    /**
     * Searches postal address by postal code.
     *
     * @param string $postalCode
     *
     * @return HttpResponseInterface
     */
    public function searchByPostalCode(string $postalCode): HttpResponseInterface
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'User-Agent' => 'U-Centric API',
        ];

        $data = [
            'APIKey' => $this->sgLocateApiKey,
            'APISecret' => $this->sgLocateApiSecret,
            'Postcode' => $postalCode,
        ];

        $client = new GuzzleClient();

        try {
            $dataResponse = $client->request('POST', self::SGLOCATE_POSTAL_CODE_API_ENDPOINT, [
                'form_params' => $data,
                'headers' => $headers,
            ]);
        } catch (GuzzleRequestException $e) {
            if ($e->hasResponse()) {
                /** @var HttpResponseInterface $errorResponse */
                $errorResponse = $e->getResponse();
                $errorResult = \json_decode((string) $errorResponse->getBody(), true);

                return new JsonResponse($errorResult, 502);
            }

            return new JsonResponse(['message' => $e->getMessage()], 502);
        }

        $dataResult = \json_decode((string) $dataResponse->getBody(), true);

        if (true !== $dataResult['IsSuccess']) {
            throw new BadRequestHttpException($dataResult['ErrorDetails']);
        }

        $content = \array_map(function ($postalAddress) {
            return [
                'buildingCode' => $postalAddress['BuildingCode'],
                'buildingDescription' => $postalAddress['BuildingDescription'],
                'buildingName' => $postalAddress['BuildingName'],
                'buildingNumber' => $postalAddress['BuildingNumber'],
                'latitude' => $postalAddress['Latitude'],
                'longitude' => $postalAddress['Longitude'],
                'postalCode' => $postalAddress['Postcode'],
                'streetAddress' => $postalAddress['StreetName'],
            ];
        }, $dataResult['Postcodes']);

        return new JsonResponse($content);
    }
}
