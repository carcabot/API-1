<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 3/1/19
 * Time: 12:40 PM.
 */

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class OldIdentification
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string|null The description
     *
     * @ODM\Field(type="string", name="desc")
     */
    protected $description;

    /**
     * @var string|null The nric fin.
     *
     * @ODM\Field(type="string", name="nric_fin")
     */
    protected $nricFin;

    /**
     * @var string|null The uen.
     *
     * @ODM\Field(type="string", name="uen")
     */
    protected $uen;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date", name="valid_from")
     */
    protected $validFrom;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date", name="valid_to")
     */
    protected $validThrough;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getNricFin(): ?string
    {
        return $this->nricFin;
    }

    /**
     * @return string|null
     */
    public function getUen(): ?string
    {
        return $this->uen;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidThrough(): ?\DateTime
    {
        return $this->validThrough;
    }
}
