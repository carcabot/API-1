<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\WebService\Billing\Controller\ContractConsumptionsByBillingPeriodController;

/**
 * The contract consumption history.
 *
 * @ApiResource(iri="ContractConsumptionHistory", attributes={
 *     "normalization_context"={"groups"={"contract_consumption_history_read"}},
 * },
 * collectionOperations={
 *     "get",
 *     "get_consumption_history"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/consumption_histories.{_format}",
 *         "controller"=ContractConsumptionsByBillingPeriodController::class,
 *         "normalization_context"={"groups"={"contract_consumption_history_read"}},
 *     },
 * },
 * itemOperations={"get"})
 */
class ContractConsumptionHistory
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var QuantitativeValue The consumption value.
     */
    protected $consumptionValue;

    /**
     * @var \DateTime|null The reading date.
     */
    protected $endDate;

    /**
     * @var string|null The meter number value.
     */
    protected $meterNumber;

    /**
     * @var \DateTime|null The reading date.
     */
    protected $startDate;

    public function __construct(QuantitativeValue $consumptionValue, ?\DateTime $endDate, ?\DateTime $startDate, ?string $meterNumber)
    {
        $this->id = \uniqid();
        $this->consumptionValue = $consumptionValue;
        $this->endDate = $endDate;
        $this->meterNumber = $meterNumber;
        $this->startDate = $startDate;
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
     * Gets consumptionValue.
     *
     * @return QuantitativeValue
     */
    public function getConsumptionValue(): QuantitativeValue
    {
        return $this->consumptionValue;
    }

    /**
     * Gets endDate.
     *
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * Gets meterNumber.
     *
     * @return string|null
     */
    public function getMeterNumber(): ?string
    {
        return $this->meterNumber;
    }

    /**
     * Gets startDate.
     *
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }
}
