<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The act of earning credits for a customer.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"earn_customer_credits_action_read"}},
 *     "denormalization_context"={"groups"={"earn_customer_credits_action_write"}}
 * })
 */
class EarnCustomerCreditsAction extends UpdateCreditsAction implements CreditsAdditionInterface
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
     * @var CustomerAccount The object upon which the action is carried out, whose state is kept intact or changed. Also known as the semantic roles patient, affected or undergoer (which change their state) or theme (which doesn't). e.g. John read a book.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/object")
     */
    protected $object;

    /**
     * @var CreditsScheme|null The credits scheme.
     *
     * @ORM\ManyToOne(targetEntity="CreditsScheme")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $scheme;

    /**
     * @var CustomerAccount|null A sub property of participant. The participant who is at the sending end of the action.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/sender")
     */
    protected $sender;

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

    /**
     * Sets scheme.
     *
     * @param CreditsScheme|null $scheme
     *
     * @return $this
     */
    public function setScheme(?CreditsScheme $scheme)
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * Gets scheme.
     *
     * @return CreditsScheme|null
     */
    public function getScheme(): ?CreditsScheme
    {
        return $this->scheme;
    }

    /**
     * Sets sender.
     *
     * @param CustomerAccount|null $sender
     *
     * @return $this
     */
    public function setSender(?CustomerAccount $sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Gets sender.
     *
     * @return CustomerAccount|null
     */
    public function getSender(): ?CustomerAccount
    {
        return $this->sender;
    }
}
