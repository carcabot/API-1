<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * User created in old version.
 *
 * @ORM\Entity()
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"bridge_user_id"}),
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"bridge_user_read"}},
 *     "denormalization_context"={"groups"={"bridge_user_write"}},
 * })
 */
class BridgeUser
{
    use Traits\BlameableTrait;
    use Traits\TimestampableTrait;

    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string The user's authentication token.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty()
     */
    protected $authToken;

    /**
     * @var string The userId from old version.
     *
     * @ORM\Column(type="text", unique=true, nullable=false)
     * @ApiProperty()
     */
    protected $bridgeUserId;

    /**
     * @var User A user.
     *
     * @ORM\OneToOne(targetEntity="User", inversedBy="bridgeUser", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @ApiProperty()
     */
    protected $user;

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
     * Sets authToken.
     *
     * @param string $authToken
     *
     * @return $this
     */
    public function setAuthToken(string $authToken)
    {
        $this->authToken = $authToken;

        return $this;
    }

    /**
     * Gets authToken.
     *
     * @return string
     */
    public function getAuthToken(): string
    {
        return $this->authToken ?? '';
    }

    /**
     * Sets user.
     *
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Gets user.
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Sets bridgeUserId.
     *
     * @param string $bridgeUserId
     *
     * @return $this
     */
    public function setBridgeUserId(string $bridgeUserId)
    {
        $this->bridgeUserId = $bridgeUserId;

        return $this;
    }

    /**
     * Gets bridgeUserId.
     *
     * @return string
     */
    public function getBridgeUserId(): string
    {
        return $this->bridgeUserId;
    }
}
