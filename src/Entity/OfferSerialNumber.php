<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * An offer serial number.
 *
 * @ORM\Entity()
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"offer_serial_number_read"}},
 *     "denormalization_context"={"groups"={"offer_serial_number_write"}},
 *     "filters"={
 *         "offer_serial_number.search",
 *     },
 * })
 */
class OfferSerialNumber
{
    use Traits\BlameableTrait;
    use Traits\TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime|null The date the item e.g. vehicle was purchased by the current owner.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="https://schema.org/purchaseDate")
     */
    protected $datePurchased;

    /**
     * @var \DateTime|null Date the content expires and is no longer useful or available.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="https://schema.org/expires")
     */
    protected $expires;

    /**
     * @var OfferListItem The offer list item which this serial number belongs to.
     *
     * @ORM\ManyToOne(targetEntity="OfferListItem")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @ApiProperty()
     */
    protected $offerListItem;

    /**
     * @var OrderItem|null The order item that holds this serial number.
     *
     * @ORM\ManyToOne(targetEntity="OrderItem", inversedBy="serialNumbers")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected $orderItem;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @ApiProperty()
     */
    protected $serialNumber;

    /**
     * Gets id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets Date Purchased.
     *
     * @return \DateTime|null
     */
    public function getDatePurchased(): ?\DateTime
    {
        return $this->datePurchased;
    }

    /**
     * Sets Date Purchased.
     *
     * @param \DateTime|null $datePurchased
     *
     * @return $this
     */
    public function setDatePurchased(?\DateTime $datePurchased)
    {
        $this->datePurchased = $datePurchased;

        return $this;
    }

    /**
     * Gets expires.
     *
     * @return \DateTime|null
     */
    public function getExpires(): ?\DateTime
    {
        return $this->expires;
    }

    /**
     * Sets expires.
     *
     * @param \DateTime|null $expires
     */
    public function setExpires(?\DateTime $expires): void
    {
        $this->expires = $expires;
    }

    /**
     * Gets offer list item.
     *
     * @return OfferListItem
     */
    public function getOfferListItem(): OfferListItem
    {
        return $this->offerListItem;
    }

    /**
     * Sets offer list item.
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
     * Gets order item.
     *
     * @return OrderItem|null
     */
    public function getOrderItem(): ?OrderItem
    {
        return $this->orderItem;
    }

    /**
     * Sets order item.
     *
     * @param OrderItem|null $orderItem
     *
     * @return $this
     */
    public function setOrderItem(?OrderItem $orderItem)
    {
        $this->orderItem = $orderItem;

        return $this;
    }

    /**
     * Gets serial number.
     *
     * @return string
     */
    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    /**
     * Sets serial number.
     *
     * @param string $serialNumber
     *
     * @return $this
     */
    public function setSerialNumber(string $serialNumber)
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }
}
