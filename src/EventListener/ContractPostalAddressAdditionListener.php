<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\ContractPostalAddress;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ContractPostalAddressAdditionListener
{
    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * @param WebServiceClient $webServiceClient
     */
    public function __construct(WebServiceClient $webServiceClient)
    {
        $this->webServiceClient = $webServiceClient;
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof ContractPostalAddress)) {
            return;
        }

        /** @var ContractPostalAddress $contract */
        $contractPostalAddress = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        if ($request->headers->has('x-custom-contract-postal-address')) {
            $this->webServiceClient->updateContractMailingAddress($contractPostalAddress);
        }
    }
}
