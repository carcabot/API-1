<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="redemption_orders")
 */
class RedemptionOrder
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var \DateTime|null The tariff created at
     *
     * @ODM\Field(type="date", name="_createdAt")
     */
    protected $createdAt;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="contract_id")
     */
    protected $contract;

    /**
     * @var string[]
     *
     * @ODM\Field(type="collection", name="items")
     */
    protected $items;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="redemption_order")
     */
    protected $orderNumber;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="product_type")
     */
    protected $productType;

    /**
     * @var int
     *
     * @ODM\Field(type="int", name="points_redeemed")
     */
    protected $points;

    /**
     * @var int|null The v
     *
     * @ODM\Field(type="int", name="__v")
     */
    protected $v;

    public function __construct()
    {
        $this->items = [];
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
     * Gets created at.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * Gets contract.
     *
     * @return string|null
     */
    public function getContract(): ?string
    {
        return $this->contract;
    }

    /**
     * Gets items.
     *
     * @return string[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Gets order number.
     *
     * @return string|null
     */
    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
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
     * Gets points.
     *
     * @return int
     */
    public function getPoints(): int
    {
        return $this->points;
    }

    /**
     * Gets v.
     *
     * @return int|null
     */
    public function getV(): ?int
    {
        return $this->v;
    }
}
