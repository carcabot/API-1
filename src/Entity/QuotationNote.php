<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A quotation note.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"quotation_note_read"}},
 *     "denormalization_context"={"groups"={"quotation_note_write"}},
 * })
 */
class QuotationNote extends Note
{
    /**
     * @var array The contract terms of the quotation
     *
     * @ORM\Column(type="json", nullable=false)
     * @ApiProperty()
     */
    protected $terms;

    /**
     * @var Collection<PriceConfiguration> The price plan suggestions of the quotation.
     *
     * @ORM\ManyToMany(targetEntity="PriceConfiguration", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="quotation_note_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="price_configuration_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $offers;

    /**
     * @var string|null indicate The retailer on the action
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $retailer;

    public function __construct()
    {
        parent::__construct();
        $this->terms = [];
        $this->offers = new ArrayCollection();
    }

    /**
     * Adds term.
     *
     * @param QuantitativeValue $term
     *
     * @return $this
     */
    public function addTerm(QuantitativeValue $term)
    {
        $this->terms[] = $term;

        return $this;
    }

    /**
     * Removes term.
     *
     * @param array $term
     *
     * @return $this
     */
    public function removeTerm(array $term)
    {
        if (false !== ($key = \array_search($term, $this->terms, true))) {
            \array_splice($this->terms, $key, 1);
        }

        return $this;
    }

    /**
     * Gets terms.
     *
     * @return array
     */
    public function getTerms(): array
    {
        return $this->terms;
    }

    /**
     * Adds offer.
     *
     * @param PriceConfiguration $offer
     *
     * @return $this
     */
    public function addOffer(PriceConfiguration $offer)
    {
        $this->offers[] = $offer;

        return $this;
    }

    /**
     * Removes offer.
     *
     * @param PriceConfiguration $offer
     *
     * @return $this
     */
    public function removeOffer(PriceConfiguration $offer)
    {
        $this->offers->removeElement($offer);

        return $this;
    }

    /**
     * Get offers.
     *
     * @return array
     */
    public function getOffers(): array
    {
        return $this->offers->getValues();
    }

    /**
     * @return string|null
     */
    public function getRetailer(): ?string
    {
        return $this->retailer;
    }

    /**
     * @param string|null $retailer
     *
     * @return $this
     */
    public function setRetailer(?string $retailer)
    {
        $this->retailer = $retailer;

        return $this;
    }
}
