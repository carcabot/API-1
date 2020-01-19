<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="authtokens")
 */
class AuthToken
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string|null The device.
     *
     * @ODM\Field(type="string", name="device_name")
     */
    protected $deviceName;

    /**
     * @var \DateTime The login date.
     *
     * @ODM\Field(type="date", name="login_date")
     */
    protected $loginDate;

    /**
     * @var string|null The logged in user
     *
     * @ODM\Field(type="id", name="user_id")
     */
    protected $userId;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    /**
     * @return \DateTime
     */
    public function getLoginDate(): \DateTime
    {
        return $this->loginDate;
    }

    /**
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }
}
