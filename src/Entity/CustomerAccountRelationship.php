<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\CustomerRelationshipType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The relationship between two customers.
 *
 * @ORM\Entity(repositoryClass="App\Repository\CustomerAccountRelationshipRepository")
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"customer_account_relationship_read"}},
 *     "denormalization_context"={"groups"={"customer_account_relationship_write"}},
 *     "filters"={
 *         "customer_account_relationship.date",
 *         "customer_account_relationship.exists",
 *         "customer_account_relationship.order",
 *         "customer_account_relationship.search",
 *     },
 * })
 */
class CustomerAccountRelationship
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
     * @var Collection<Contract> A contract.
     *
     * @ORM\ManyToMany(targetEntity="Contract", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="customer_account_relationship_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="contract_id", onDelete="CASCADE")},
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $contracts;

    /**
     * @var bool|null Determines whether the relationship is Manage SSP.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $customerPortalEnabled;

    /**
     * @var CustomerAccount The owning side of the relationship.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $from;

    /**
     * @var CustomerAccount The inverse side of the relationship.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $to;

    /**
     * @var CustomerRelationshipType The type of relationship.
     *
     * @ORM\Column(type="customer_relationship_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * @var \DateTime|null The date when the item becomes valid.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validFrom")
     */
    protected $validFrom;

    /**
     * @var \DateTime|null The date after when the item is not valid. For example the end of an offer, salary period, or a period of opening hours.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validThrough")
     */
    protected $validThrough;

    public function __construct()
    {
        $this->contracts = new ArrayCollection();
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
     * Adds contract.
     *
     * @param Contract $contract
     *
     * @return $this
     */
    public function addContract(Contract $contract)
    {
        $this->contracts[] = $contract;

        return $this;
    }

    /**
     * Removes contract.
     *
     * @param Contract $contract
     *
     * @return $this
     */
    public function removeContract(Contract $contract)
    {
        $this->contracts->removeElement($contract);

        return $this;
    }

    /**
     * Gets contracts.
     *
     * @return Contract[]
     */
    public function getContracts(): array
    {
        return $this->contracts->getValues();
    }

    /**
     * @return bool|null
     */
    public function getCustomerPortalEnabled(): ?bool
    {
        return $this->customerPortalEnabled;
    }

    /**
     * @param bool|null $customerPortalEnabled
     */
    public function setCustomerPortalEnabled(?bool $customerPortalEnabled): void
    {
        $this->customerPortalEnabled = $customerPortalEnabled;
    }

    /**
     * Sets from.
     *
     * @param CustomerAccount $from
     *
     * @return $this
     */
    public function setFrom(CustomerAccount $from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Gets from.
     *
     * @return CustomerAccount
     */
    public function getFrom(): CustomerAccount
    {
        return $this->from;
    }

    /**
     * Sets to.
     *
     * @param CustomerAccount $to
     *
     * @return $this
     */
    public function setTo(CustomerAccount $to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Gets to.
     *
     * @return CustomerAccount
     */
    public function getTo(): CustomerAccount
    {
        return $this->to;
    }

    /**
     * Sets type.
     *
     * @param CustomerRelationshipType $type
     *
     * @return $this
     */
    public function setType(CustomerRelationshipType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return CustomerRelationshipType
     */
    public function getType(): CustomerRelationshipType
    {
        return $this->type;
    }

    /**
     * Sets validFrom.
     *
     * @param \DateTime|null $validFrom
     *
     * @return $this
     */
    public function setValidFrom(?\DateTime $validFrom)
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    /**
     * Gets validFrom.
     *
     * @return \DateTime|null
     */
    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    /**
     * Sets validThrough.
     *
     * @param \DateTime|null $validThrough
     *
     * @return $this
     */
    public function setValidThrough(?\DateTime $validThrough)
    {
        $this->validThrough = $validThrough;

        return $this;
    }

    /**
     * Gets validThrough.
     *
     * @return \DateTime|null
     */
    public function getValidThrough(): ?\DateTime
    {
        return $this->validThrough;
    }

    /**
     * Determines if the relationship is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $now = new \DateTime();

        if (null === $this->getValidFrom() || $now >= $this->getValidFrom()) {
            if (null === $this->getValidThrough() || $now <= $this->getValidThrough()) {
                return true;
            }
        }

        return false;
    }
}
