<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A money credits transaction.
 *
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"amount_value"}),
 *     @ORM\Index(columns={"balance_value"}),
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"money_credits_transaction_read"}},
 *     "denormalization_context"={"groups"={"money_credits_transaction_write"}}
 * })
 */
class MoneyCreditsTransaction extends CreditsTransaction
{
    /**
     * @var MonetaryAmount The amount involved in the transaction.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $amount;

    /**
     * @var MonetaryAmount The balance after the transaction.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $balance;

    /**
     * @var Collection<Payment> A payment.
     *
     * @ORM\ManyToMany(targetEntity="Payment", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="money_credits_transaction_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="payment_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $payments;

    public function __construct()
    {
        $this->amount = new MonetaryAmount();
        $this->balance = new MonetaryAmount();
        $this->payments = new ArrayCollection();
    }

    /**
     * Sets amount.
     *
     * @param MonetaryAmount $amount
     *
     * @return $this
     */
    public function setAmount(MonetaryAmount $amount)
    {
        $this->amount = $amount;

        return $this;
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
     * Sets balance.
     *
     * @param MonetaryAmount $balance
     *
     * @return $this
     */
    public function setBalance(MonetaryAmount $balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Gets balance.
     *
     * @return MonetaryAmount
     */
    public function getBalance(): MonetaryAmount
    {
        return $this->balance;
    }

    /**
     * Adds payment.
     *
     * @param Payment $payment
     *
     * @return $this
     */
    public function addPayment(Payment $payment)
    {
        $this->payments[] = $payment;

        return $this;
    }

    /**
     * Removes payment.
     *
     * @param Payment $payment
     *
     * @return $this
     */
    public function removePayment(Payment $payment)
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * Gets payments.
     *
     * @return Payment[]
     */
    public function getPayments(): array
    {
        return $this->payments->getValues();
    }
}
