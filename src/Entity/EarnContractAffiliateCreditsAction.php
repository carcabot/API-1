<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use iter;

/**
 * The act of earning affiliate credits for a contract.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"earn_contract_affiliate_credits_action_read"}},
 *     "denormalization_context"={"groups"={"earn_contract_affiliate_credits_action_write"}}
 * })
 */
class EarnContractAffiliateCreditsAction extends UpdateCreditsAction implements CreditsAdditionInterface
{
    /**
     * @var Contract|null The object that helped the agent perform the action. e.g. John wrote a book with a pen.
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/instrument")
     */
    protected $instrument;

    /**
     * @var Contract The object upon which the action is carried out, whose state is kept intact or changed. Also known as the semantic roles patient, affected or undergoer (which change their state) or theme (which doesn't). e.g. John read a book.
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/object")
     */
    protected $object;

    /**
     * @var Collection<AffiliateProgramTransaction> An affiliate program transaction.
     *
     * @ORM\ManyToMany(targetEntity="AffiliateProgramTransaction", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     name="earn_contract_affiliate_credits_transactions",
     *     joinColumns={@ORM\JoinColumn(name="earn_contract_affiliate_credits_action_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="affiliate_program_transaction_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    /**
     * Gets instrument.
     *
     * @return Contract|null
     */
    public function getInstrument(): ?Contract
    {
        return $this->instrument;
    }

    /**
     * Sets instrument.
     *
     * @param Contract|null $instrument
     *
     * @return $this
     */
    public function setInstrument(?Contract $instrument)
    {
        $this->instrument = $instrument;

        return $this;
    }

    /**
     * Sets object.
     *
     * @param Contract $object
     *
     * @return $this
     */
    public function setObject(Contract $object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Gets object.
     *
     * @return Contract
     */
    public function getObject(): Contract
    {
        return $this->object;
    }

    /**
     * Adds transaction.
     *
     * @param AffiliateProgramTransaction $transaction
     *
     * @return $this
     */
    public function addTransaction(AffiliateProgramTransaction $transaction)
    {
        $this->transactions[] = $transaction;

        return $this;
    }

    /**
     * Removes transaction.
     *
     * @param AffiliateProgramTransaction $transaction
     *
     * @return $this
     */
    public function removeTransaction(AffiliateProgramTransaction $transaction)
    {
        $this->transactions->removeElement($transaction);

        return $this;
    }

    /**
     * Get transactions.
     *
     * @return AffiliateProgramTransaction[]
     */
    public function getTransactions(): array
    {
        return $this->transactions->getValues();
    }

    /**
     * Gets total transactions amount.
     *
     * @return MonetaryAmount
     */
    public function getTotalTransactionsAmount(): MonetaryAmount
    {
        $total = iter\reduce(function (MonetaryAmount $total, AffiliateProgramTransaction $transaction, $i): MonetaryAmount {
            $currency = $total->getCurrency();
            $totalValue = $total->getValue() + $transaction->getOrderAmount()->getValue();

            return new MonetaryAmount((string) \round($totalValue, 2), $currency);
        }, $this->getTransactions(), new MonetaryAmount('0', 'SGD'));

        return $total;
    }
}
