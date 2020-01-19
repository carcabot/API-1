<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\ContractActionType;
use Doctrine\ORM\Mapping as ORM;

/**
 * The act of updating a contract.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"update_contract_action_read"}},
 *     "denormalization_context"={"groups"={"update_contract_action_write"}},
 * })
 */
class UpdateContractAction extends ContractAction
{
    /**
     * @var ContractActionType The action type.
     *
     * @ORM\Column(type="contract_action_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * Sets type.
     *
     * @param ContractActionType $type
     *
     * @return $this
     */
    public function setType(ContractActionType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return ContractActionType
     */
    public function getType(): ContractActionType
    {
        return $this->type;
    }
}
