<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The activity carried out on an item.
 *
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"date_created"}),
 * })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=128)
 * @ORM\DiscriminatorMap({
 *     "activity"="Activity",
 *     "email_activity"="EmailActivity",
 *     "phone_contact_activity"="PhoneContactActivity",
 *     "sms_activity"="SmsActivity",
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"activity_read"}},
 *     "denormalization_context"={"groups"={"activity_write"}},
 * })
 */
class Activity
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
     * @var string|null The textual content of this CreativeWork.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/text")
     */
    protected $text;

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
     * Gets agent.
     *
     * @return User|null
     */
    public function getAgent(): ?User
    {
        return $this->agent;
    }

    /**
     * Sets text.
     *
     * @param string|null $text
     *
     * @return $this
     */
    public function setText(?string $text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Gets text.
     *
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }
}
