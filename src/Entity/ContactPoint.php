<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;

/**
 * A contact point—for example, a Customer Complaints department.
 *
 * @see http://schema.org/ContactPoint
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/ContactPoint",
 *      attributes={
 *          "normalization_context"={"groups"={"contact_point_read"}},
 *          "denormalization_context"={"groups"={"contact_point_write", "lead_import_write"}},
 *      },
 *      collectionOperations={"post"}
 * )
 */
class ContactPoint
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
     * @var string|null A person or organization can have different contact points, for different purposes. For example, a sales contact point, a PR contact point and so on. This property is used to specify the kind of contact point.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/contactType")
     */
    protected $contactType;

    /**
     * @var string[] Email address.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty(iri="http://schema.org/email")
     */
    protected $emails;

    /**
     * @var Collection<PhoneNumberRole> The fax number.
     *
     * @ORM\ManyToMany(targetEntity="PhoneNumberRole", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     name="contact_points_fax_phone_number_roles",
     *     joinColumns={@ORM\JoinColumn(name="contact_point_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="fax_number_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty(iri="http://schema.org/faxNumber")
     */
    protected $faxNumbers;

    /**
     * @var Collection<PhoneNumberRole> The mobile phone number.
     *
     * @ORM\ManyToMany(targetEntity="PhoneNumberRole", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     name="contact_points_mobile_phone_number_roles",
     *     joinColumns={@ORM\JoinColumn(name="contact_point_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="mobile_phone_number_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $mobilePhoneNumbers;

    /**
     * @var Collection<PhoneNumberRole> The telephone number.
     *
     * @ORM\ManyToMany(targetEntity="PhoneNumberRole", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     name="contact_points_telephone_number_roles",
     *     joinColumns={@ORM\JoinColumn(name="contact_point_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="telephone_number_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty(iri="http://schema.org/telephone")
     */
    protected $telephoneNumbers;

    public function __construct()
    {
        $this->emails = [];
        $this->faxNumbers = new ArrayCollection();
        $this->mobilePhoneNumbers = new ArrayCollection();
        $this->telephoneNumbers = new ArrayCollection();
    }

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;

            $faxNumbers = new ArrayCollection();
            foreach ($this->faxNumbers as $faxNumber) {
                $faxNumbers[] = clone $faxNumber;
            }
            $this->faxNumbers = $faxNumbers;

            $mobilePhoneNumbers = new ArrayCollection();
            foreach ($this->mobilePhoneNumbers as $mobilePhoneNumber) {
                $mobilePhoneNumbers[] = clone $mobilePhoneNumber;
            }
            $this->mobilePhoneNumbers = $mobilePhoneNumbers;

            $telephoneNumbers = new ArrayCollection();
            foreach ($this->telephoneNumbers as $telephoneNumber) {
                $telephoneNumbers[] = clone $telephoneNumber;
            }
            $this->telephoneNumbers = $telephoneNumbers;
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
     * Sets contactType.
     *
     * @param string|null $contactType
     *
     * @return $this
     */
    public function setContactType(?string $contactType)
    {
        $this->contactType = $contactType;

        return $this;
    }

    /**
     * Gets contactType.
     *
     * @return string|null
     */
    public function getContactType(): ?string
    {
        return $this->contactType;
    }

    /**
     * Adds email.
     *
     * @param string $email
     *
     * @return $this
     */
    public function addEmail(string $email)
    {
        $this->emails[] = $email;

        return $this;
    }

    /**
     * Removes email.
     *
     * @param string $email
     *
     * @return $this
     */
    public function removeEmail(string $email)
    {
        if (false !== ($key = \array_search($email, $this->emails, true))) {
            \array_splice($this->emails, $key, 1);
        }

        return $this;
    }

    /**
     * Replaces emails.
     *
     * @param array $emails
     *
     * @return $this
     */
    public function replaceEmails(array $emails)
    {
        $this->emails = $emails;

        return $this;
    }

    /**
     * Gets emails.
     *
     * @return string[]
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * Adds faxNumber.
     *
     * @param PhoneNumber $faxNumber
     *
     * @return $this
     */
    public function addFaxNumber(PhoneNumber $faxNumber)
    {
        $phoneNumberRole = new PhoneNumberRole();
        $phoneNumberRole->setPhoneNumber($faxNumber);

        $this->faxNumbers[] = $phoneNumberRole;

        return $this;
    }

    /**
     * Removes faxNumber.
     *
     * @param PhoneNumber $faxNumber
     *
     * @return $this
     */
    public function removeFaxNumber(PhoneNumber $faxNumber)
    {
        $phoneNumberRole = $this->faxNumbers->filter(function ($phoneNumberRole) use ($faxNumber) {
            return $faxNumber === $phoneNumberRole->getPhoneNumber();
        })->first();

        $this->faxNumbers->removeElement($phoneNumberRole);

        return $this;
    }

    /**
     * Gets faxNumbers.
     *
     * @return PhoneNumber[]
     */
    public function getFaxNumbers(): array
    {
        return $this->faxNumbers->map(function ($phoneNumberRole) {
            return $phoneNumberRole->getPhoneNumber();
        })->getValues();
    }

    /**
     * Clears faxNumbers.
     *
     * @return $this
     */
    public function clearFaxNumbers()
    {
        $this->faxNumbers = new ArrayCollection();

        return $this;
    }

    /**
     * Adds mobilePhoneNumber.
     *
     * @param PhoneNumber $mobilePhoneNumber
     *
     * @return $this
     */
    public function addMobilePhoneNumber(PhoneNumber $mobilePhoneNumber)
    {
        $phoneNumberRole = new PhoneNumberRole();
        $phoneNumberRole->setPhoneNumber($mobilePhoneNumber);

        $this->mobilePhoneNumbers[] = $phoneNumberRole;

        return $this;
    }

    /**
     * Removes mobilePhoneNumber.
     *
     * @param PhoneNumber $mobilePhoneNumber
     *
     * @return $this
     */
    public function removeMobilePhoneNumber(PhoneNumber $mobilePhoneNumber)
    {
        $phoneNumberRole = $this->mobilePhoneNumbers->filter(function ($phoneNumberRole) use ($mobilePhoneNumber) {
            return $mobilePhoneNumber === $phoneNumberRole->getPhoneNumber();
        })->first();

        $this->mobilePhoneNumbers->removeElement($phoneNumberRole);

        return $this;
    }

    /**
     * Gets mobilePhoneNumbers.
     *
     * @return PhoneNumber[]
     */
    public function getMobilePhoneNumbers(): array
    {
        return $this->mobilePhoneNumbers->map(function ($phoneNumberRole) {
            return $phoneNumberRole->getPhoneNumber();
        })->getValues();
    }

    /**
     * Clears telephoneNumbers.
     *
     * @return $this
     */
    public function clearMobilePhoneNumbers()
    {
        $this->mobilePhoneNumbers = new ArrayCollection();

        return $this;
    }

    /**
     * Adds telephoneNumber.
     *
     * @param PhoneNumber $telephoneNumber
     *
     * @return $this
     */
    public function addTelephoneNumber(PhoneNumber $telephoneNumber)
    {
        $phoneNumberRole = new PhoneNumberRole();
        $phoneNumberRole->setPhoneNumber($telephoneNumber);

        $this->telephoneNumbers[] = $phoneNumberRole;

        return $this;
    }

    /**
     * Removes telephoneNumber.
     *
     * @param PhoneNumber $telephoneNumber
     *
     * @return $this
     */
    public function removeTelephoneNumber(PhoneNumber $telephoneNumber)
    {
        $phoneNumberRole = $this->telephoneNumbers->filter(function ($phoneNumberRole) use ($telephoneNumber) {
            return $telephoneNumber === $phoneNumberRole->getPhoneNumber();
        })->first();

        $this->telephoneNumbers->removeElement($phoneNumberRole);

        return $this;
    }

    /**
     * Gets telephoneNumbers.
     *
     * @return PhoneNumber[]
     */
    public function getTelephoneNumbers(): array
    {
        return $this->telephoneNumbers->map(function ($phoneNumberRole) {
            return $phoneNumberRole->getPhoneNumber();
        })->getValues();
    }

    /**
     * Clears telephoneNumbers.
     *
     * @return $this
     */
    public function clearTelephoneNumbers()
    {
        $this->telephoneNumbers = new ArrayCollection();

        return $this;
    }
}
