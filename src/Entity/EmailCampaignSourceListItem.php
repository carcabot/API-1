<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * An email campaign source list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"email_campaign_source_list_item_read"}},
 *     "denormalization_context"={"groups"={"email_campaign_source_list_item_write"}},
 * })
 */
class EmailCampaignSourceListItem extends CampaignSourceListItem
{
    /**
     * @var string Email address.
     *
     * @ORM\Column(type="string")
     * @ApiProperty()
     */
    protected $emailAddress;

    /**
     * Gets email address.
     *
     * @return string
     */
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    /**
     * Sets email.
     *
     * @param string $emailAddress
     */
    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }
}
