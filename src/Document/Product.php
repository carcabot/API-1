<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="products")
 */
class Product
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var int|null Product amount.
     *
     * @ODM\Field(type="int", name="amount")
     */
    protected $amount;

    /**
     * @var \DateTime|null
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
     * @var string|null Product description.
     *
     * @ODM\Field(type="string", name="description")
     */
    protected $description;

    /**
     * @var bool|null
     * @ODM\Field(type="bool", name="editable")
     */
    protected $editable;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="image")
     */
    protected $image;

    /**
     * @var int|null
     *
     * @ODM\Field(type="int", name="point")
     */
    protected $point;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="catalog_id")
     */
    protected $productCatalog;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="category_id")
     */
    protected $productCategory;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="product_id")
     */
    protected $productNumber;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="partner_id")
     */
    protected $productPartner;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="product_type")
     */
    protected $productType;

    /**
     * @var int|null
     *
     * @ODM\Field(type="int", name="quantity")
     */
    protected $quantity;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="remarks")
     */
    protected $remarks;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="product_status")
     */
    protected $status;

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
     * @var \DateTime|null The tariff updated at
     *
     * @ODM\Field(type="date", name="start_date")
     */
    protected $validFrom;

    /**
     * @var \DateTime|null The tariff updated at
     *
     * @ODM\Field(type="date", name="end_date")
     */
    protected $validThrough;

    /**
     * @var string[]|null
     *
     * @ODM\Field(type="collection", name="voucher")
     */
    protected $vouchers;

    public function __construct()
    {
        $this->vouchers = [];
    }

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
     * Gets amount.
     *
     * @return int|null
     */
    public function getAmount(): ?int
    {
        return $this->amount;
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
     * Gets description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
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
     * Gets image.
     *
     * @return string|null
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Gets point.
     *
     * @return int|null
     */
    public function getPoint(): ?int
    {
        return $this->point;
    }

    /**
     * Gets product catalog.
     *
     * @return string|null
     */
    public function getProductCatalog(): ?string
    {
        return $this->productCatalog;
    }

    /**
     * Gets product category.
     *
     * @return string|null
     */
    public function getProductCategory(): ?string
    {
        return $this->productCategory;
    }

    /**
     * Gets product number.
     *
     * @return string|null
     */
    public function getProductNumber(): ?string
    {
        return $this->productNumber;
    }

    /**
     * Gets product partner.
     *
     * @return string|null
     */
    public function getProductPartner(): ?string
    {
        return $this->productPartner;
    }

    /**
     * Gets product type.
     *
     * @return string|null
     */
    public function getProductType(): ?string
    {
        return $this->productType;
    }

    /**
     * Gets quantity.
     *
     * @return int|null
     */
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    /**
     * Gets remarks.
     *
     * @return string|null
     */
    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    /**
     * Gets status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
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

    /**
     * Gets valid from.
     *
     * @return \DateTime|null
     */
    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    /**
     * Gets valid through.
     *
     * @return \DateTime|null
     */
    public function getValidThrough(): ?\DateTime
    {
        return $this->validThrough;
    }

    /**
     * Gets vouchers.
     *
     * @return string[]|null
     */
    public function getVouchers()
    {
        return $this->vouchers;
    }
}
