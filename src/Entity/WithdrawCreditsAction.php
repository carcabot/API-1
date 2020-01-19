<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The act of withdrawing credits.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"withdraw_credits_action_read"}},
 *     "denormalization_context"={"groups"={"withdraw_credits_action_write"}}
 * })
 */
class WithdrawCreditsAction extends UpdateCreditsAction implements CreditsSubtractionInterface
{
    /**
     * @var Contract|null The contract to be withdrawn for.
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $contract;

    /**
     * @var Payment The object that helped the agent perform the action. e.g. John wrote a book with a pen.
     *
     * @ORM\ManyToOne(targetEntity="Payment")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/instrument")
     */
    protected $instrument;

    /**
     * @var CustomerAccount The object upon which the action is carried out, whose state is kept intact or changed. Also known as the semantic roles patient, affected or undergoer (which change their state) or theme (which doesn't). e.g. John read a book.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/object")
     */
    protected $object;

    /**
     * Sets contract.
     *
     * @param Contract|null $contract
     *
     * @return $this
     */
    public function setContract(?Contract $contract)
    {
        $this->contract = $contract;

        return $this;
    }

    /**
     * Gets contract.
     *
     * @return Contract|null
     */
    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    /**
     * Sets instrument.
     *
     * @param Payment $instrument
     *
     * @return $this
     */
    public function setInstrument(Payment $instrument)
    {
        $this->instrument = $instrument;

        return $this;
    }

    /**
     * Gets instrument.
     *
     * @return Payment
     */
    public function getInstrument(): Payment
    {
        return $this->instrument;
    }

    /**
     * Sets object.
     *
     * @param CustomerAccount $object
     *
     * @return $this
     */
    public function setObject(CustomerAccount $object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Gets object.
     *
     * @return CustomerAccount
     */
    public function getObject(): CustomerAccount
    {
        return $this->object;
    }
}
