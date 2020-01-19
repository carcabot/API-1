<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="product_categories")
 */
class ProductCategory
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="code")
     */
    protected $code;

    /**
     * @var \DateTime|null The tariff created at
     *
     * @ODM\Field(type="date", name="_createdAt")
     */
    protected $createdAt;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="_createdBy")
     */
    protected $createdBy;

    /**
     * @var bool|null
     * @ODM\Field(type="bool", name="editable")
     */
    protected $editable;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="name")
     */
    protected $name;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="picture")
     */
    protected $picture;

    /**
     * @var \DateTime|null The tariff updated at
     *
     * @ODM\Field(type="date", name="_updatedAt")
     */
    protected $updatedAt;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="_updatedBy")
     */
    protected $updatedBy;

    /**
     * @var int|null The v
     *
     * @ODM\Field(type="int", name="__v")
     */
    protected $v;

    /**
     * Gets id.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Gets code.
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Gets created at.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * Gets created by.
     *
     * @return string|null
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * Gets editable.
     *
     * @return bool|null
     */
    public function getEditable(): ?bool
    {
        return $this->editable;
    }

    /**
     * Gets name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Gets picture.
     *
     * @return string|null
     */
    public function getPicture(): ?string
    {
        return $this->picture;
    }

    /**
     * Gets updated at.
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Gets updated by.
     *
     * @return string|null
     */
    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    /**
     * @return int|null
     */
    public function getV(): ?int
    {
        return $this->v;
    }
}
