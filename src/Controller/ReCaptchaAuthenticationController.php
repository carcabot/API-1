<?php

declare(strict_types=1);

namespace App\Controller;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Diactoros\Response;

class ReCaptchaAuthenticationController
{
    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var string
     */
    private $SECRET_KEY;

    public function __construct(string $SECRET_KEY)
    {
        $this->client = new GuzzleClient();
        $this->SECRET_KEY = $SECRET_KEY;
    }

    /**
     * @Route("/verify_recaptcha_token", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function authenticate(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);

        $verificationResponse = $this->client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => $this->SECRET_KEY,
                'response' => $params['token'],
            ],
        ]);

        $verificationResult = \json_decode((string) $verificationResponse->getBody(), true);

        return new Response\JsonResponse($verificationResult);
    }
}
