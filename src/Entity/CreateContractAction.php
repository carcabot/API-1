<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The act of creating a contract.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"create_contract_action_read"}},
 *     "denormalization_context"={"groups"={"create_contract_action_write"}},
 * })
 */
class CreateContractAction extends ContractAction
{
}
