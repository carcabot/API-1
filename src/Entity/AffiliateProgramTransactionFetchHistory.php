<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\AffiliateWebServicePartner;
use Doctrine\ORM\Mapping as ORM;

/**
 * We need this because Affiliate APIs are very limited, we need to keep track of old fetch criterias in order to make sure we get the new conversions.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"affiliate_program_transaction_fetch_history_read"}},
 *     "denormalization_context"={"groups"={"affiliate_program_transaction_fetch_history_write"}},
 * })
 */
class AffiliateProgramTransactionFetchHistory
{
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
     * @var QuantitativeValue The number of pending transaction conversions.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $pendingConversions;

    /**
     * @var \DateTime The end date used in the fetch.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty()
     */
    protected $endDate;

    /**
     * @var AffiliateWebServicePartner The service provider, service operator, or service performer; the goods producer. Another party (a seller) may offer those services or goods on behalf of the provider. A provider may also serve as the seller.
     *
     * @ORM\Column(type="affiliate_web_service_partner_enum", nullable=false)
     * @ApiProperty(iri="http://schema.org/provider")
     */
    protected $provider;

    /**
     * @var \DateTime The start date used in the fetch.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty()
     */
    protected $startDate;

    public function __construct()
    {
        $this->pendingConversions = new QuantitativeValue();
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
     * Sets pendingConversions.
     *
     * @param QuantitativeValue $pendingConversions
     *
     * @return $this
     */
    public function setPendingConversions(QuantitativeValue $pendingConversions)
    {
        $this->pendingConversions = $pendingConversions;

        return $this;
    }

    /**
     * Gets pendingConversions.
     *
     * @return QuantitativeValue
     */
    public function getPendingConversions(): QuantitativeValue
    {
        return $this->pendingConversions;
    }

    /**
     * Sets endDate.
     *
     * @param \DateTime $endDate
     *
     * @return $this
     */
    public function setEndDate(\DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Gets endDate.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
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
     * Sets startDate.
     *
     * @param \DateTime $startDate
     *
     * @return $this
     */
    public function setStartDate(\DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Gets startDate.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
}
