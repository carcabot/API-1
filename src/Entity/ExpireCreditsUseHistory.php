<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A record of usage for expiring credits.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"expire_credits_use_history_read"}},
 *     "denormalization_context"={"groups"={"expire_credits_use_history_write"}}
 * })
 */
class ExpireCreditsUseHistory
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
     * @var UpdateCreditsAction The expiring credits action.
     *
     * @ORM\ManyToOne(targetEntity="UpdateCreditsAction")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @ApiProperty()
     */
    protected $expireAction;

    /**
     * @var UpdateCreditsAction The use credits action.
     *
     * @ORM\ManyToOne(targetEntity="UpdateCreditsAction")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @ApiProperty()
     */
    protected $useAction;

    /**
     * @var string The amount of credits used.
     *
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=false)
     * @ApiProperty()
     */
    protected $useAmount;

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
     * Sets expireAction.
     *
     * @param UpdateCreditsAction $expireAction
     *
     * @return $this
     */
    public function setExpireAction(UpdateCreditsAction $expireAction)
    {
        $this->expireAction = $expireAction;

        return $this;
    }

    /**
     * Gets expireAction.
     *
     * @return UpdateCreditsAction
     */
    public function getExpireAction(): UpdateCreditsAction
    {
        return $this->expireAction;
    }

    /**
     * Sets useAction.
     *
     * @param UpdateCreditsAction $useAction
     *
     * @return $this
     */
    public function setUseAction(UpdateCreditsAction $useAction)
    {
        $this->useAction = $useAction;

        return $this;
    }

    /**
     * Gets useAction.
     *
     * @return UpdateCreditsAction
     */
    public function getUseAction(): UpdateCreditsAction
    {
        return $this->useAction;
    }

    /**
     * Sets useAmount.
     *
     * @param string $useAmount
     *
     * @return $this
     */
    public function setUseAmount(string $useAmount)
    {
        $this->useAmount = $useAmount;

        return $this;
    }

    /**
     * Gets useAmount.
     *
     * @return string
     */
    public function getUseAmount(): string
    {
        return $this->useAmount;
    }
}
