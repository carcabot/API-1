<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\PostalAddressType;
use Doctrine\ORM\Mapping as ORM;

/**
 * The mailing address.
 *
 * @see http://schema.org/PostalAddress
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/PostalAddress",
 *      attributes={
 *          "normalization_context"={"groups"={"postal_address_read"}},
 *          "denormalization_context"={"groups"={"postal_address_write", "lead_import_write"}},
 *      },
 *      collectionOperations={"post"}
 * )
 */
class PostalAddress
{
    use Traits\BlameableTrait;
    use Traits\TimestampableTrait;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string|null The country. For example, USA. You can also provide the two-letter [ISO 3166-1 alpha-2 country code](http://en.wikipedia.org/wiki/ISO_3166-1).
     *
     * @ORM\Column(type="string", length=2, nullable=true)
     * @ApiProperty(iri="http://schema.org/addressCountry")
     */
    protected $addressCountry;

    /**
     * @var string|null The locality. For example, Mountain View.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/addressLocality")
     */
    protected $addressLocality;

    /**
     * @var string|null The region. For example, CA.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/addressRegion")
     */
    protected $addressRegion;

    /**
     * @var string|null The building name. For example, Vertical Business Suites.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $buildingName;

    /**
     * @var string|null The floor number of a building. For example, 13A.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $floor;

    /**
     * @var string|null The house number.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $houseNumber;

    /**
     * @var string|null The postal code. For example, 94043.
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     * @ApiProperty(iri="http://schema.org/postalCode")
     */
    protected $postalCode;

    /**
     * @var string|null The street address. For example, 1600 Amphitheatre Pkwy.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/streetAddress")
     */
    protected $streetAddress;

    /**
     * @var string|null The textual content of this CreativeWork..
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/text")
     */
    protected $text;

    /**
     * @var PostalAddressType The postal address type.
     *
     * @ORM\Column(type="postal_address_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * @var string|null The unit number of the address.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $unitNumber;

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;
        }
    }

    public function __toString()
    {
        return \sprintf('%s_%s_%s_%s_%s_%s_%s_%s_%s_%s', $this->addressCountry, $this->addressLocality, $this->addressRegion, $this->buildingName, $this->floor, $this->houseNumber, $this->postalCode, $this->streetAddress, $this->type->getValue(), $this->unitNumber);
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
     * Sets addressCountry.
     *
     * @param string|null $addressCountry
     *
     * @return $this
     */
    public function setAddressCountry(?string $addressCountry)
    {
        $this->addressCountry = $addressCountry;

        return $this;
    }

    /**
     * Gets addressCountry.
     *
     * @return string|null
     */
    public function getAddressCountry(): ?string
    {
        return $this->addressCountry;
    }

    /**
     * Sets addressLocality.
     *
     * @param string|null $addressLocality
     *
     * @return $this
     */
    public function setAddressLocality(?string $addressLocality)
    {
        $this->addressLocality = $addressLocality;

        return $this;
    }

    /**
     * Gets addressLocality.
     *
     * @return string|null
     */
    public function getAddressLocality(): ?string
    {
        return $this->addressLocality;
    }

    /**
     * Sets addressRegion.
     *
     * @param string|null $addressRegion
     *
     * @return $this
     */
    public function setAddressRegion(?string $addressRegion)
    {
        $this->addressRegion = $addressRegion;

        return $this;
    }

    /**
     * Gets addressRegion.
     *
     * @return string|null
     */
    public function getAddressRegion(): ?string
    {
        return $this->addressRegion;
    }

    /**
     * Sets buildingName.
     *
     * @param string|null $buildingName
     *
     * @return $this
     */
    public function setBuildingName(?string $buildingName)
    {
        $this->buildingName = $buildingName;

        return $this;
    }

    /**
     * Gets buildingName.
     *
     * @return string|null
     */
    public function getBuildingName(): ?string
    {
        return $this->buildingName;
    }

    /**
     * Sets floor.
     *
     * @param string|null $floor
     *
     * @return $this
     */
    public function setFloor(?string $floor)
    {
        $this->floor = $floor;

        return $this;
    }

    /**
     * Gets floor.
     *
     * @return string|null
     */
    public function getFloor(): ?string
    {
        return $this->floor;
    }

    /**
     * Gets full address string.
     *
     * @return string|null
     */
    public function getFullAddress(): ?string
    {
        return $this->getText();
    }

    /**
     * Sets houseNumber.
     *
     * @param string|null $houseNumber
     *
     * @return $this
     */
    public function setHouseNumber(?string $houseNumber)
    {
        $this->houseNumber = $houseNumber;

        return $this;
    }

    /**
     * Gets houseNumber.
     *
     * @return string|null
     */
    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    /**
     * Sets postalCode.
     *
     * @param string|null $postalCode
     *
     * @return $this
     */
    public function setPostalCode(?string $postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Gets postalCode.
     *
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * Sets streetAddress.
     *
     * @param string|null $streetAddress
     *
     * @return $this
     */
    public function setStreetAddress(?string $streetAddress)
    {
        $this->streetAddress = $streetAddress;

        return $this;
    }

    /**
     * Gets streetAddress.
     *
     * @return string|null
     */
    public function getStreetAddress(): ?string
    {
        return $this->streetAddress;
    }

    /**
     * Sets text.
     *
     * @param string|null $text
     *
     * @return $this
     */
    public function setText(?string $text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Gets text.
     *
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Sets type.
     *
     * @param PostalAddressType $type
     *
     * @return $this
     */
    public function setType(PostalAddressType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return PostalAddressType
     */
    public function getType(): PostalAddressType
    {
        return $this->type;
    }

    /**
     * Sets unitNumber.
     *
     * @param string|null $unitNumber
     *
     * @return $this
     */
    public function setUnitNumber(?string $unitNumber)
    {
        $this->unitNumber = $unitNumber;

        return $this;
    }

    /**
     * Gets unitNumber.
     *
     * @return string|null
     */
    public function getUnitNumber(): ?string
    {
        return $this->unitNumber;
    }
}
