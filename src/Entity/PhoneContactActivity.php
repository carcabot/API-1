<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\CallDirection;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;

/**
 * An phone contact activity carried out on an item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"phone_contact_activity_read"}},
 *     "denormalization_context"={"groups"={"phone_contact_activity_write"}},
 * })
 */
class PhoneContactActivity extends Activity
{
    /**
     * @var CallDirection The call direction of the activity.
     *
     * @ORM\Column(type="call_direction_enum", nullable=false)
     * @ApiProperty()
     */
    protected $direction;

    /**
     * @var PhoneNumber The recipient telephone number.
     *
     * @ORM\Column(type="phone_number", nullable=false)
     * @ApiProperty()
     */
    protected $recipientTelephoneNumber;

    /**
     * Sets direction.
     *
     * @param CallDirection $direction
     *
     * @return $this
     */
    public function setDirection(CallDirection $direction)
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * Gets direction.
     *
     * @return CallDirection
     */
    public function getDirection(): CallDirection
    {
        return $this->direction;
    }

    /**
     * Sets recipientTelephoneNumber.
     *
     * @param PhoneNumber $recipientTelephoneNumber
     *
     * @return $this
     */
    public function setRecipientTelephoneNumber(PhoneNumber $recipientTelephoneNumber)
    {
        $this->recipientTelephoneNumber = $recipientTelephoneNumber;

        return $this;
    }

    /**
     * Gets recipientTelephoneNumber.
     *
     * @return PhoneNumber
     */
    public function getRecipientTelephoneNumber(): PhoneNumber
    {
        return $this->recipientTelephoneNumber;
    }
}
