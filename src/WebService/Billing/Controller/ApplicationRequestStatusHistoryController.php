<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ApplicationRequestStatusHistoryCollectionDataProvider;
use App\Entity\ApplicationRequest;
use App\Entity\ApplicationRequestStatusHistoryCollection;

class ApplicationRequestStatusHistoryController
{
    /**
     * @var ApplicationRequestStatusHistoryCollectionDataProvider
     */
    private $statusHistoryCollectionDataProvider;

    /**
     * @param ApplicationRequestStatusHistoryCollectionDataProvider $statusHistoryCollectionDataProvider
     */
    public function __construct(ApplicationRequestStatusHistoryCollectionDataProvider $statusHistoryCollectionDataProvider)
    {
        $this->statusHistoryCollectionDataProvider = $statusHistoryCollectionDataProvider;
    }

    public function __invoke(ApplicationRequest $applicationRequest): ApplicationRequestStatusHistoryCollection
    {
        $data = $this->statusHistoryCollectionDataProvider->getSubresource(ApplicationRequest::class, ['id' => $applicationRequest->getId()], ['status_history_read'], 'GET');

        return $data;
    }
}
