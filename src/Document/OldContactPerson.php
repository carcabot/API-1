<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 2/1/19
 * Time: 5:20 PM.
 */

namespace App\Document;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class OldContactPerson
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string|null An additional name for a Person, can be used for a middle name.
     *
     * @ODM\Field(type="string", name="middle_name")
     */
    protected $additionalName;

    /**
     * @var string|null An alias for the item
     *
     * @ODM\Field(type="string", name="nick_name")
     */
    protected $alternateName;

    /**
     * @var string|null The company name
     *
     * @ODM\Field(type="string", name="company_name")
     */
    protected $companyName;

    /**
     * @var string|null The company name 2
     *
     * @ODM\Field(type="string", name="company_name2")
     */
    protected $companyName2;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldContact",
     * name="contact")
     */
    protected $contact;

    /**
     * @var string[]|null The contract.
     *
     * @ODM\Field(type="collection", name="contract")
     */
    protected $contract;

    /**
     * @var string|null The country origin
     *
     * @ODM\Field(type="string", name="country_origin")
     */
    protected $countryOrigin;

    /**
     * @var string[]|null The customer account.
     *
     * @ODM\Field(type="collection", name="customer_account")
     */
    protected $customerAccount;

    /**
     * @var string|null The designation
     *
     * @ODM\Field(type="string", name="designation")
     */
    protected $designation;

    /**
     * @var \DateTime|null The date of birth
     *
     * @ODM\Field(type="date", name="date_of_birth")
     */
    protected $dateOfBirth;

    /**
     * @var \DateTime|null The date of death
     *
     * @ODM\Field(type="date", name="date_of_death")
     */
    protected $dateOfDeath;

    /**
     * @var string|null Family name. In the U.S., the last name of an Person. This can be used along with givenName instead of the name property.
     *
     * @ODM\Field(type="string", name="last_name")
     */
    protected $familyName;

    /**
     * @var \DateTime|null The found date
     *
     * @ODM\Field(type="date", name="found_date")
     */
    protected $foundDate;

    /**
     * @var string|null The gender
     *
     * @ODM\Field(type="string", name="gender")
     */
    protected $gender;

    /**
     * @var string|null Given name. In the U.S., the first name of a Person. This can be used along with familyName instead of the name property.
     *
     * @ODM\Field(type="string", name="first_name")
     */
    protected $givenName;

    /**
     * @var string|null An honorific prefix preceding a Person's name such as Dr/Mrs/Mr.
     *
     * @ODM\Field(type="string", name="salutation")
     */
    protected $honorificPrefix;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldIdentification",
     * name="identity")
     */
    protected $identification;

    /**
     * @var string|null The industry
     *
     * @ODM\Field(type="string", name="industry")
     */
    protected $industry;

    /**
     * @var string|null The initial
     *
     * @ODM\Field(type="string", name="initial")
     */
    protected $initial;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldLeadContact",
     * name="contact")
     */
    protected $leadContact;

    /**
     * @var \DateTime|null The liquidation date
     *
     * @ODM\Field(type="date", name="liquidation_date")
     */
    protected $liquidationDate;

    /**
     * @var string|null The martial status
     *
     * @ODM\Field(type="string", name="martial_status")
     */
    protected $martialStatus;

    /**
     * @var string|null The full name
     *
     * @ODM\Field(type="string", name="full_name")
     */
    protected $name;

    /**
     * @var string|null The nationality
     *
     * @ODM\Field(type="string", name="nationality")
     */
    protected $nationality;

    /**
     * @var string|null The place of birth
     *
     * @ODM\Field(type="string", name="place_of_birth")
     */
    protected $placeOfBirth;

    /**
     * @var string[]|null The relationship.
     *
     * @ODM\Field(type="collection", name="relationship")
     */
    protected $relationship;

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
    public function getAdditionalName(): ?string
    {
        return $this->additionalName;
    }

    /**
     * @return string|null
     */
    public function getAlternateName(): ?string
    {
        return $this->alternateName;
    }

    /**
     * @return string|null
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * @return string|null
     */
    public function getCompanyName2(): ?string
    {
        return $this->companyName2;
    }

    /**
     * @return OldContact|null
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @return string[]|null
     */
    public function getContract(): ?array
    {
        return $this->contract;
    }

    /**
     * @return string|null
     */
    public function getCountryOrigin(): ?string
    {
        return $this->countryOrigin;
    }

    /**
     * @return string[]|null
     */
    public function getCustomerAccount(): ?array
    {
        return $this->customerAccount;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateOfBirth(): ?\DateTime
    {
        return $this->dateOfBirth;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateOfDeath(): ?\DateTime
    {
        return $this->dateOfDeath;
    }

    /**
     * @return string|null
     */
    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    /**
     * @return string|null
     */
    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    /**
     * @return \DateTime|null
     */
    public function getFoundDate(): ?\DateTime
    {
        return $this->foundDate;
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @return string|null
     */
    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    /**
     * @return string|null
     */
    public function getHonorificPrefix(): ?string
    {
        return $this->honorificPrefix;
    }

    /**
     * @return OldIdentification|null
     */
    public function getIdentification()
    {
        return $this->identification;
    }

    /**
     * @return string|null
     */
    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    /**
     * @return string|null
     */
    public function getInitial(): ?string
    {
        return $this->initial;
    }

    /**
     * @return OldLeadContact|null
     */
    public function getLeadContact()
    {
        return $this->leadContact;
    }

    /**
     * @return \DateTime|null
     */
    public function getLiquidationDate(): ?\DateTime
    {
        return $this->liquidationDate;
    }

    /**
     * @return string|null
     */
    public function getMartialStatus(): ?string
    {
        return $this->martialStatus;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    /**
     * @return string|null
     */
    public function getPlaceOfBirth(): ?string
    {
        return $this->placeOfBirth;
    }

    /**
     * Get Relationship.
     *
     * @return string[]|null
     */
    public function getRelationship(): ?array
    {
        return $this->relationship;
    }
}
