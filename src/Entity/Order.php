<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\OrderVoucherController;
use App\Enum\OrderStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An order is a confirmation of a transaction (a receipt), which can contain multiple line items, each represented by an Offer that has been accepted by the customer.
 *
 * @ORM\Entity(repositoryClass="App\Repository\OrderRepository")
 * @ApiResource(iri="http://schema.org/Order", attributes={
 *     "normalization_context"={"groups"={"order_read"}},
 *     "denormalization_context"={"groups"={"order_write"}},
 *     "filters"={
 *         "order.date",
 *         "order.order",
 *         "order.search",
 *     },
 * },
 * itemOperations={
 *     "delete",
 *     "get",
 *     "get_redemption_voucher"={
 *         "method"="GET",
 *         "path"="/orders/{id}/redemption_voucher.{_format}",
 *         "controller"=OrderVoucherController::class
 *     },
 *     "put",
 * }
 * )
 */
class Order
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
     * @var CustomerAccount Party placing the order or paying the invoice.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="https://schema.org/customer")
     */
    protected $customer;

    /**
     * @var Collection<OrderItem> An order item is a line of an order. It includes the quantity and shipping details of a bought offer.
     *
     * @ORM\OneToMany(targetEntity="OrderItem", cascade={"persist"}, mappedBy="order")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $items;

    /**
     * @var Contract The object upon which the action is carried out, whose state is kept intact or changed. Also known as the semantic roles patient, affected or undergoer (which change their state) or theme (which doesn't). e.g. John read a book.
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/object")
     */
    protected $object;

    /**
     * @var \DateTime|null Date order was placed.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/orderDate")
     */
    protected $orderDate;

    /**
     * @var string The identifier of the transaction.
     *
     * @ORM\Column(type="string", nullable=false)
     * @ApiProperty(iri="http://schema.org/orderNumber")
     */
    protected $orderNumber;

    /**
     * @var OrderStatus The current status of the order.
     *
     * @ORM\Column(type="order_status_enum", nullable=false)
     * @ApiProperty(iri="http://schema.org/orderStatus")
     */
    protected $orderStatus;

    /**
     * @var PriceSpecification The total points of the order.
     *
     * @ORM\Embedded(class="PriceSpecification")
     * @ApiProperty()
     */
    protected $totalPrice;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->totalPrice = new PriceSpecification();
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
     * Sets customer.
     *
     * @param CustomerAccount $customer
     *
     * @return $this
     */
    public function setCustomer(CustomerAccount $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Gets customer.
     *
     * @return CustomerAccount
     */
    public function getCustomer(): CustomerAccount
    {
        return $this->customer;
    }

    /**
     * Adds items.
     *
     * @param OrderItem $items
     *
     * @return $this
     */
    public function addItems(OrderItem $items)
    {
        $this->items[] = $items;

        return $this;
    }

    /**
     * Removes items.
     *
     * @param OrderItem $items
     *
     * @return $this
     */
    public function removeItems(OrderItem $items)
    {
        $this->items->removeElement($items);

        return $this;
    }

    /**
     * Gets items.
     *
     * @return OrderItem[]
     */
    public function getItems(): array
    {
        return $this->items->getValues();
    }

    /**
     * Gets object.
     *
     * @return Contract
     */
    public function getObject(): Contract
    {
        return $this->object;
    }

    /**
     * Sets object.
     *
     * @param Contract $object
     *
     * @return $this
     */
    public function setObject(Contract $object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Sets orderDate.
     *
     * @param \DateTime|null $orderDate
     *
     * @return $this
     */
    public function setOrderDate(?\DateTime $orderDate)
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    /**
     * Gets orderDate.
     *
     * @return \DateTime|null
     */
    public function getOrderDate(): ?\DateTime
    {
        return $this->orderDate;
    }

    /**
     * Sets orderNumber.
     *
     * @param string $orderNumber
     *
     * @return $this
     */
    public function setOrderNumber(string $orderNumber)
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    /**
     * Gets orderNumber.
     *
     * @return string
     */
    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    /**
     * Sets orderStatus.
     *
     * @param OrderStatus $orderStatus
     *
     * @return $this
     */
    public function setOrderStatus(OrderStatus $orderStatus)
    {
        $this->orderStatus = $orderStatus;

        return $this;
    }

    /**
     * Gets orderStatus.
     *
     * @return OrderStatus
     */
    public function getOrderStatus(): OrderStatus
    {
        return $this->orderStatus;
    }

    /**
     * Gets total points.
     *
     * @return PriceSpecification
     */
    public function getTotalPrice(): PriceSpecification
    {
        return $this->totalPrice;
    }

    /**
     * Sets total points.
     *
     * @param PriceSpecification $totalPrice
     *
     * @return $this
     */
    public function setTotalPrice(PriceSpecification $totalPrice)
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }
}
