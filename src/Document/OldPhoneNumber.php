<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 3/1/19
 * Time: 12:21 PM.
 */

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class OldPhoneNumber
{
    /**
     * @var string|null The country code
     *
     * @ODM\Field(type="string", name="country_code")
     */
    protected $countryCode;

    /**
     * @var int|null The number
     *
     * @ODM\Field(type="int", name="number")
     */
    protected $number;

    /**
     * @return string|null
     */
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    /**
     * @return int|null
     */
    public function getNumber(): ?int
    {
        return $this->number;
    }
}
