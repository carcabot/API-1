<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\BlacklistConfigurationAction;
use Doctrine\ORM\Mapping as ORM;

/**
 * The configurations related with blacklisted customers.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"customer_blacklist_configuration_read"}},
 *     "denormalization_context"={"groups"={"customer_blacklist_configuration_write"}}
 * })
 */
class CustomerBlacklistConfiguration
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
     * @var BlacklistConfigurationAction Determines the action for the configuration of blacklisted customers.
     *
     * @ORM\Column(type="blacklist_configuration_action_enum", nullable=false)
     * @ApiProperty()
     */
    protected $action;

    /**
     * @var string|null A description of the action.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var bool The enabled indicator.
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @ApiProperty()
     */
    protected $enabled;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return BlacklistConfigurationAction
     */
    public function getAction(): BlacklistConfigurationAction
    {
        return $this->action;
    }

    /**
     * @param BlacklistConfigurationAction $action
     */
    public function setAction(BlacklistConfigurationAction $action): void
    {
        $this->action = $action;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
