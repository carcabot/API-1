<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\WebService\Billing\Controller\ContractFinancialHistoryController;

/**
 * The contract financial history.
 *
 * @ApiResource(iri="ContractFinancialHistory", attributes={
 *     "normalization_context"={"groups"={"contract_financial_history_read"}},
 * },
 * collectionOperations={
 *     "get",
 *     "get_contract_financial_history"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/financial_histories.{_format}",
 *         "controller"=ContractFinancialHistoryController::class,
 *         "normalization_context"={"groups"={"contract_financial_history_read"}},
 *     },
 * },
 * itemOperations={"get"})
 */
class ContractFinancialHistory
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var MonetaryAmount The amount.
     */
    protected $amount;

    /**
     * @var \DateTime|null The date on which the CreativeWork was created or the item was added to a DataFeed.
     */
    protected $dateCreated;

    /**
     * @var string The payment mode.
     */
    protected $paymentMode;

    /**
     * @var string The payment status.
     */
    protected $paymentStatus;

    /**
     * @var string The reference number.
     */
    protected $referenceNumber;

    /**
     * @var string The status.
     */
    protected $status;

    /**
     * @var QuantitativeValue The total billed consumption.
     */
    protected $totalBilledConsumption;

    /**
     * @var string The document type.
     */
    protected $type;

    public function __construct(MonetaryAmount $amount, ?\DateTime $dateCreated, string $paymentMode, string $paymentStatus, string $referenceNumber, string $status, QuantitativeValue $totalBilledConsumption, string $type)
    {
        $this->id = \uniqid();
        $this->amount = $amount;
        $this->dateCreated = $dateCreated;
        $this->paymentMode = $paymentMode;
        $this->paymentStatus = $paymentStatus;
        $this->referenceNumber = $referenceNumber;
        $this->status = $status;
        $this->totalBilledConsumption = $totalBilledConsumption;
        $this->type = $type;
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
     * Gets amount.
     *
     * @return MonetaryAmount
     */
    public function getAmount(): MonetaryAmount
    {
        return $this->amount;
    }

    /**
     * Gets dateCreated.
     *
     * @return \DateTime|null
     */
    public function getDateCreated(): ?\DateTime
    {
        return $this->dateCreated;
    }

    /**
     * Gets paymentMode.
     *
     * @return string
     */
    public function getPaymentMode(): string
    {
        return $this->paymentMode;
    }

    /**
     * Gets paymentStatus.
     *
     * @return string
     */
    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    /**
     * Gets referenceNumber.
     *
     * @return string
     */
    public function getReferenceNumber(): string
    {
        return $this->referenceNumber;
    }

    /**
     * Gets status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Gets totalBilledConsumption.
     *
     * @return QuantitativeValue
     */
    public function getTotalBilledConsumption(): QuantitativeValue
    {
        return $this->totalBilledConsumption;
    }

    /**
     * Gets type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
