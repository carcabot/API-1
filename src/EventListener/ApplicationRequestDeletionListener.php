<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\ApplicationRequest;
use App\Enum\ApplicationRequestStatus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApplicationRequestDeletionListener
{
    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof ApplicationRequest)) {
            return;
        }

        /** @var ApplicationRequest $applicationRequest */
        $applicationRequest = $controllerResult;

        if (Request::METHOD_DELETE !== $request->getMethod()) {
            return;
        }

        if (!\in_array($applicationRequest->getStatus()->getValue(), [
                ApplicationRequestStatus::DRAFT,
                ApplicationRequestStatus::PARTNER_DRAFT,
            ], true)
        ) {
            throw new BadRequestHttpException('Unable to DELETE the application request.');
        }
    }
}
