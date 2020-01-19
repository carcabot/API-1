<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="users")
 */
class OldUsers
{
    /**
     * @var string
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string The authorization token
     *
     * @ODM\Field(type="string", name="authtoken")
     */
    protected $authToken;

    /**
     * @var string|null The company name
     *
     * @ODM\Field(type="string", name="company_name")
     */
    protected $companyName;

    /**
     * @var string|null The customer id
     *
     * @ODM\Field(type="id", name="customer_id")
     */
    protected $customerId;

    /**
     * @var string|null The customer type
     *
     * @ODM\Field(type="string", name="customer_type")
     */
    protected $customerType;

    /**
     * @var \DateTime|null The user created at
     *
     * @ODM\Field(type="date", name="_createdAt")
     */
    protected $createdAt;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="_createdBy")
     */
    protected $createdBy;

    /**
     * @var string|null The email
     *
     * @ODM\Field(type="string", name="email")
     */
    protected $email;

    /**
     * @var string|null Family name. In the U.S., the last name of an Person. This can be used along with givenName instead of the name property.
     *
     * @ODM\Field(type="string", name="last_name")
     */
    protected $familyName;

    /**
     * @var string|null Given name. In the U.S., the first name of a Person. This can be used along with familyName instead of the name property.
     *
     * @ODM\Field(type="string", name="first_name")
     */
    protected $givenName;

    /**
     * @var bool|null The authentication status.
     *
     * @ODM\Field(type="bool", name="isAuthenticated")
     */
    protected $isAuthenticated;

    /**
     * @var bool|null The mobile status.
     *
     * @ODM\Field(type="bool", name="is_on_mobile")
     */
    protected $isOnMobile;

    /**
     * @var bool|null The activation status.
     *
     * @ODM\Field(type="bool", name="isactivated")
     */
    protected $isActivated;

    /**
     * @var \DateTime|null The last login.
     *
     * @ODM\Field(type="date", name="last_login")
     */
    protected $lastLogin;

    /**
     * @var string|null The login type
     *
     * @ODM\Field(type="string", name="login_type")
     */
    protected $loginType;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldPhoneNumber",
     * name="mobile_number")
     */
    protected $mobileNumber;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldPhoneNumber",
     * name="office_number")
     */
    protected $officeNumber;

    /**
     * @var string|null The password
     *
     * @ODM\Field(type="string", name="password")
     */
    protected $password;

    /**
     * @var string|null The partnership id
     *
     * @ODM\Field(type="id", name="partnership_id")
     */
    protected $partnershipId;

    /**
     * @var string|null The preferred language
     *
     * @ODM\Field(type="string", name="preferred_lang")
     */
    protected $preferredLanguage;

    /**
     * @var string[]|null The role Id
     *
     * @ODM\Field(type="collection", name="role_id")
     */
    protected $roleId;

    /**
     * @var string|null The sale representative id
     *
     * @ODM\Field(type="id", name="sale_id")
     */
    protected $saleId;

    /**
     * @var string|null The status
     *
     * @ODM\Field(type="string", name="status")
     */
    protected $status;

    /**
     * @var \DateTime|null The user updated at
     *
     * @ODM\Field(type="date", name="_updatedAt")
     */
    protected $updatedAt;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="_updateBy")
     */
    protected $updatedBy;

    /**
     * Gets id.
     *
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Gets authToken.
     *
     * @return string
     */
    public function getAuthToken(): ?string
    {
        return $this->authToken;
    }

    /**
     * Gets companyName.
     *
     * @return string|null
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * Gets customerId.
     *
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * Gets customerType.
     *
     * @return string|null
     */
    public function getCustomerType(): ?string
    {
        return $this->customerType;
    }

    /**
     * Gets createdAt.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * Gets createdBy.
     *
     * @return string|null
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * Gets email.
     *
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Gets familyName.
     *
     * @return string|null
     */
    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    /**
     * Gets givenName.
     *
     * @return string|null
     */
    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    /**
     * Gets isAuthenticated.
     *
     * @return bool|null
     */
    public function getIsAuthenticated(): ?bool
    {
        return $this->isAuthenticated;
    }

    /**
     * Gets isOnMobile.
     *
     * @return bool|null
     */
    public function getIsOnMobile(): ?bool
    {
        return $this->isOnMobile;
    }

    /**
     * Gets isActivated.
     *
     * @return bool|null
     */
    public function getIsActivated(): ?bool
    {
        return $this->isActivated;
    }

    /**
     * Gets lastLogin.
     *
     * @return \DateTime|null
     */
    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    /**
     * Gets loginType.
     *
     * @return string|null
     */
    public function getLoginType(): ?string
    {
        return $this->loginType;
    }

    /**
     * @return OldPhoneNumber|null
     */
    public function getMobileNumber()
    {
        return $this->mobileNumber;
    }

    /**
     * @return OldPhoneNumber|null
     */
    public function getOfficeNumber()
    {
        return $this->officeNumber;
    }

    /**
     * Gets password.
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getPartnershipId(): ?string
    {
        return $this->partnershipId;
    }

    /**
     * @return string|null
     */
    public function getPreferredLanguage(): ?string
    {
        return $this->preferredLanguage;
    }

    /**
     * Gets roleId.
     *
     * @return string[]|null
     */
    public function getRoleId(): ?array
    {
        return $this->roleId;
    }

    /**
     * @return string|null
     */
    public function getSaleId(): ?string
    {
        return $this->saleId;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Gets updatedBy.
     *
     * @return string|null
     */
    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }
}
