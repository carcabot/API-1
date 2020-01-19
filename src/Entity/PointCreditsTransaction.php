<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A point credits transaction.
 *
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"amount_value"}),
 *     @ORM\Index(columns={"balance_value"}),
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"point_credits_transaction_read"}},
 *     "denormalization_context"={"groups"={"point_credits_transaction_write"}}
 * })
 */
class PointCreditsTransaction extends CreditsTransaction
{
    /**
     * @var QuantitativeValue The amount involved in the transaction.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $amount;

    /**
     * @var QuantitativeValue The balance after the transaction.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $balance;

    public function __construct()
    {
        $this->amount = new QuantitativeValue();
        $this->balance = new QuantitativeValue();
    }

    /**
     * Sets amount.
     *
     * @param QuantitativeValue $amount
     *
     * @return $this
     */
    public function setAmount(QuantitativeValue $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Gets amount.
     *
     * @return QuantitativeValue
     */
    public function getAmount(): QuantitativeValue
    {
        return $this->amount;
    }

    /**
     * Sets balance.
     *
     * @param QuantitativeValue $balance
     *
     * @return $this
     */
    public function setBalance(QuantitativeValue $balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Gets balance.
     *
     * @return QuantitativeValue
     */
    public function getBalance(): QuantitativeValue
    {
        return $this->balance;
    }
}
