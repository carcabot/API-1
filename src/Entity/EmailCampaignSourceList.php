<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A list of email sources.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"email_campaign_source_list_read"}},
 *     "denormalization_context"={"groups"={"email_campaign_source_list_write"}},
 *     "filters"={
 *         "item_list.order",
 *         "item_list.search",
 *     },
 * })
 */
class EmailCampaignSourceList extends SourceList
{
}
