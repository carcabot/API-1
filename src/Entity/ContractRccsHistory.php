<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\WebService\Billing\Controller\ContractRCCSHistoryController;

/**
 * The contract rccs history.
 *
 * @ApiResource(iri="ContractRccsHistory", attributes={
 *     "normalization_context"={"groups"={"contract_rccs_history_read"}},
 * },
 * collectionOperations={
 *     "get",
 *     "get_contract_rccs_history"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/rccs_histories.{_format}",
 *         "controller"=ContractRCCSHistoryController::class,
 *         "normalization_context"={"groups"={"contract_rccs_history_read"}},
 *     },
 * },
 * itemOperations={"get"})
 */
class ContractRccsHistory
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $contractNumber;

    /**
     * @var string|null
     */
    protected $cardNumber;

    /**
     * @var \DateTime|null
     */
    protected $dateEffective;

    /**
     * @var \DateTime|null
     */
    protected $dateExpired;

    /**
     * @var \DateTime|null
     */
    protected $dateTerminated;

    /**
     * @var string|null
     */
    protected $groupNumber;

    /**
     * @var string|null
     */
    protected $status;

    /**
     * @param string|null    $contractNumber
     * @param string|null    $cardNumber
     * @param \DateTime|null $dateEffective
     * @param \DateTime|null $dateExpired
     * @param \DateTime|null $dateTerminated
     * @param string|null    $groupNumber
     * @param string|null    $status
     */
    public function __construct(?string $contractNumber, ?string $cardNumber, ?\DateTime $dateEffective, ?\DateTime $dateExpired, ?\DateTime $dateTerminated, ?string $groupNumber, ?string $status)
    {
        $this->id = \uniqid();
        $this->contractNumber = $contractNumber;
        $this->cardNumber = $cardNumber;
        $this->dateEffective = $dateEffective;
        $this->dateExpired = $dateExpired;
        $this->dateTerminated = $dateTerminated;
        $this->groupNumber = $groupNumber;
        $this->status = $status;
    }

    /**
     * Gets id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets account number.
     *
     * @return string|null
     */
    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    /**
     * Gets card number.
     *
     * @return string|null
     */
    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }

    /**
     * Gets effective date.
     *
     * @return \DateTime|null
     */
    public function getDateEffective(): ?\DateTime
    {
        return $this->dateEffective;
    }

    /**
     * Gets expired date.
     *
     * @return \DateTime|null
     */
    public function getDateExpired(): ?\DateTime
    {
        return $this->dateExpired;
    }

    /**
     * Gets terminated date.
     *
     * @return \DateTime|null
     */
    public function getDateTerminated(): ?\DateTime
    {
        return $this->dateTerminated;
    }

    /**
     * Gets group number.
     *
     * @return string|null
     */
    public function getGroupNumber(): ?string
    {
        return $this->groupNumber;
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
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }
}
