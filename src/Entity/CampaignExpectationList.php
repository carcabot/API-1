<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A list of campaign expectation.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"campaign_expectation_list_read"}},
 *     "denormalization_context"={"groups"={"campaign_expectation_list_write"}},
 * })
 */
class CampaignExpectationList extends ItemList
{
}
