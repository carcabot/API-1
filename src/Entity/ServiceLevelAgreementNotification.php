<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The service level agreement notification.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"service_level_agreement_notification_read"}},
 *     "denormalization_context"={"groups"={"service_level_agreement_notification_write"}}
 * })
 */
class ServiceLevelAgreementNotification
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
     * @var string The name of the item.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var QuantitativeValue The value of the quantitative value or property value node.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty(iri="http://schema.org/value")
     */
    protected $value;

    public function __construct()
    {
        $this->value = new QuantitativeValue();
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
     * Sets name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets value.
     *
     * @param QuantitativeValue $value
     *
     * @return $this
     */
    public function setValue(QuantitativeValue $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets value.
     *
     * @return QuantitativeValue
     */
    public function getValue(): QuantitativeValue
    {
        return $this->value;
    }
}
