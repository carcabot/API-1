<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An corporation/person that is partnered with. For example, a convenience store chain, a club, or a restaurant.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"partner_read"}},
 *     "denormalization_context"={"groups"={"partner_write"}},
 * })
 */
class Partner
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
     * @var User|null Administrator in charge of the partner.
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $administrator;

    /**
     * @var string[] An intended audience, i.e. a group for whom something was created.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty(iri="http://schema.org/audience")
     */
    protected $announcementAudiences;

    /**
     * @var Collection<CommissionRate> The commission rate that applies to the partner.
     *
     * @ORM\ManyToMany(targetEntity="CommissionRate", inversedBy="partners", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="partner_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="commission_rate_id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $commissionRates;

    /**
     * @var Collection<PartnerCommissionStatement> The commission statements generated for the partner.
     *
     * @ORM\OneToMany(targetEntity="PartnerCommissionStatement", mappedBy="partner", orphanRemoval=true)
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $commissionStatements;

    /**
     * @var string|null The homepage URL.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $homepageUrl;

    /**
     * @var \DateTime|null Date of joining.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $joiningDate;

    /**
     * @var QuantitativeValue The payout cycle.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $payoutCycle;

    /**
     * @var \DateTime|null Date when the payout starts.
     *
     * @ORM\Column(type="date", nullable=true)
     * @ApiProperty()
     */
    protected $payoutStartDate;

    /**
     * @var bool|null Determines whether the referral url is hidden.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $referralUrlHidden;

    /**
     * @var TariffRateList|null A list of tariff rates.
     *
     * @ORM\ManyToOne(targetEntity="TariffRateList")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $tariffRateList;

    /**
     * @var \DateTime|null Date of termination.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $terminationDate;

    public function __construct()
    {
        $this->announcementAudiences = [];
        $this->commissionRates = new ArrayCollection();
        $this->commissionStatements = new ArrayCollection();
        $this->payoutCycle = new QuantitativeValue();
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
     * Sets administrator.
     *
     * @param User|null $administrator
     *
     * @return $this
     */
    public function setAdministrator(?User $administrator)
    {
        $this->administrator = $administrator;

        return $this;
    }

    /**
     * Gets administrator.
     *
     * @return User|null
     */
    public function getAdministrator(): ?User
    {
        return $this->administrator;
    }

    /**
     * Adds announcementAudience.
     *
     * @param string $announcementAudience
     *
     * @return $this
     */
    public function addAnnouncementAudience(string $announcementAudience)
    {
        $this->announcementAudiences[] = $announcementAudience;

        return $this;
    }

    /**
     * Removes announcementAudience.
     *
     * @param string $announcementAudience
     *
     * @return $this
     */
    public function removeAnnouncementAudience(string $announcementAudience)
    {
        if (false !== ($key = \array_search($announcementAudience, $this->announcementAudiences, true))) {
            \array_splice($this->announcementAudiences, $key, 1);
        }

        return $this;
    }

    /**
     * Gets announcementAudiences.
     *
     * @return string[]
     */
    public function getAnnouncementAudiences(): array
    {
        return $this->announcementAudiences;
    }

    /**
     * Adds commissionRate.
     *
     * @param CommissionRate $commissionRate
     *
     * @return $this
     */
    public function addCommissionRate(CommissionRate $commissionRate)
    {
        $this->commissionRates[] = $commissionRate;

        return $this;
    }

    /**
     * Removes commissionRate.
     *
     * @param CommissionRate $commissionRate
     *
     * @return $this
     */
    public function removeCommissionRate(CommissionRate $commissionRate)
    {
        $this->commissionRates->removeElement($commissionRate);

        return $this;
    }

    /**
     * Gets commissionRates.
     *
     * @return CommissionRate[]
     */
    public function getCommissionRates(): array
    {
        return $this->commissionRates->getValues();
    }

    /**
     * Adds commissionStatement.
     *
     * @param PartnerCommissionStatement $commissionStatement
     *
     * @return $this
     */
    public function addCommissionStatement(PartnerCommissionStatement $commissionStatement)
    {
        $this->commissionStatements[] = $commissionStatement;
        $commissionStatement->setPartner($this);

        return $this;
    }

    /**
     * Removes commissionStatement.
     *
     * @param PartnerCommissionStatement $commissionStatement
     *
     * @return $this
     */
    public function removeCommissionStatement(PartnerCommissionStatement $commissionStatement)
    {
        $this->commissionStatements->removeElement($commissionStatement);

        return $this;
    }

    /**
     * Gets commissionStatements.
     *
     * @return PartnerCommissionStatement[]
     */
    public function getCommissionStatements(): array
    {
        return $this->commissionStatements->getValues();
    }

    /**
     * Sets homepageUrl.
     *
     * @param string|null $homepageUrl
     *
     * @return $this
     */
    public function setHomepageUrl(?string $homepageUrl)
    {
        $this->homepageUrl = $homepageUrl;

        return $this;
    }

    /**
     * Gets homepageUrl.
     *
     * @return string|null
     */
    public function getHomepageUrl(): ?string
    {
        return $this->homepageUrl;
    }

    /**
     * Sets joiningDate.
     *
     * @param \DateTime|null $joiningDate
     *
     * @return $this
     */
    public function setJoiningDate(?\DateTime $joiningDate)
    {
        $this->joiningDate = $joiningDate;

        return $this;
    }

    /**
     * Gets joiningDate.
     *
     * @return \DateTime|null
     */
    public function getJoiningDate(): ?\DateTime
    {
        return $this->joiningDate;
    }

    /**
     * Sets payoutCycle.
     *
     * @param QuantitativeValue $payoutCycle
     *
     * @return $this
     */
    public function setPayoutCycle(QuantitativeValue $payoutCycle)
    {
        $this->payoutCycle = $payoutCycle;

        return $this;
    }

    /**
     * Gets payoutCycle.
     *
     * @return QuantitativeValue
     */
    public function getPayoutCycle(): QuantitativeValue
    {
        return $this->payoutCycle;
    }

    /**
     * Sets payoutStartDate.
     *
     * @param \DateTime|null $payoutStartDate
     *
     * @return $this
     */
    public function setPayoutStartDate(?\DateTime $payoutStartDate)
    {
        $this->payoutStartDate = $payoutStartDate;

        return $this;
    }

    /**
     * Gets payoutStartDate.
     *
     * @return \DateTime|null
     */
    public function getPayoutStartDate(): ?\DateTime
    {
        return $this->payoutStartDate;
    }

    /**
     * Sets referralUrlHidden.
     *
     * @param bool|null $referralUrlHidden
     *
     * @return $this
     */
    public function setReferralUrlHidden(?bool $referralUrlHidden)
    {
        $this->referralUrlHidden = $referralUrlHidden;

        return $this;
    }

    /**
     * Gets referralUrlHidden.
     *
     * @return bool|null
     */
    public function isReferralUrlHidden(): ?bool
    {
        return $this->referralUrlHidden;
    }

    /**
     * Sets tariffRateList.
     *
     * @param TariffRateList|null $tariffRateList
     *
     * @return $this
     */
    public function setTariffRateList(?TariffRateList $tariffRateList)
    {
        $this->tariffRateList = $tariffRateList;

        return $this;
    }

    /**
     * Gets tariffRateList.
     *
     * @return TariffRateList|null
     */
    public function getTariffRateList(): ?TariffRateList
    {
        return $this->tariffRateList;
    }

    /**
     * Sets terminationDate.
     *
     * @param \DateTime|null $terminationDate
     *
     * @return $this
     */
    public function setTerminationDate(?\DateTime $terminationDate)
    {
        $this->terminationDate = $terminationDate;

        return $this;
    }

    /**
     * Gets terminationDate.
     *
     * @return \DateTime|null
     */
    public function getTerminationDate(): ?\DateTime
    {
        return $this->terminationDate;
    }
}
