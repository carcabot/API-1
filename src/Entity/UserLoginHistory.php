<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * User login history.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"user_login_history_read"}},
 *     "denormalization_context"={"groups"={"user_login_history_write"}},
 * })
 */
class UserLoginHistory
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime The datetime the user logged in.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty()
     */
    protected $date;

    /**
     * @var string|null The device used.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $device;

    /**
     * @var string|null The login IP address.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $ipAddress;

    /**
     * @var User The user.
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="loginHistories")
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
     * Sets date.
     *
     * @param \DateTime $date
     *
     * @return $this
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Gets date.
     *
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Sets device.
     *
     * @param string|null $device
     *
     * @return $this
     */
    public function setDevice(?string $device)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * Gets device.
     *
     * @return string|null
     */
    public function getDevice(): ?string
    {
        return $this->device;
    }

    /**
     * Sets ipAddress.
     *
     * @param string|null $ipAddress
     *
     * @return $this
     */
    public function setIpAddress(?string $ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Gets ipAddress.
     *
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
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
}
