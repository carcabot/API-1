<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\AffiliateWebServicePartner;
use Doctrine\ORM\Mapping as ORM;

/**
 * An affiliate program.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"affiliate_program_read"}},
 *     "denormalization_context"={"groups"={"affiliate_program_write"}},
 *     "filters"={
 *         "affiliate_program.date",
 *         "affiliate_program.order",
 *         "affiliate_program.search",
 *     },
 * })
 */
class AffiliateProgram extends WebPageBase
{
    /**
     * @var string|null The base tracking URL.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/url")
     */
    protected $baseTrackingUrl;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var string|null The identifier of the affiliate program.
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     * @ApiProperty()
     */
    protected $programNumber;

    /**
     * @var AffiliateWebServicePartner The service provider, service operator, or service performer; the goods producer. Another party (a seller) may offer those services or goods on behalf of the provider. A provider may also serve as the seller.
     *
     * @ORM\Column(type="affiliate_web_service_partner_enum", nullable=false)
     * @ApiProperty(iri="http://schema.org/provider")
     */
    protected $provider;

    /**
     * @var WebPage|null A web page. Every web page is implicitly assumed to be declared to be of type WebPage, so the various properties about that webpage, such as breadcrumb may be used. We recommend explicit declaration if these properties are specified, but if they are found outside of an itemscope, they will be assumed to be about the page.
     *
     * @ORM\OneToOne(targetEntity="WebPage")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $terms;

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

    /**
     * Sets baseTrackingUrl.
     *
     * @param string|null $baseTrackingUrl
     *
     * @return $this
     */
    public function setBaseTrackingUrl(?string $baseTrackingUrl)
    {
        $this->baseTrackingUrl = $baseTrackingUrl;

        return $this;
    }

    /**
     * Gets baseTrackingUrl.
     *
     * @return string|null
     */
    public function getBaseTrackingUrl(): ?string
    {
        return $this->baseTrackingUrl;
    }

    /**
     * Sets programNumber.
     *
     * @param string|null $programNumber
     *
     * @return $this
     */
    public function setProgramNumber(?string $programNumber)
    {
        $this->programNumber = $programNumber;

        return $this;
    }

    /**
     * Gets programNumber.
     *
     * @return string|null
     */
    public function getProgramNumber(): ?string
    {
        return $this->programNumber;
    }

    /**
     * Sets name.
     *
     * @param string|null $name
     *
     * @return $this
     */
    public function setName(?string $name)
    {
        $this->name = $name;

        return $this;
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
     * Sets provider.
     *
     * @param AffiliateWebServicePartner $provider
     *
     * @return $this
     */
    public function setProvider(AffiliateWebServicePartner $provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Gets provider.
     *
     * @return AffiliateWebServicePartner
     */
    public function getProvider(): AffiliateWebServicePartner
    {
        return $this->provider;
    }

    /**
     * Gets trackingUrl.
     *
     * @return string
     */
    public function getTrackingUrl(): string
    {
        // just placeholder, injection in normalizer
        return '';
    }

    /**
     * Sets terms.
     *
     * @param WebPage|null $terms
     *
     * @return $this
     */
    public function setTerms(?WebPage $terms)
    {
        $this->terms = $terms;

        return $this;
    }

    /**
     * Gets terms.
     *
     * @return WebPage|null
     */
    public function getTerms(): ?WebPage
    {
        return $this->terms;
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
}
