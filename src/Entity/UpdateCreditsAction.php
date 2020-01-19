<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\ActionStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * The act of updating the credits amount.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"update_credits_action_read"}},
 *     "denormalization_context"={"groups"={"update_credits_action_write"}},
 *     "filters"={
 *         "update_credits_action.order",
 *         "update_credits_action.date",
 *         "update_credits_action.search",
 *     },
 * })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=128)
 * @ORM\DiscriminatorMap({
 *      "allocate_credits_action"="AllocateCreditsAction",
 *      "deactivate_contract_credits_action"="DeactivateContractCreditsAction",
 *      "earn_contract_affiliate_credits_action"="EarnContractAffiliateCreditsAction",
 *      "earn_contract_credits_action"="EarnContractCreditsAction",
 *      "earn_customer_affiliate_credits_action"="EarnCustomerAffiliateCreditsAction",
 *      "earn_customer_credits_action"="EarnCustomerCreditsAction",
 *      "expire_contract_credits_action"="ExpireContractCreditsAction",
 *      "expire_customer_credits_action"="ExpireCustomerCreditsAction",
 *      "redeem_credits_action"="RedeemCreditsAction",
 *      "transfer_credits_action"="TransferCreditsAction",
 *      "update_credits_action"="UpdateCreditsAction",
 *      "withdraw_credits_action"="WithdrawCreditsAction",
 * })
 */
class UpdateCreditsAction implements CreditsObjectInterface
{
    use Traits\BlameableTrait;
    use Traits\TimestampableTrait;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string|null The amount of credits.
     *
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     * @ApiProperty()
     */
    protected $amount;

    /**
     * @var CreditsTransaction The result produced in the action. e.g. John wrote a book.
     *
     * @ORM\ManyToOne(targetEntity="CreditsTransaction", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/result")
     */
    protected $creditsTransaction;

    /**
     * @var string|null The currency in which the monetary amount is expressed.
     *
     * Use standard formats: ISO 4217 currency format e.g. "USD"; Ticker symbol for cryptocurrencies e.g. "BTC"; well known names for Local Exchange Tradings Systems (LETS) and other currency types e.g. "Ithaca HOUR".
     *
     * @ORM\Column(type="string", length=3, nullable=true)
     * @ApiProperty(iri="http://schema.org/currency")
     */
    protected $currency;

    /**
     * @var \DateTime|null The endTime of something. For a reserved event or service (e.g. FoodEstablishmentReservation), the time that it is expected to end. For actions that span a period of time, when the action was performed. e.g. John wrote a book from January to *December*.
     *
     * Note that Event uses startDate/endDate instead of startTime/endTime, even when describing dates with times. This situation may be clarified in future revisions.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/endTime")
     */
    protected $endTime;

    /**
     * @var \DateTime The startTime of something. For a reserved event or service (e.g. FoodEstablishmentReservation), the time that it is expected to start. For actions that span a period of time, when the action was performed. e.g. John wrote a book from *January* to December.
     *
     * Note that Event uses startDate/endDate instead of startTime/endTime, even when describing dates with times. This situation may be clarified in future revisions.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty(iri="http://schema.org/startTime")
     */
    protected $startTime;

    /**
     * @var ActionStatus The status of the transaction performed.
     *
     * @ORM\Column(type="action_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * Gets id.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Sets amount.
     *
     * @param string|null $amount
     *
     * @return $this
     */
    public function setAmount(?string $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Gets amount.
     *
     * @return string|null
     */
    public function getAmount(): ?string
    {
        return $this->amount;
    }

    /**
     * Sets creditsTransaction.
     *
     * @param CreditsTransaction $creditsTransaction
     *
     * @return $this
     */
    public function setCreditsTransaction(CreditsTransaction $creditsTransaction)
    {
        $this->creditsTransaction = $creditsTransaction;

        return $this;
    }

    /**
     * Gets creditsTransaction.
     *
     * @return CreditsTransaction
     */
    public function getCreditsTransaction(): CreditsTransaction
    {
        return $this->creditsTransaction;
    }

    /**
     * Sets currency.
     *
     * @param string|null $currency
     *
     * @return $this
     */
    public function setCurrency(?string $currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Gets currency.
     *
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * Sets endTime.
     *
     * @param \DateTime|null $endTime
     *
     * @return $this
     */
    public function setEndTime(?\DateTime $endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Gets endTime.
     *
     * @return \DateTime|null
     */
    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    /**
     * Sets startTime.
     *
     * @param \DateTime $startTime
     *
     * @return $this
     */
    public function setStartTime(\DateTime $startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Gets startTime.
     *
     * @return \DateTime
     */
    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }

    /**
     * Sets status.
     *
     * @param ActionStatus $status
     *
     * @return $this
     */
    public function setStatus(ActionStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return ActionStatus
     */
    public function getStatus(): ActionStatus
    {
        return $this->status;
    }

    public function getObject()
    {
    }
}
