<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;

/**
 * Represents additional information about a relationship or property involving a phone number.
 *
 * @ORM\Entity
 */
class PhoneNumberRole
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var PhoneNumber
     *
     * @ORM\Column(type="phone_number", nullable=false)
     */
    protected $phoneNumber;

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;
        }
    }

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
     * Sets phoneNumber.
     *
     * @param PhoneNumber $phoneNumber
     *
     * @return $this
     */
    public function setPhoneNumber(PhoneNumber $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Gets phoneNumber.
     *
     * @return PhoneNumber
     */
    public function getPhoneNumber(): PhoneNumber
    {
        return $this->phoneNumber;
    }
}
