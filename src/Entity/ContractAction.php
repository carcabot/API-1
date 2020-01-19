<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\ActionStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * An action performed by a direct agent and indirect participants upon a direct object. Optionally happens at a location with the help of an inanimate instrument. The execution of the action may produce a result. Specific action sub-type documentation specifies the exact expectation of each argument/role.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=128)
 * @ORM\DiscriminatorMap({
 *     "contract_action"="ContractAction",
 *     "create_contract_action"="CreateContractAction",
 *     "update_contract_action"="UpdateContractAction",
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"contract_action_read"}},
 *     "denormalization_context"={"groups"={"contract_action_write"}},
 * })
 */
class ContractAction
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
     * @var ApplicationRequest|null The object that helped the agent perform the action. e.g. John wrote a book with a pen.
     *
     * @ORM\ManyToOne(targetEntity="ApplicationRequest")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/instrument")
     */
    protected $instrument;

    /**
     * @var ActionStatus The status of the action.
     *
     * @ORM\Column(type="action_status_enum", nullable=false)
     * @ApiProperty(iri="http://schema.org/actionStatus")
     */
    protected $actionStatus;

    /**
     * @var Contract The object upon which the action is carried out, whose state is kept intact or changed. Also known as the semantic roles patient, affected or undergoer (which change their state) or theme (which doesn't). e.g. John read a book.
     *
     * @ORM\ManyToOne(targetEntity="Contract",cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @ApiProperty(iri="http://schema.org/object")
     */
    protected $object;

    /**
     * @var Contract|null
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $result;

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
     * Gets instrument.
     *
     * @return ApplicationRequest|null
     */
    public function getInstrument(): ?ApplicationRequest
    {
        return $this->instrument;
    }

    /**
     * Sets actionStatus.
     *
     * @param ActionStatus $actionStatus
     *
     * @return $this
     */
    public function setActionStatus(ActionStatus $actionStatus)
    {
        $this->actionStatus = $actionStatus;

        return $this;
    }

    /**
     * Gets actionStatus.
     *
     * @return ActionStatus
     */
    public function getActionStatus(): ActionStatus
    {
        return $this->actionStatus;
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
     * @return Contract|null
     */
    public function getResult(): ?Contract
    {
        return $this->result;
    }

    /**
     * @param Contract|null $result
     */
    public function setResult(?Contract $result)
    {
        $this->result = $result;
    }
}
