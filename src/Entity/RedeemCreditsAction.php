<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The act of credits redemption.
 *
 * @ORM\Entity(repositoryClass="App\Repository\CustomerAccountRelationshipRepository")
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"redeem_credits_action_read"}},
 *     "denormalization_context"={"groups"={"redeem_credits_action_write"}},
 *     "filters"={
 *         "redeem_credits_action.order",
 *         "redeem_credits_action.date",
 *         "redeem_credits_action.search",
 *     },
 * })
 */
class RedeemCreditsAction extends UpdateCreditsAction implements CreditsSubtractionInterface
{
    /**
     * @var Contract The object upon which the action is carried out, whose state is kept intact or changed. Also known as the semantic roles patient, affected or undergoer (which change their state) or theme (which doesn't). e.g. John read a book.
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/object")
     */
    protected $object;

    /**
     * @var Order The object that helped the agent perform the action. e.g. John wrote a book with a pen.
     *
     * @ORM\ManyToOne(targetEntity="Order")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/instrument")
     */
    protected $instrument;

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
     * Sets instrument.
     *
     * @param Order $instrument
     *
     * @return $this
     */
    public function setInstrument(Order $instrument)
    {
        $this->instrument = $instrument;

        return $this;
    }

    /**
     * Gets instrument.
     *
     * @return Order
     */
    public function getInstrument(): Order
    {
        return $this->instrument;
    }
}
