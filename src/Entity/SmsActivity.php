<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\SMSType;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;

/**
 * An SMS activity carried out on an item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"sms_activity_read"}},
 *     "denormalization_context"={"groups"={"sms_activity_write"}},
 * })
 */
class SmsActivity extends Activity
{
    /**
     * @var PhoneNumber The recipient mobile phone number.
     *
     * @ORM\Column(type="phone_number", nullable=false)
     * @ApiProperty()
     */
    protected $recipientMobileNumber;

    /**
     * @var SMSType The sms type.
     *
     * @ORM\Column(type="sms_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * Sets recipientMobileNumber.
     *
     * @param PhoneNumber $recipientMobileNumber
     *
     * @return $this
     */
    public function setRecipientMobileNumber(PhoneNumber $recipientMobileNumber)
    {
        $this->recipientMobileNumber = $recipientMobileNumber;

        return $this;
    }

    /**
     * Gets recipientMobileNumber.
     *
     * @return PhoneNumber
     */
    public function getRecipientMobileNumber(): PhoneNumber
    {
        return $this->recipientMobileNumber;
    }

    /**
     * Sets type.
     *
     * @param SMSType $type
     *
     * @return $this
     */
    public function setType(SMSType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return SMSType
     */
    public function getType(): SMSType
    {
        return $this->type;
    }
}
