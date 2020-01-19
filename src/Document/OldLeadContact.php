<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 19/1/19
 * Time: 10:12 AM.
 */

namespace App\Document;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class OldLeadContact
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var bool|null Do not contact.
     *
     * @ODM\Field(type="bool", name="do_not_contact")
     */
    protected $doNotContact;

    /**
     * @var string|null The email
     *
     * @ODM\Field(type="string", name="email")
     */
    protected $email;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldPhoneNumber",
     * name="fax_number")
     */
    protected $faxNumber;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldLeadAddress",
     * name="address")
     */
    protected $address;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldPhoneNumber",
     * name="mobile_number")
     */
    protected $mobileNumber;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldPhoneNumber",
     * name="phone_number")
     */
    protected $phoneNumber;

    /**
     * @var string|null The preferred contact method.
     *
     * @ODM\Field(type="string", name="prefer_contact_method")
     */
    protected $preferContactMethod;

    /**
     * @var string[]|null The social media account.
     *
     * @ODM\Field(type="collection", name="social_media_account")
     */
    protected $socialMediaAccount;

    /**
     * @var string|null The website.
     *
     * @ODM\Field(type="string", name="website")
     */
    protected $website;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return OldLeadAddress|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return bool|null
     */
    public function getDoNotContact(): ?bool
    {
        return $this->doNotContact;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string[]|null
     */
    public function getSocialMediaAccount(): ?array
    {
        return $this->socialMediaAccount;
    }

    /**
     * @return string|null
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * @return OldPhoneNumber|null
     */
    public function getFaxNumber()
    {
        return $this->faxNumber;
    }

    /**
     * @return OldPhoneNumber|null
     */
    public function getMobileNumber()
    {
        return $this->mobileNumber;
    }

    /**
     * @return OldPhoneNumber | null
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @return string|null
     */
    public function getPreferContactMethod(): ?string
    {
        return $this->preferContactMethod;
    }
}
