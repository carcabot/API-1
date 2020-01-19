<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A quotation postal address.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"quotation_postal_address_read"}},
 *     "denormalization_context"={"groups"={"quotation_postal_address_write"}},
 * })
 */
class QuotationPostalAddress
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
     * @var Collection<PostalAddress> The mailing address.
     *
     * @ORM\ManyToMany(targetEntity="PostalAddress", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="quotation_postal_address_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="address_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $addresses;

    /**
     * @var string|null The EBS account number.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     * @ApiProperty()
     */
    protected $ebsAccountNumber;

    /**
     * @var string|null The MSSL account number.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     * @ApiProperty()
     */
    protected $msslAccountNumber;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
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
     * Adds address.
     *
     * @param PostalAddress $address
     *
     * @return $this
     */
    public function addAddress(PostalAddress $address)
    {
        $this->addresses[] = $address;

        return $this;
    }

    /**
     * Removes address.
     *
     * @param PostalAddress $address
     *
     * @return $this
     */
    public function removeAddress(PostalAddress $address)
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    /**
     * Gets addresses.
     *
     * @return PostalAddress[]
     */
    public function getAddresses(): array
    {
        return $this->addresses->getValues();
    }

    /**
     * Sets ebsAccountNumber.
     *
     * @param string|null $ebsAccountNumber
     *
     * @return $this
     */
    public function setEbsAccountNumber(?string $ebsAccountNumber)
    {
        $this->ebsAccountNumber = $ebsAccountNumber;

        return $this;
    }

    /**
     * Gets ebsAccountNumber.
     *
     * @return string|null
     */
    public function getEbsAccountNumber(): ?string
    {
        return $this->ebsAccountNumber;
    }

    /**
     * Sets msslAccountNumber.
     *
     * @param string|null $msslAccountNumber
     *
     * @return $this
     */
    public function setMsslAccountNumber(?string $msslAccountNumber)
    {
        $this->msslAccountNumber = $msslAccountNumber;

        return $this;
    }

    /**
     * Gets msslAccountNumber.
     *
     * @return string|null
     */
    public function getMsslAccountNumber(): ?string
    {
        return $this->msslAccountNumber;
    }
}
