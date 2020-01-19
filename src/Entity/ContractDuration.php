<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The durations of contract.
 *
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"date_created"}),
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"contract_duration_read"}},
 *     "denormalization_context"={"groups"={"contract_duration_write"}},
 * })
 */
class ContractDuration
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
     * @var QuantitativeValue The term of the contract.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $term;

    /**
     * @var \DateTime The date when the item becomes valid.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty(iri="http://schema.org/validFrom")
     */
    protected $validFrom;

    /**
     * @var \DateTime The date after when the item is not valid. For example the end of an offer, salary period, or a period of opening hours.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty(iri="http://schema.org/validThrough")
     */
    protected $validThrough;

    public function __construct()
    {
        $this->term = new QuantitativeValue();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return QuantitativeValue
     */
    public function getTerm(): QuantitativeValue
    {
        return $this->term;
    }

    /**
     * @param QuantitativeValue $term
     *
     * @return $this
     */
    public function setTerm(QuantitativeValue $term)
    {
        $this->term = $term;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getValidFrom(): \DateTime
    {
        return $this->validFrom;
    }

    /**
     * @param \DateTime $validFrom
     *
     * @return $this
     */
    public function setValidFrom(\DateTime $validFrom)
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getValidThrough(): \DateTime
    {
        return $this->validThrough;
    }

    /**
     * @param \DateTime $validThrough
     *
     * @return $this
     */
    public function setValidThrough(\DateTime $validThrough)
    {
        $this->validThrough = $validThrough;

        return $this;
    }
}
