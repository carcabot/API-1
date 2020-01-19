<?php

declare(strict_types=1);

namespace App\Bridge\Controller;

use App\Bridge\Services\TariffRateApi;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Diactoros\Response\EmptyResponse;

class TariffRateController
{
    /**
     * @var TariffRateApi
     */
    private $tariffRateApi;

    /**
     * @param TariffRateApi $tariffRateApi
     */
    public function __construct(TariffRateApi $tariffRateApi)
    {
        $this->tariffRateApi = $tariffRateApi;
    }

    /**
     * @Route("/bridge/tariff_rates", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function createOrUpdateAction(ServerRequestInterface $request): HttpResponseInterface
    {
        $tariffRates = \json_decode($request->getBody()->getContents(), true);
        $this->tariffRateApi->updateTariffRates($tariffRates);

        return new EmptyResponse(204);
    }
}
