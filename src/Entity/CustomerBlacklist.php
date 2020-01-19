<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"customer_blacklist_read"}},
 *     "denormalization_context"={"groups"={"customer_blacklist_write"}},
 * })
 */
class CustomerBlacklist
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
     * @var string Determines whether to add or remove from blacklist.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty()
     */
    protected $action;

    /**
     * @var CustomerAccount|null Party placing the order or paying the invoice.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/customer")
     */
    protected $customer;

    /**
     * @var string The identifier property represents any kind of identifier for any kind of Thing, such as ISBNs, GTIN codes, UUIDs etc. Schema.org provides dedicated properties for representing many of these, either as textual strings or as URL (URI) links. See background notes for more details.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/identification")
     */
    protected $identification;

    /**
     * @var string The name of the item.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var string|null The reason for the blacklist/removal from blacklist.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $reason;

    /**
     * @var string|null Additional remarks.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $remarks;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction(string $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Sets customer.
     *
     * @param CustomerAccount|null $customer
     *
     * @return $this
     */
    public function setCustomer(?CustomerAccount $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Gets customer.
     *
     * @return CustomerAccount|null
     */
    public function getCustomer(): ?CustomerAccount
    {
        return $this->customer;
    }

    /**
     * @param string $identification
     *
     * @return $this
     */
    public function setIdentification(string $identification)
    {
        $this->identification = $identification;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentification(): string
    {
        return $this->identification;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string|null $reason
     *
     * @return $this
     */
    public function setReason(?string $reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @param string|null $remarks
     *
     * @return $this
     */
    public function setRemarks(?string $remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRemarks(): ?string
    {
        return $this->remarks;
    }
}
