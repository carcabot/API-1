<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The Referral Credits Scheme.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"referral_credits_scheme_read"}},
 *     "denormalization_context"={"groups"={"referral_credits_scheme_write"}},
 * })
 */
class ReferralCreditsScheme extends CreditsScheme
{
    /**
     * @var QuantitativeValue Amount of points given to referral.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $referralAmount;

    /**
     * @var QuantitativeValue Amount of points given to referral.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $refereeAmount;

    public function __construct()
    {
        parent::__construct();

        $this->referralAmount = new QuantitativeValue();
        $this->refereeAmount = new QuantitativeValue();
    }

    public function __clone()
    {
        parent::__clone();
    }

    /**
     * Gets referral amount.
     *
     * @return QuantitativeValue
     */
    public function getReferralAmount(): QuantitativeValue
    {
        return $this->referralAmount;
    }

    /**
     * Sets referral amount.
     *
     * @param QuantitativeValue $referralAmount
     *
     * @return $this
     */
    public function setReferralAmount(QuantitativeValue $referralAmount)
    {
        $this->referralAmount = $referralAmount;

        return $this;
    }

    /**
     * Gets referee amount.
     *
     * @return QuantitativeValue
     */
    public function getRefereeAmount(): QuantitativeValue
    {
        return $this->refereeAmount;
    }

    /**
     * Sets referee amount.
     *
     * @param QuantitativeValue $refereeAmount
     *
     * @return $this
     */
    public function setRefereeAmount(QuantitativeValue $refereeAmount)
    {
        $this->refereeAmount = $refereeAmount;

        return $this;
    }
}
