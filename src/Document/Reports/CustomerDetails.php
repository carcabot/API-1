<?php

declare(strict_types=1);

namespace App\Document\Reports;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class CustomerDetails
{
    /**
     * @var string|null the account number of the customer
     *
     * @ODM\Field(type="string")
     */
    protected $customerId;

    /**
     * @var string|null the identification number of the customer
     *
     * @ODM\Field(type="string")
     */
    protected $identificationNumber;

    /**
     * @var string|null the category of the customer applying for this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $category;

    /**
     * @var string|null the salutation the customer
     *
     * @ODM\Field(type="string")
     */
    protected $salutation;

    /**
     * @var string|null First name of the Customer
     *
     * @ODM\Field(type="string")
     */
    protected $firstName;

    /**
     * @var string|null Middle name of the customer
     *
     * @ODM\Field(type="string")
     */
    protected $middleName;

    /**
     * @var string|null Last name of the customer
     *
     * @ODM\Field(type="string")
     */
    protected $lastName;

    /**
     * @var string|null Full name of the customer
     *
     * @ODM\Field(type="string")
     */
    protected $fullName;

    /**
     * @var string|null mobile number of the customer
     *
     * @ODM\Field(type="string")
     */
    protected $mobileNumber;

    /**
     * @var string|null phone number of the customer
     *
     * @ODM\Field(type="string")
     */
    protected $phoneNumber;

    /**
     * @var string|null email of the customer
     *
     * @ODM\Field(type="string")
     */
    protected $email;

    /**
     * @var string|null status of the customer
     *
     * @ODM\Field(type="string")
     */
    protected $status;

    /**
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @param string|null $customerId
     */
    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    /**
     * @return string|null
     */
    public function getIdentificationNumber(): ?string
    {
        return $this->identificationNumber;
    }

    /**
     * @param string|null $identificationNumber
     */
    public function setIdentificationNumber(?string $identificationNumber): void
    {
        $this->identificationNumber = $identificationNumber;
    }

    /**
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @param string|null $category
     */
    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    /**
     * @return string|null
     */
    public function getSalutation(): ?string
    {
        return $this->salutation;
    }

    /**
     * @param string|null $salutation
     */
    public function setSalutation(?string $salutation): void
    {
        $this->salutation = $salutation;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     */
    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string|null
     */
    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    /**
     * @param string|null $middleName
     */
    public function setMiddleName(?string $middleName): void
    {
        $this->middleName = $middleName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     */
    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string|null
     */
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * @param string|null $fullName
     */
    public function setFullName(?string $fullName): void
    {
        $this->fullName = $fullName;
    }

    /**
     * @return string|null
     */
    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    /**
     * @param string|null $mobileNumber
     */
    public function setMobileNumber(?string $mobileNumber): void
    {
        $this->mobileNumber = $mobileNumber;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     */
    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }
}
