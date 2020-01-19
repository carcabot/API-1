<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\WebService\Billing\Controller\ContractBasicBillingSummaryController;
use App\WebService\Billing\Controller\ContractBillingInformationController;
use App\WebService\Billing\Controller\ContractBillingSummaryController;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * The contract summary from billing web service.
 *
 * @ApiResource(iri="ContractBillingSummary", attributes={
 *     "normalization_context"={"groups"={"contract_billing_summary_read"}},
 * },
 * collectionOperations={
 *     "get",
 *     "get_billing_summary"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/billing_summary.{_format}",
 *         "controller"=ContractBillingSummaryController::class,
 *         "normalization_context"={"groups"={"contract_billing_summary_read", "contract_arrears_history_read", "contract_consumption_history_read", "contract_financial_history_read", "contract_giro_history_read"}},
 *     },
 *     "get_basic_billing_summary"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/basic_billing_summary.{_format}",
 *         "controller"=ContractBasicBillingSummaryController::class,
 *         "normalization_context"={"groups"={"contract_billing_summary_read"}},
 *     },
 *     "get_billing_information"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/billing_information.{_format}",
 *         "controller"=ContractBillingInformationController::class,
 *         "normalization_context"={"groups"={"contract_billing_summary_read"}},
 *     },
 * },
 * itemOperations={"get"})
 */
class ContractBillingSummary
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var Collection<ContractArrearsHistory> The contract arrear history.
     */
    protected $arrearHistories;

    /**
     * @var Collection<ContractConsumptionHistory> The contract consumption history.
     */
    protected $consumptionHistories;

    /**
     * @var string|null The current arrears status.
     */
    protected $currentArrearsStatus;

    /**
     * @var string|null The current GIRO account status.
     */
    protected $currentGiroAccountStatus;

    /**
     * @var MonetaryAmount The deposit amount.
     */
    protected $depositAmount;

    /**
     * @var Collection<ContractEmailHistory> The contract email history.
     */
    protected $emailHistories;

    /**
     * @var Collection<ContractFinancialHistory> The contract financial history.
     */
    protected $financialHistories;

    /**
     * @var Collection<ContractGiroHistory> The contract GIRO history.
     */
    protected $giroHistories;

    /**
     * @var \DateTime|null The latest invoice print date.
     */
    protected $latestInvoicePrintOutDate;

    /**
     * @var MonetaryAmount|null The outstanding balance.
     */
    protected $outstandingBalance;

    /**
     * @var string|null The payment mode.
     */
    protected $paymentMode;

    public function __construct(?string $currentArrearsStatus, ?string $currentGiroAccountStatus, MonetaryAmount $depositAmount, ?\DateTime $latestInvoicePrintOutDate, ?MonetaryAmount $outstandingBalance, ?string $paymentMode = null)
    {
        $this->id = \uniqid();
        $this->arrearHistories = new ArrayCollection();
        $this->consumptionHistories = new ArrayCollection();
        $this->currentArrearsStatus = $currentArrearsStatus;
        $this->currentGiroAccountStatus = $currentGiroAccountStatus;
        $this->depositAmount = $depositAmount;
        $this->emailHistories = new ArrayCollection();
        $this->financialHistories = new ArrayCollection();
        $this->giroHistories = new ArrayCollection();
        $this->latestInvoicePrintOutDate = $latestInvoicePrintOutDate;
        $this->outstandingBalance = $outstandingBalance;
        $this->paymentMode = $paymentMode;
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
     * Adds arrearHistory.
     *
     * @param ContractArrearsHistory $arrearHistory
     *
     * @return $this
     */
    public function addArrearHistory(ContractArrearsHistory $arrearHistory)
    {
        $this->arrearHistories[] = $arrearHistory;

        return $this;
    }

    /**
     * Gets arrearHistories.
     *
     * @return ContractArrearsHistory[]
     */
    public function getArrearHistories(): array
    {
        return $this->arrearHistories->getValues();
    }

    /**
     * Adds consumptionHistory.
     *
     * @param ContractConsumptionHistory $consumptionHistory
     *
     * @return $this
     */
    public function addConsumptionHistory(ContractConsumptionHistory $consumptionHistory)
    {
        $this->consumptionHistories[] = $consumptionHistory;

        return $this;
    }

    /**
     * Gets consumptionHistories.
     *
     * @return ContractConsumptionHistory[]
     */
    public function getConsumptionHistories(): array
    {
        return $this->consumptionHistories->getValues();
    }

    /**
     * Gets currentArrearsStatus.
     *
     * @return string|null
     */
    public function getCurrentArrearsStatus(): ?string
    {
        return $this->currentArrearsStatus;
    }

    /**
     * Gets currentGiroAccountStatus.
     *
     * @return string|null
     */
    public function getCurrentGiroAccountStatus(): ?string
    {
        return $this->currentGiroAccountStatus;
    }

    /**
     * Sets currentGiroAccountStatus.
     *
     * @param string|null $currentGiroAccountStatus
     *
     * @return $this
     */
    public function setCurrentGiroAccountStatus(?string $currentGiroAccountStatus)
    {
        $this->currentGiroAccountStatus = $currentGiroAccountStatus;

        return $this;
    }

    /**
     * Gets depositAmount.
     *
     * @return MonetaryAmount
     */
    public function getDepositAmount(): MonetaryAmount
    {
        return $this->depositAmount;
    }

    /**
     * Adds emailHistory.
     *
     * @param ContractEmailHistory $emailHistory
     *
     * @return $this
     */
    public function addEmailHistory(ContractEmailHistory $emailHistory)
    {
        $this->emailHistories[] = $emailHistory;

        return $this;
    }

    /**
     * Gets emailHistories.
     *
     * @return ContractEmailHistory[]
     */
    public function getEmailHistories(): array
    {
        return $this->emailHistories->getValues();
    }

    /**
     * Adds financialHistory.
     *
     * @param ContractFinancialHistory $financialHistory
     *
     * @return $this
     */
    public function addFinancialHistory(ContractFinancialHistory $financialHistory)
    {
        $this->financialHistories[] = $financialHistory;

        return $this;
    }

    /**
     * Gets financialHistories.
     *
     * @return ContractFinancialHistory[]
     */
    public function getFinancialHistories(): array
    {
        return $this->financialHistories->getValues();
    }

    /**
     * Adds giroHistory.
     *
     * @param ContractGiroHistory $giroHistory
     *
     * @return $this
     */
    public function addGiroHistory(ContractGiroHistory $giroHistory)
    {
        $this->giroHistories[] = $giroHistory;

        return $this;
    }

    /**
     * Gets giroHistories.
     *
     * @return ContractGiroHistory[]
     */
    public function getGiroHistories(): array
    {
        return $this->giroHistories->getValues();
    }

    /**
     * Gets latestInvoicePrintOutDate.
     *
     * @return \DateTime|null
     */
    public function getLatestInvoicePrintOutDate(): ?\DateTime
    {
        return $this->latestInvoicePrintOutDate;
    }

    /**
     * Gets outstandingBalance.
     *
     * @return MonetaryAmount|null
     */
    public function getOutstandingBalance(): ?MonetaryAmount
    {
        return $this->outstandingBalance;
    }

    /**
     * Sets outstandingBalance.
     *
     * @param MonetaryAmount|null $outstandingBalance
     */
    public function setOutstandingBalance(?MonetaryAmount $outstandingBalance)
    {
        $this->outstandingBalance = $outstandingBalance;
    }

    /**
     * Gets paymentMode.
     *
     * @return string|null
     */
    public function getPaymentMode(): ?string
    {
        return $this->paymentMode;
    }

    /**
     * Sets paymentMode.
     *
     * @param string|null $paymentMode
     */
    public function setPaymentMode(?string $paymentMode): void
    {
        $this->paymentMode = $paymentMode;
    }
}
