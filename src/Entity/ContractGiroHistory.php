<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\WebService\Billing\Controller\ContractGiroHistoryController;

/**
 * The contract GIRO history.
 *
 * @ApiResource(iri="ContractGiroHistory", attributes={
 *     "normalization_context"={"groups"={"contract_giro_history_read"}},
 * },
 * collectionOperations={
 *     "get",
 *     "get_contract_giro_history"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/giro_histories.{_format}",
 *         "controller"=ContractGiroHistoryController::class,
 *         "normalization_context"={"groups"={"contract_giro_history_read"}},
 *     },
 * },
 * itemOperations={"get"})
 */
class ContractGiroHistory
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var string|null The bank account number.
     */
    protected $bankAccountNumber;

    /**
     * @var string|null The bank name.
     */
    protected $bankName;

    /**
     * @var \DateTime|null The end date and time of the item (in ISO 8601 date format).
     */
    protected $endDate;

    /**
     * @var \DateTime|null The start date and time of the item (in ISO 8601 date format).
     */
    protected $startDate;

    /**
     * @var string|null The GIRO status.
     */
    protected $status;

    public function __construct(?string $bankAccountNumber, ?string $bankName, ?\DateTime $endDate, ?\DateTime $startDate, ?string $status)
    {
        $this->id = \uniqid();
        $this->bankAccountNumber = $bankAccountNumber;
        $this->bankName = $bankName;
        $this->endDate = $endDate;
        $this->startDate = $startDate;
        $this->status = $status;
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
     * Gets bankAccountNumber.
     *
     * @return string|null
     */
    public function getBankAccountNumber(): ?string
    {
        return $this->bankAccountNumber;
    }

    /**
     * Gets bankName.
     *
     * @return string|null
     */
    public function getBankName(): ?string
    {
        return $this->bankName;
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
     * Gets startDate.
     *
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    /**
     * Gets status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Sets status.
     *
     * @param string|null $status
     *
     * @return $this
     */
    public function setStatus(?string $status)
    {
        $this->status = $status;

        return $this;
    }
}
