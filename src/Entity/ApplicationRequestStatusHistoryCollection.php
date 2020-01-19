<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * The status history collection from billing web service.
 *
 * @ApiResource(iri="ApplicationRequestStatusHistoryCollection")
 */
class ApplicationRequestStatusHistoryCollection
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var Collection<ApplicationRequestStatusHistory> The status history from billing web service.
     */
    protected $statusHistories;

    public function __construct()
    {
        $this->id = \uniqid();
        $this->statusHistories = new ArrayCollection();
    }

    /**
     * Gets id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Adds statusHistory.
     *
     * @param ApplicationRequestStatusHistory $statusHistory
     *
     * @return $this
     */
    public function addStatusHistory(ApplicationRequestStatusHistory $statusHistory)
    {
        $this->statusHistories[] = $statusHistory;

        return $this;
    }

    /**
     * Gets statusHistories.
     *
     * @return ApplicationRequestStatusHistory[]
     */
    public function getStatusHistories(): array
    {
        return $this->statusHistories->getValues();
    }
}
