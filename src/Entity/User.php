<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\AccountType;
use App\Enum\TwoFactorAuthenticationStatus;
use App\Enum\TwoFactorAuthenticationType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A user.
 *
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"user_read"}},
 *     "denormalization_context"={"groups"={"user_write"}},
 *     "filters"={
 *         "user.boolean",
 *         "user.date",
 *         "user.json_search",
 *         "user.order",
 *         "user.search",
 *     },
 * })
 */
class User implements UserInterface
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
     * @var BridgeUser|null The bridge user for old version.
     *
     * @ORM\OneToOne(targetEntity="BridgeUser", mappedBy="user")
     * @ApiProperty()
     */
    protected $bridgeUser;

    /**
     * @var CustomerAccount Basic unit of information about a customer.
     *
     * @ORM\OneToOne(targetEntity="CustomerAccount", mappedBy="user", cascade={"persist"})
     * @ApiProperty()
     */
    protected $customerAccount;

    /**
     * @var \DateTime|null The date when the user was activated.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $dateActivated;

    /**
     * @var \DateTime|null The last time user was logged on.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $dateLastLogon;

    /**
     * @var string|null Email address.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     * @ApiProperty(iri="http://schema.org/email")
     */
    protected $email;

    /**
     * @var string[] The expo push notification tokens.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $expoPushNotificationTokens;

    /**
     * @var bool|null Indicates whether user has logged in via mobile app at least once.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $mobileDeviceLogin;

    /**
     * @var Collection<UserLoginHistory>
     *
     * @ORM\OneToMany(targetEntity="UserLoginHistory", cascade={"persist"}, mappedBy="user")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $loginHistories;

    /**
     * @var string|null The user's password.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $password;

    /**
     * @var string|null
     *
     * @ApiProperty()
     */
    protected $plainPassword;

    /**
     * @var string[] The user's enabled modules.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $modules;

    /**
     * @var string[] The user's roles.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $roles;

    /**
     * @var bool The indicator for 2FA.
     *
     * @ORM\Column(type="boolean")
     * @ApiProperty()
     */
    private $twoFactorAuthentication = false;

    /**
     * @var string|null Current 2FA code.
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     * @ApiProperty()
     */
    private $twoFactorAuthenticationCode;

    /**
     * @var string|null The recipient of the 2FA code.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty()
     */
    private $twoFactorAuthenticationRecipient;

    /**
     * @var TwoFactorAuthenticationStatus|null The 2FA status.
     *
     * @ORM\Column(type="two_factor_authentication_status_enum", nullable=true)
     * @ApiProperty()
     */
    private $twoFactorAuthenticationStatus;

    /**
     * @var TwoFactorAuthenticationType|null The 2FA type. (Email, Mobile, etc.)
     *
     * @ORM\Column(type="two_factor_authentication_type_enum", nullable=true)
     * @ApiProperty()
     */
    private $twoFactorAuthenticationType;

    /**
     * @var string|null Username.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     * @ApiProperty()
     */
    protected $username;

    /**
     * @var Collection<Role>
     *
     * @ORM\ManyToMany(targetEntity="Role", cascade={"persist"}, inversedBy="users")
     * @ORM\JoinTable(
     *  joinColumns={@ORM\JoinColumn(name="user_id", onDelete="CASCADE")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="role_id", onDelete="CASCADE")}
     * )
     *
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $userRoles;

    public function __construct()
    {
        $this->expoPushNotificationTokens = [];
        $this->loginHistories = new ArrayCollection();
        $this->modules = [];
        $this->roles = [
            'ROLE_USER',
        ];
        $this->userRoles = new ArrayCollection();
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
     * Sets bridgeUser.
     *
     * @param BridgeUser|null $bridgeUser
     *
     * @return $this
     */
    public function setBridgeUser(?BridgeUser $bridgeUser)
    {
        $this->bridgeUser = $bridgeUser;

        return $this;
    }

    /**
     * Gets bridgeUser.
     *
     * @return BridgeUser|null
     */
    public function getBridgeUser(): ?BridgeUser
    {
        return $this->bridgeUser;
    }

    /**
     * Sets customerAccount.
     *
     * @param CustomerAccount $customerAccount
     *
     * @return $this
     */
    public function setCustomerAccount(CustomerAccount $customerAccount)
    {
        $this->customerAccount = $customerAccount;
        $customerAccount->setUser($this);

        return $this;
    }

    /**
     * Gets customerAccount.
     *
     * @return CustomerAccount
     */
    public function getCustomerAccount(): CustomerAccount
    {
        return $this->customerAccount;
    }

    /**
     * Sets dateActivated.
     *
     * @param \DateTime|null $dateActivated
     *
     * @return $this
     */
    public function setDateActivated(?\DateTime $dateActivated)
    {
        $this->dateActivated = $dateActivated;

        return $this;
    }

    /**
     * Gets dateActivated.
     *
     * @return \DateTime|null
     */
    public function getDateActivated(): ?\DateTime
    {
        return $this->dateActivated;
    }

    /**
     * Sets dateLastLogon.
     *
     * @param \DateTime|null $dateLastLogon
     *
     * @return $this
     */
    public function setDateLastLogon(?\DateTime $dateLastLogon)
    {
        $this->dateLastLogon = $dateLastLogon;

        return $this;
    }

    /**
     * Gets dateLastLogon.
     *
     * @return \DateTime|null
     */
    public function getDateLastLogon(): ?\DateTime
    {
        return $this->dateLastLogon;
    }

    /**
     * Sets email.
     *
     * @param string|null $email
     *
     * @return $this
     */
    public function setEmail(?string $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Gets email.
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Adds expoPushNotificationToken.
     *
     * @param string $expoPushNotificationToken
     *
     * @return $this
     */
    public function addExpoPushNotificationToken(string $expoPushNotificationToken)
    {
        $this->expoPushNotificationTokens[] = $expoPushNotificationToken;

        return $this;
    }

    /**
     * Removes expoPushNotificationToken.
     *
     * @param string $expoPushNotificationToken
     *
     * @return $this
     */
    public function removeExpoPushNotificationToken(string $expoPushNotificationToken)
    {
        if (false !== ($key = \array_search($expoPushNotificationToken, $this->expoPushNotificationTokens, true))) {
            \array_splice($this->expoPushNotificationTokens, $key, 1);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpoPushNotificationTokens(): array
    {
        return $this->expoPushNotificationTokens;
    }

    /**
     * Sets mobileDeviceLogin.
     *
     * @param bool|null $mobileDeviceLogin
     *
     * @return $this
     */
    public function setMobileDeviceLogin(?bool $mobileDeviceLogin)
    {
        $this->mobileDeviceLogin = $mobileDeviceLogin;

        return $this;
    }

    /**
     * Gets mobileDeviceLogin.
     *
     * @return bool|null
     */
    public function hasMobileDeviceLogin(): ?bool
    {
        return $this->mobileDeviceLogin;
    }

    /**
     * Gets loginHistories.
     *
     * @return UserLoginHistory[]
     */
    public function getLoginHistories(): array
    {
        return $this->loginHistories->getValues();
    }

    /**
     * Sets password.
     *
     * @param string|null $password
     *
     * @return $this
     */
    public function setPassword(?string $password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): string
    {
        return $this->password ?? '';
    }

    /**
     * Sets plainPassword.
     *
     * @param string|null $plainPassword
     *
     * @return $this
     */
    public function setPlainPassword(?string $plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * Gets plainPassword.
     *
     * @return string|null
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * Adds role.
     *
     * @param string $role
     *
     * @return $this
     */
    public function addRole(string $role)
    {
        $this->roles[] = $role;

        return $this;
    }

    /**
     * Removes role.
     *
     * @param string $role
     *
     * @return $this
     */
    public function removeRole(string $role)
    {
        if (false !== ($key = \array_search($role, $this->roles, true))) {
            \array_splice($this->roles, $key, 1);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Adds module.
     *
     * @param string $module
     *
     * @return $this
     */
    public function addModule(string $module)
    {
        $this->modules[] = $module;

        return $this;
    }

    /**
     * Removes module.
     *
     * @param string $module
     *
     * @return $this
     */
    public function removeModule(string $module)
    {
        if (false !== ($key = \array_search($module, $this->modules, true))) {
            \array_splice($this->modules, $key, 1);
        }

        return $this;
    }

    /**
     * Gets modules.
     *
     * @return string[]
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Sets username.
     *
     * @param string|null $username
     *
     * @return $this
     */
    public function setUsername(?string $username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Gets username.
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt(): ?string
    {
        // The bcrypt and argon2i algorithms don't require a separate salt.
        // You *may* need a real salt if you choose a different encoder.
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonExpired(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return null !== $this->dateActivated;
    }

    public function getCustomerName(): ?string
    {
        $customer = $this->getCustomerAccount();

        if (AccountType::CORPORATE === $customer->getType()->getValue() && null !== $customer->getCorporationDetails()) {
            return $customer->getCorporationDetails()->getName();
        } elseif (AccountType::INDIVIDUAL === $customer->getType()->getValue() && null !== $customer->getPersonDetails()) {
            return $customer->getPersonDetails()->getName();
        }

        return null;
    }

    /**
     * Adds userRole.
     *
     * @param Role $userRole
     *
     * @return $this
     */
    public function addUserRole(Role $userRole)
    {
        $this->userRoles[] = $userRole;

        return $this;
    }

    /**
     * Removes userRole.
     *
     * @param Role $userRole
     *
     * @return $this
     */
    public function removeUserRole(Role $userRole)
    {
        $this->userRoles->removeElement($userRole);

        return $this;
    }

    /**
     * Gets userRoles.
     *
     * @return Role []
     */
    public function getUserRoles(): array
    {
        return $this->userRoles->getValues();
    }

    /**
     * Gets twoFactorAuthentication.
     *
     * @return bool
     */
    public function hasTwoFactorAuthentication(): bool
    {
        return $this->twoFactorAuthentication;
    }

    /**
     * Sets twoFactorAuthentication.
     *
     * @param bool $twoFactorAuthentication
     */
    public function setTwoFactorAuthentication(bool $twoFactorAuthentication): void
    {
        $this->twoFactorAuthentication = $twoFactorAuthentication;
    }

    /**
     * Gets twoFactorAuthenticationCode.
     *
     * @return string|null
     */
    public function getTwoFactorAuthenticationCode(): ?string
    {
        return $this->twoFactorAuthenticationCode;
    }

    /**
     * Sets twoFactorAuthenticationCode.
     *
     * @param string|null $twoFactorAuthenticationCode
     */
    public function setTwoFactorAuthenticationCode(?string $twoFactorAuthenticationCode): void
    {
        $this->twoFactorAuthenticationCode = $twoFactorAuthenticationCode;
    }

    /**
     * Gets twoFactorAuthenticationType.
     *
     * @return TwoFactorAuthenticationType|null
     */
    public function getTwoFactorAuthenticationType(): ?TwoFactorAuthenticationType
    {
        return $this->twoFactorAuthenticationType;
    }

    /**
     * Sets twoFactorAuthenticationType.
     *
     * @param TwoFactorAuthenticationType|null $twoFactorAuthenticationType
     */
    public function setTwoFactorAuthenticationType(?TwoFactorAuthenticationType $twoFactorAuthenticationType): void
    {
        $this->twoFactorAuthenticationType = $twoFactorAuthenticationType;
    }

    /**
     * Gets twoFactorAuthenticationRecipient.
     *
     * @return string|null
     */
    public function getTwoFactorAuthenticationRecipient(): ?string
    {
        return $this->twoFactorAuthenticationRecipient;
    }

    /**
     * Sets twoFactorAuthenticationRecipient.
     *
     * @param string|null $twoFactorAuthenticationRecipient
     */
    public function setTwoFactorAuthenticationRecipient(?string $twoFactorAuthenticationRecipient): void
    {
        $this->twoFactorAuthenticationRecipient = $twoFactorAuthenticationRecipient;
    }

    /**
     * Gets twoFactorAuthenticationStatus.
     *
     * @return TwoFactorAuthenticationStatus|null
     */
    public function getTwoFactorAuthenticationStatus(): ?TwoFactorAuthenticationStatus
    {
        return $this->twoFactorAuthenticationStatus;
    }

    /**
     * Sets twoFactorAuthenticationStatus.
     *
     * @param TwoFactorAuthenticationStatus $twoFactorAuthenticationStatus
     */
    public function setTwoFactorAuthenticationStatus(TwoFactorAuthenticationStatus $twoFactorAuthenticationStatus): void
    {
        $this->twoFactorAuthenticationStatus = $twoFactorAuthenticationStatus;
    }
}
