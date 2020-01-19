<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 19/1/19
 * Time: 10:18 AM.
 */

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class OldLeadAddress
{
    /**
     * @var string|null The address type
     *
     * @ODM\Field(type="string", name="address_type")
     */
    protected $addressType;

    /**
     * @var string|null The country
     *
     * @ODM\Field(type="string", name="country")
     */
    protected $country;

    /**
     * @var string|null The city
     *
     * @ODM\Field(type="string", name="city")
     */
    protected $city;

    /**
     * @var string|null The building name
     *
     * @ODM\Field(type="string", name="building_name")
     */
    protected $buildingName;

    /**
     * @var string|null The floor
     *
     * @ODM\Field(type="string", name="floor")
     */
    protected $floor;

    /**
     * @var string|null The house number
     *
     * @ODM\Field(type="string", name="house_no")
     */
    protected $houseNumber;

    /**
     * @var int|null The postal code
     *
     * @ODM\Field(type="int", name="post_code")
     */
    protected $postCode;

    /**
     * @var string|null The region
     *
     * @ODM\Field(type="string", name="region")
     */
    protected $region;

    /**
     * @var string|null The street
     *
     * @ODM\Field(type="string", name="street")
     */
    protected $street;

    /**
     * @var string|null The unit number
     *
     * @ODM\Field(type="string", name="unit_no")
     */
    protected $unitNumber;

    /**
     * @return string|null
     */
    public function getAddressType(): ?string
    {
        return $this->addressType;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @return string|null
     */
    public function getBuildingName(): ?string
    {
        return $this->buildingName;
    }

    /**
     * @return string|null
     */
    public function getFloor(): ?string
    {
        return $this->floor;
    }

    /**
     * @return string|null
     */
    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    /**
     * @return int|null
     */
    public function getPostCode(): ?int
    {
        return $this->postCode;
    }

    /**
     * @return string|null
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @return string|null
     */
    public function getUnitNumber(): ?string
    {
        return $this->unitNumber;
    }
}
