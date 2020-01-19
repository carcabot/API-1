<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;

/**
 * A sms campaign source list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"sms_campaign_source_list_item_read"}},
 *     "denormalization_context"={"groups"={"sms_campaign_source_list_item_write"}},
 * })
 */
class SmsCampaignSourceListItem extends CampaignSourceListItem
{
    /**
     * @var PhoneNumber Mobile number.
     *
     * @ORM\Column(type="phone_number")
     * @ApiProperty()
     */
    protected $mobileNumber;

    /**
     * Gets mobile number.
     *
     * @return PhoneNumber
     */
    public function getMobileNumber(): PhoneNumber
    {
        return $this->mobileNumber;
    }

    /**
     * Sets mobile number.
     *
     * @param PhoneNumber $mobileNumber
     */
    public function setMobileNumber(PhoneNumber $mobileNumber): void
    {
        $this->mobileNumber = $mobileNumber;
    }
}
