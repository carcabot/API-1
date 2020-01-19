<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An order item is a line of an order. It includes the quantity and shipping details of a bought offer.
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/OrderItem", attributes={
 *     "normalization_context"={"groups"={"order_item_read"}},
 *     "denormalization_context"={"groups"={"order_item_write"}},
 * })
 */
class OrderItem
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
     * @var OfferListItem The offer list item.
     *
     * @ORM\ManyToOne(targetEntity="OfferListItem")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $offerListItem;

    /**
     * @var Order An order is a confirmation of a transaction (a receipt), which can contain multiple line items, each represented by an Offer that has been accepted by the customer.
     *
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="items")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $order;

    /**
     * @var string|null The identifier of the order item.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty(iri="http://schema.org/orderItemNumber")
     */
    protected $orderItemNumber;

    /**
     * @var string|null The current status of the order item.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty(iri="http://schema.org/orderItemStatus")
     */
    protected $orderItemStatus;

    /**
     * @var QuantitativeValue The number of the item ordered. If the property is not set, assume the quantity is one.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty(iri="http://schema.org/orderQuantity")
     */
    protected $orderQuantity;

    /**
     * @var PriceSpecification The unit price of the offer.
     *
     * @ORM\Embedded(class="PriceSpecification")
     * @ApiProperty()
     */
    protected $unitPrice;

    /**
     * @var Collection<OfferSerialNumber> Serial numbers purchased.
     *
     * @ORM\OneToMany(targetEntity="OfferSerialNumber", mappedBy="orderItem")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $serialNumbers;

    public function __construct()
    {
        $this->orderQuantity = new QuantitativeValue();
        $this->unitPrice = new PriceSpecification();
        $this->serialNumbers = new ArrayCollection();
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
     * Sets offer.
     *
     * @param OfferListItem $offerListItem
     *
     * @return $this
     */
    public function setOfferListItem(OfferListItem $offerListItem)
    {
        $this->offerListItem = $offerListItem;

        return $this;
    }

    /**
     * Gets offer.
     *
     * @return OfferListItem
     */
    public function getOfferListItem(): OfferListItem
    {
        return $this->offerListItem;
    }

    /**
     * Sets order.
     *
     * @param Order $order
     *
     * @return $this
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Gets order.
     *
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * Sets orderItemNumber.
     *
     * @param string|null $orderItemNumber
     *
     * @return $this
     */
    public function setOrderItemNumber(?string $orderItemNumber)
    {
        $this->orderItemNumber = $orderItemNumber;

        return $this;
    }

    /**
     * Gets orderItemNumber.
     *
     * @return string|null
     */
    public function getOrderItemNumber(): ?string
    {
        return $this->orderItemNumber;
    }

    /**
     * Sets orderItemStatus.
     *
     * @param string|null $orderItemStatus
     *
     * @return $this
     */
    public function setOrderItemStatus(?string $orderItemStatus)
    {
        $this->orderItemStatus = $orderItemStatus;

        return $this;
    }

    /**
     * Gets orderItemStatus.
     *
     * @return string|null
     */
    public function getOrderItemStatus(): ?string
    {
        return $this->orderItemStatus;
    }

    /**
     * Sets orderQuantity.
     *
     * @param QuantitativeValue $orderQuantity
     *
     * @return $this
     */
    public function setOrderQuantity(QuantitativeValue $orderQuantity)
    {
        $this->orderQuantity = $orderQuantity;

        return $this;
    }

    /**
     * Gets orderQuantity.
     *
     * @return QuantitativeValue
     */
    public function getOrderQuantity(): QuantitativeValue
    {
        return $this->orderQuantity;
    }

    /**
     * Sets unitPrice.
     *
     * @param PriceSpecification $unitPrice
     *
     * @return $this
     */
    public function setUnitPrice(PriceSpecification $unitPrice)
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * Gets unitPrice.
     *
     * @return PriceSpecification
     */
    public function getUnitPrice(): PriceSpecification
    {
        return $this->unitPrice;
    }

    /**
     * Adds serial number.
     *
     * @param OfferSerialNumber $serialNumber
     */
    public function addSerialNumber(OfferSerialNumber $serialNumber)
    {
        $this->serialNumbers[] = $serialNumber;
    }

    /**
     * Removes serial number.
     *
     * @param OfferSerialNumber $serialNumber
     */
    public function removeSerialNumber(OfferSerialNumber $serialNumber)
    {
        $this->serialNumbers->removeElement($serialNumber);
    }

    /**
     * @return OfferSerialNumber[]
     */
    public function getSerialNumbers(): array
    {
        return $this->serialNumbers->getValues();
    }
}
