<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The tariff rate terms.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"tariff_rate_terms_read"}},
 *     "denormalization_context"={"groups"={"tariff_rate_terms_write"}},
 * })
 */
class TariffRateTerms
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
     * @var AddonServiceList|null List of add-on services.
     *
     * @ORM\ManyToOne(targetEntity="AddonServiceList", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $addonServiceList;

    /**
     * @var bool|null Retailer to bill electricity.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $billFromServiceProvider;

    /**
     * @var string|null Bundled products or services.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $bundledProductsOrServices;

    /**
     * @var string|null Duration of contract.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $contractDuration;

    /**
     * @var string|null Renewal of contract.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $contractRenewal;

    /**
     * @var string|null Conditional discount.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $discount;

    /**
     * @var string|null Early termination charges.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $earlyTerminationCharges;

    /**
     * @var string|null Fixed electricity rate.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $fixedRate;

    /**
     * @var FreeGiftList|null List of free gifts.
     *
     * @ORM\ManyToOne(targetEntity="FreeGiftList", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $freeGiftList;

    /**
     * @var string|null Incentives given by retailer.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $incentives;

    /**
     * @var string|null Late payment charges.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $latePaymentCharges;

    /**
     * @var string|null Meter instalation fee.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $meterInstallationFee;

    /**
     * @var string|null For non-standard price plan.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $nonStandardPlan;

    /**
     * @var string|null Any other fee and charges.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $otherFeeAndCharges;

    /**
     * @var string|null Name pf price plan.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $planName;

    /**
     * @var string|null Type of price plan.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $planType;

    /**
     * @var string|null Additional fee for prevailing meter.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $prevailingMeterCharge;

    /**
     * @var string|null Registration fee.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $registrationFee;

    /**
     * @var string|null The security deposit.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $securityDeposit;

    /**
     * @var SecurityDepositList|null A list of security deposits.
     *
     * @ORM\ManyToOne(targetEntity="SecurityDepositList", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $securityDepositList;

    /**
     * @var string|null Service fee.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $serviceFee;

    /**
     * @var string|null Name of electric retailer.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $serviceProvider;

    /**
     * @var bool|null Use of smart meter.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $smartMeter;

    /**
     * @var string|null Standard price plan.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $standardPlan;

    /**
     * @var TariffRate The tariff rate.
     *
     * @ORM\OneToOne(targetEntity="TariffRate", mappedBy="terms", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @ApiProperty()
     */
    protected $tariffRate;

    /**
     * @var string|null Factsheet version date.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $version;

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;

            if (null !== $this->addonServiceList) {
                $this->addonServiceList = clone $this->addonServiceList;
            }

            if (null !== $this->securityDepositList) {
                $this->securityDepositList = clone $this->securityDepositList;
            }

            if (null !== $this->freeGiftList) {
                $this->freeGiftList = clone $this->freeGiftList;
            }
        }
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
     * Sets addonServiceList.
     *
     * @param AddonServiceList|null $addonServiceList
     *
     * @return $this
     */
    public function setAddonServiceList(?AddonServiceList $addonServiceList)
    {
        $this->addonServiceList = $addonServiceList;

        return $this;
    }

    /**
     * Gets addonServiceList.
     *
     * @return AddonServiceList|null
     */
    public function getAddonServiceList(): ?AddonServiceList
    {
        return $this->addonServiceList;
    }

    /**
     * Sets billFromServiceProvider.
     *
     * @param bool|null $billFromServiceProvider
     *
     * @return $this
     */
    public function setBillFromServiceProvider(?bool $billFromServiceProvider)
    {
        $this->billFromServiceProvider = $billFromServiceProvider;

        return $this;
    }

    /**
     * Gets billFromServiceProvider.
     *
     * @return bool|null
     */
    public function isBillFromServiceProvider(): ?bool
    {
        return $this->billFromServiceProvider;
    }

    /**
     * Sets bundledProductsOrServices.
     *
     * @param string|null $bundledProductsOrServices
     *
     * @return $this
     */
    public function setBundledProductsOrServices(?string $bundledProductsOrServices)
    {
        $this->bundledProductsOrServices = $bundledProductsOrServices;

        return $this;
    }

    /**
     * Gets bundledProductsOrServices.
     *
     * @return string|null
     */
    public function getBundledProductsOrServices(): ?string
    {
        return $this->bundledProductsOrServices;
    }

    /**
     * Sets contractDuration.
     *
     * @param string|null $contractDuration
     *
     * @return $this
     */
    public function setContractDuration(?string $contractDuration)
    {
        $this->contractDuration = $contractDuration;

        return $this;
    }

    /**
     * Gets contractDuration.
     *
     * @return string|null
     */
    public function getContractDuration(): ?string
    {
        return $this->contractDuration;
    }

    /**
     * Sets contractRenewal.
     *
     * @param string|null $contractRenewal
     *
     * @return $this
     */
    public function setContractRenewal(?string $contractRenewal)
    {
        $this->contractRenewal = $contractRenewal;

        return $this;
    }

    /**
     * Gets contractRenewal.
     *
     * @return string|null
     */
    public function getContractRenewal(): ?string
    {
        return $this->contractRenewal;
    }

    /**
     * Sets discount.
     *
     * @param string|null $discount
     *
     * @return $this
     */
    public function setDiscount(?string $discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Gets discount.
     *
     * @return string|null
     */
    public function getDiscount(): ?string
    {
        return $this->discount;
    }

    /**
     * Sets earlyTerminationCharges.
     *
     * @param string|null $earlyTerminationCharges
     *
     * @return $this
     */
    public function setEarlyTerminationCharges(?string $earlyTerminationCharges)
    {
        $this->earlyTerminationCharges = $earlyTerminationCharges;

        return $this;
    }

    /**
     * Gets earlyTerminationCharges.
     *
     * @return string|null
     */
    public function getEarlyTerminationCharges(): ?string
    {
        return $this->earlyTerminationCharges;
    }

    /**
     * Sets fixedRate.
     *
     * @param string|null $fixedRate
     *
     * @return $this
     */
    public function setFixedRate(?string $fixedRate)
    {
        $this->fixedRate = $fixedRate;

        return $this;
    }

    /**
     * Gets fixedRate.
     *
     * @return string|null
     */
    public function getFixedRate(): ?string
    {
        return $this->fixedRate;
    }

    /**
     * Sets freeGiftList.
     *
     * @param FreeGiftList|null $freeGiftList
     *
     * @return $this
     */
    public function setFreeGiftList(?FreeGiftList $freeGiftList)
    {
        $this->freeGiftList = $freeGiftList;

        return $this;
    }

    /**
     * Gets FreeGiftList.
     *
     * @return FreeGiftList|null
     */
    public function getFreeGiftList(): ?FreeGiftList
    {
        return $this->freeGiftList;
    }

    /**
     * Sets incentives.
     *
     * @param string|null $incentives
     *
     * @return $this
     */
    public function setIncentives(?string $incentives)
    {
        $this->incentives = $incentives;

        return $this;
    }

    /**
     * Gets incentives.
     *
     * @return string|null
     */
    public function getIncentives(): ?string
    {
        return $this->incentives;
    }

    /**
     * Sets latePaymentCharges.
     *
     * @param string|null $latePaymentCharges
     *
     * @return $this
     */
    public function setLatePaymentCharges(?string $latePaymentCharges)
    {
        $this->latePaymentCharges = $latePaymentCharges;

        return $this;
    }

    /**
     * Gets latePaymentCharges.
     *
     * @return string|null
     */
    public function getLatePaymentCharges(): ?string
    {
        return $this->latePaymentCharges;
    }

    /**
     * Sets meterInstallationFee.
     *
     * @param string|null $meterInstallationFee
     *
     * @return $this
     */
    public function setMeterInstallationFee(?string $meterInstallationFee)
    {
        $this->meterInstallationFee = $meterInstallationFee;

        return $this;
    }

    /**
     * Gets meterInstallationFee.
     *
     * @return string|null
     */
    public function getMeterInstallationFee(): ?string
    {
        return $this->meterInstallationFee;
    }

    /**
     * Sets nonStandardPlan.
     *
     * @param string|null $nonStandardPlan
     *
     * @return $this
     */
    public function setNonStandardPlan(?string $nonStandardPlan)
    {
        $this->nonStandardPlan = $nonStandardPlan;

        return $this;
    }

    /**
     * Gets nonStandardPlan.
     *
     * @return string|null
     */
    public function getNonStandardPlan(): ?string
    {
        return $this->nonStandardPlan;
    }

    /**
     * Sets otherFeeAndCharges.
     *
     * @param string|null $otherFeeAndCharges
     *
     * @return $this
     */
    public function setOtherFeeAndCharges(?string $otherFeeAndCharges)
    {
        $this->otherFeeAndCharges = $otherFeeAndCharges;

        return $this;
    }

    /**
     * Gets otherFeeAndCharges.
     *
     * @return string|null
     */
    public function getOtherFeeAndCharges(): ?string
    {
        return $this->otherFeeAndCharges;
    }

    /**
     * Sets planName.
     *
     * @param string|null $planName
     *
     * @return $this
     */
    public function setPlanName(?string $planName)
    {
        $this->planName = $planName;

        return $this;
    }

    /**
     * Gets planName.
     *
     * @return string|null
     */
    public function getPlanName(): ?string
    {
        return $this->planName;
    }

    /**
     * Sets planType.
     *
     * @param string|null $planType
     *
     * @return $this
     */
    public function setPlanType(?string $planType)
    {
        $this->planType = $planType;

        return $this;
    }

    /**
     * Gets planType.
     *
     * @return string|null
     */
    public function getPlanType(): ?string
    {
        return $this->planType;
    }

    /**
     * Sets prevailingMeterCharge.
     *
     * @param string|null $prevailingMeterCharge
     *
     * @return $this
     */
    public function setPrevailingMeterCharge(?string $prevailingMeterCharge)
    {
        $this->prevailingMeterCharge = $prevailingMeterCharge;

        return $this;
    }

    /**
     * Gets prevailingMeterCharge.
     *
     * @return string|null
     */
    public function getPrevailingMeterCharge(): ?string
    {
        return $this->prevailingMeterCharge;
    }

    /**
     * Sets registrationFee.
     *
     * @param string|null $registrationFee
     *
     * @return $this
     */
    public function setRegistrationFee(?string $registrationFee)
    {
        $this->registrationFee = $registrationFee;

        return $this;
    }

    /**
     * Gets registrationFee.
     *
     * @return string|null
     */
    public function getRegistrationFee(): ?string
    {
        return $this->registrationFee;
    }

    /**
     * Sets securityDeposit.
     *
     * @param string|null $securityDeposit
     *
     * @return $this
     */
    public function setSecurityDeposit(?string $securityDeposit)
    {
        $this->securityDeposit = $securityDeposit;

        return $this;
    }

    /**
     * Gets securityDeposit.
     *
     * @return string|null
     */
    public function getSecurityDeposit(): ?string
    {
        return $this->securityDeposit;
    }

    /**
     * Sets securityDepositList.
     *
     * @param SecurityDepositList|null $securityDepositList
     *
     * @return $this
     */
    public function setSecurityDepositList(?SecurityDepositList $securityDepositList)
    {
        $this->securityDepositList = $securityDepositList;

        return $this;
    }

    /**
     * Gets securityDepositList.
     *
     * @return SecurityDepositList|null
     */
    public function getSecurityDepositList(): ?SecurityDepositList
    {
        return $this->securityDepositList;
    }

    /**
     * Sets serviceFee.
     *
     * @param string|null $serviceFee
     *
     * @return $this
     */
    public function setServiceFee(?string $serviceFee)
    {
        $this->serviceFee = $serviceFee;

        return $this;
    }

    /**
     * Gets serviceFee.
     *
     * @return string|null
     */
    public function getServiceFee(): ?string
    {
        return $this->serviceFee;
    }

    /**
     * Sets serviceProvider.
     *
     * @param string|null $serviceProvider
     *
     * @return $this
     */
    public function setServiceProvider(?string $serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;

        return $this;
    }

    /**
     * Gets serviceProvider.
     *
     * @return string|null
     */
    public function getServiceProvider(): ?string
    {
        return $this->serviceProvider;
    }

    /**
     * Sets smartMeter.
     *
     * @param bool|null $smartMeter
     *
     * @return $this
     */
    public function setSmartMeter(?bool $smartMeter)
    {
        $this->smartMeter = $smartMeter;

        return $this;
    }

    /**
     * Gets smartMeter.
     *
     * @return bool|null
     */
    public function isSmartMeter(): ?bool
    {
        return $this->smartMeter;
    }

    /**
     * Sets standardPlan.
     *
     * @param string|null $standardPlan
     *
     * @return $this
     */
    public function setStandardPlan(?string $standardPlan)
    {
        $this->standardPlan = $standardPlan;

        return $this;
    }

    /**
     * Gets standardPlan.
     *
     * @return string|null
     */
    public function getStandardPlan(): ?string
    {
        return $this->standardPlan;
    }

    /**
     * Sets tariffRate.
     *
     * @param TariffRate $tariffRate
     *
     * @return $this
     */
    public function setTariffRate(TariffRate $tariffRate)
    {
        $this->tariffRate = $tariffRate;
        $tariffRate->setTerms($this);

        return $this;
    }

    /**
     * Gets tariffRate.
     *
     * @return TariffRate
     */
    public function getTariffRate(): TariffRate
    {
        return $this->tariffRate;
    }

    /**
     * Sets version.
     *
     * @param string|null $version
     *
     * @return $this
     */
    public function setVersion(?string $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Gets version.
     *
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }
}
