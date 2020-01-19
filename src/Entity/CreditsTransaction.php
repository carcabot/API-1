<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A credits transaction.
 *
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"credits_transaction_number"}),
 * })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=128)
 * @ORM\DiscriminatorMap({
 *     "credits_transaction"="CreditsTransaction",
 *     "money_credits_transaction"="MoneyCreditsTransaction",
 *     "point_credits_transaction"="PointCreditsTransaction",
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"credits_transaction_read"}},
 *     "denormalization_context"={"groups"={"credits_transaction_write"}}
 * })
 */
class CreditsTransaction
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
     * @var string|null The credits transaction number for each transaction.
     *
     * @ORM\Column(type="string", length=128, unique=true, nullable=true)
     * @ApiProperty()
     */
    protected $creditsTransactionNumber;

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
     * Gets id.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Sets creditsTransactionNumber.
     *
     * @param string|null $creditsTransactionNumber
     *
     * @return $this
     */
    public function setCreditsTransactionNumber(?string $creditsTransactionNumber)
    {
        $this->creditsTransactionNumber = $creditsTransactionNumber;

        return $this;
    }

    /**
     * Gets creditsTransactionNumber.
     *
     * @return string|null
     */
    public function getCreditsTransactionNumber(): ?string
    {
        return $this->creditsTransactionNumber;
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
