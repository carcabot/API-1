<?php

declare(strict_types=1);

namespace App\Document\Reports;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class Address
{
    /**
     * @var string|null the postal code
     *
     * @ODM\Field(type="string")
     */
    protected $postalCode;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $unitNumber;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $floor;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $buildingNumber;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $buildingName;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $street;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $city;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $country;

    /**
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * @param string|null $postalCode
     */
    public function setPostalCode(?string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    /**
     * @return string|null
     */
    public function getUnitNumber(): ?string
    {
        return $this->unitNumber;
    }

    /**
     * @param string|null $unitNumber
     */
    public function setUnitNumber(?string $unitNumber): void
    {
        $this->unitNumber = $unitNumber;
    }

    /**
     * @return string|null
     */
    public function getFloor(): ?string
    {
        return $this->floor;
    }

    /**
     * @param string|null $floor
     */
    public function setFloor(?string $floor): void
    {
        $this->floor = $floor;
    }

    /**
     * @return string|null
     */
    public function getBuildingNumber(): ?string
    {
        return $this->buildingNumber;
    }

    /**
     * @param string|null $buildingNumber
     */
    public function setBuildingNumber(?string $buildingNumber): void
    {
        $this->buildingNumber = $buildingNumber;
    }

    /**
     * @return string|null
     */
    public function getBuildingName(): ?string
    {
        return $this->buildingName;
    }

    /**
     * @param string|null $buildingName
     */
    public function setBuildingName(?string $buildingName): void
    {
        $this->buildingName = $buildingName;
    }

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @param string|null $street
     */
    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     */
    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     */
    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }
}
