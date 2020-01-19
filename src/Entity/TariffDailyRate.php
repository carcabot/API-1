<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * TariffDailyRate. A structured value providing information about daily rate of a tariff.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"tariff_daily_rate_read"}},
 *     "denormalization_context"={"groups"={"tariff_daily_rate_write"}},
 *
 * })
 */
class TariffDailyRate
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
     * @var QuantitativeValue The rate value.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $rate;

    /**
     * @var TariffRate
     *
     * @ORM\ManyToOne(targetEntity="TariffRate", inversedBy="dailyRates")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $tariffRate;

    /**
     * @var \DateTime|null The date when the item becomes valid.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validFrom")
     */
    protected $validFrom;

    /**
     * @var \DateTime|null The date after when the item is not valid.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validThrough")
     */
    protected $validThrough;

    public function __construct()
    {
        $this->rate = new QuantitativeValue();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return TariffRate
     */
    public function getTariffRate(): TariffRate
    {
        return $this->tariffRate;
    }

    /**
     * @param TariffRate $tariffRate
     *
     * @return $this
     */
    public function setTariffRate(TariffRate $tariffRate)
    {
        $this->tariffRate = $tariffRate;

        return $this;
    }

    /**
     * @return QuantitativeValue
     */
    public function getRate(): QuantitativeValue
    {
        return $this->rate;
    }

    /**
     * @param QuantitativeValue $rate
     *
     * @return $this
     */
    public function setRate(QuantitativeValue $rate)
    {
        $this->rate = $rate;

        return $this;
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
