<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The act of deactivating credits for a contract.
 *
 * @ORM\Entity()
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"deactivate_contract_credits_action_read"}},
 *     "denormalization_context"={"groups"={"deactivate_contract_credits_action_write"}},
 * })
 */
class DeactivateContractCreditsAction extends UpdateCreditsAction implements CreditsSubtractionInterface
{
    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var ApplicationRequest|null The object that helped the agent perform the action. e.g. John wrote a book with a pen.
     *
     * @ORM\ManyToOne(targetEntity="ApplicationRequest")
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
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription(?string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets instrument.
     *
     * @return ApplicationRequest|null
     */
    public function getInstrument(): ?ApplicationRequest
    {
        return $this->instrument;
    }

    /**
     * Sets instrument.
     *
     * @param ApplicationRequest|null $instrument
     *
     * @return $this
     */
    public function setInstrument(?ApplicationRequest $instrument)
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
}
