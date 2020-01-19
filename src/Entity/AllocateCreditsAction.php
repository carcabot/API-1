<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The act of allocating credits to a Contract.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"allocate_credits_action_read"}},
 *     "denormalization_context"={"groups"={"allocate_credits_action_write"}}
 * })
 */
class AllocateCreditsAction extends UpdateCreditsAction implements CreditsSubtractionInterface
{
    /**
     * @var CustomerAccount The object upon which the action is carried out, whose state is kept intact or changed. Also known as the semantic roles patient, affected or undergoer (which change their state) or theme (which doesn't). e.g. John read a book.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/object")
     */
    protected $object;

    /**
     * @var Contract A sub property of participant. The participant who is at the receiving end of the action.
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/recipient")
     */
    protected $recipient;

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
     * Sets recipient.
     *
     * @param Contract $recipient
     *
     * @return $this
     */
    public function setRecipient(Contract $recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Gets recipient.
     *
     * @return Contract
     */
    public function getRecipient(): Contract
    {
        return $this->recipient;
    }
}
