<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 3/1/19
 * Time: 12:01 PM.
 */

namespace App\Document;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class OldContact
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string[]|null The address.
     *
     * @ODM\Field(type="collection", name="address")
     */
    protected $address;

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
     * @return string[]|null
     */
    public function getAddress(): ?array
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
