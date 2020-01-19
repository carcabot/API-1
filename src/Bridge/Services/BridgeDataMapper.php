<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Entity\Lead;
use App\Entity\PostalAddress;
use App\Enum\ApplicationRequestType;
use App\Enum\DwellingType;
use App\Enum\NoteType;
use App\Enum\PostalAddressType;
use App\Enum\Source;
use GuzzleHttp\Client as GuzzleClient;
use League\Uri\Schemes\Http as HttpUri;

final class BridgeDataMapper
{
    /**
     * @var string
     */
    private $bridgeApiUrl;

    /**
     * @var SettingsApi
     */
    private $settingsApi;

    /**
     * @var UploadApi
     */
    private $uploadApi;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @param string      $bridgeApiUrl
     * @param SettingsApi $settingsApi
     * @param UploadApi   $uploadApi
     */
    public function __construct(string $bridgeApiUrl, SettingsApi $settingsApi, UploadApi $uploadApi)
    {
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->settingsApi = $settingsApi;
        $this->uploadApi = $uploadApi;
        $this->client = new GuzzleClient();
        $this->baseUri = HttpUri::createFromString($this->bridgeApiUrl);
    }

    /**
     * Maps contract addresses to old version.
     *
     * @param array $addresses
     *
     * @return array
     */
    public function mapContractAddresses(array $addresses)
    {
        $premiseAddress = [];
        $mailingAddress = [];

        foreach ($addresses as $address) {
            $addressData = [
                'address_source' => 'CONTRACT',
                'building_name' => $address->getBuildingName(),
                'city' => $address->getAddressLocality(),
                'country' => $address->getAddressCountry(),
                'floor' => $address->getFloor(),
                'house_no' => $address->getHouseNumber(),
                'postal_code' => $address->getPostalCode(),
                'street' => $address->getStreetAddress(),
                'unit_no' => $address->getUnitNumber(),
            ];

            if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                $mailingAddress = $addressData;
            } elseif (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                $premiseAddress = $addressData;
            }
        }

        return [$premiseAddress, $mailingAddress];
    }

    /**
     * Maps lead source to old version.
     *
     * @param string $source
     *
     * @return string|null
     */
    public function mapLeadSource(string $source)
    {
        $mapSource = null;

        switch ($source) {
            case Source::ADVERTISEMENT:
                $mapSource = 'ADVERTISING';
                break;
            case Source::MEDIA:
                $mapSource = 'MEDIA_PRESS';
                break;
            case Source::PARTNERSHIP_PORTAL:
                $mapSource = 'PARTNER';
                break;
            case Source::STAFF:
                $mapSource = 'PERSONAL_STAFF';
                break;
            default:
                $mapSource = $source;
                break;
        }

        if (!\in_array($mapSource, [
            'ADVERTISING',
            'ASSOCIATION',
            'BANK',
            'CAMPAIGN',
            'DIGITAL_MARKETING',
            'DIRECT_MARKETING',
            'EMAIL',
            'EVENT',
            'EXTERNAL_LIST',
            'HOMEPAGE',
            'LEAD',
            'MEDIA_PRESS',
            'PARTNER',
            'PERSONAL_STAFF',
            'REFERRAL',
            'TELEMARKETING',
            'TENDER',
        ], true)) {
            $mapSource = null;
        }

        return $mapSource;
    }

    /**
     * Maps lead address to old version.
     *
     * @param PostalAddress $address
     *
     * @return array
     */
    public function mapLeadAddress(PostalAddress $address)
    {
        $addressType = $address->getType()->getValue();

        switch ($addressType) {
            case PostalAddressType::CORRESPONDENCE_ADDRESS:
                $type = 'CORRES_ADDRESS';
                break;
            case PostalAddressType::PREMISE_ADDRESS:
                $type = 'PREMISE_ADDRESS';
                break;
            default:
                $type = 'CORRES_ADDRESS';
                break;
        }

        return [
            'address_type' => $type,
            'building_name' => $address->getBuildingName(),
            'city' => $address->getAddressLocality(),
            'country' => $address->getAddressCountry(),
            'floor' => $address->getFloor(),
            'house_no' => $address->getHouseNumber(),
            'post_code' => $address->getPostalCode(),
            'street' => $address->getStreetAddress(),
            'unit_no' => $address->getUnitNumber(),
        ];
    }

    /**
     * Map contact points to old version.
     *
     * @param array $contactPoints
     *
     * @return array
     */
    public function mapContactPoints(array $contactPoints)
    {
        $contactData = [];

        foreach ($contactPoints as $contactPoint) {
            if (\count($contactPoint->getEmails()) > 0) {
                $contactData['email'] = $contactPoint->getEmails()[0];
            }

            if (\count($contactPoint->getFaxNumbers()) > 0) {
                $contactData['fax_number'] = [
                    'country_code' => '+'.$contactPoint->getFaxNumbers()[0]->getCountryCode(),
                    'number' => $contactPoint->getFaxNumbers()[0]->getNationalNumber(),
                ];
            }

            if (\count($contactPoint->getMobilePhoneNumbers()) > 0) {
                $contactData['mobile_number'] = [
                    'country_code' => '+'.$contactPoint->getMobilePhoneNumbers()[0]->getCountryCode(),
                    'number' => $contactPoint->getMobilePhoneNumbers()[0]->getNationalNumber(),
                ];
            }

            if (\count($contactPoint->getTelephoneNumbers()) > 0) {
                $contactData['phone_number'] = [
                    'country_code' => '+'.$contactPoint->getTelephoneNumbers()[0]->getCountryCode(),
                    'number' => $contactPoint->getTelephoneNumbers()[0]->getNationalNumber(),
                ];
            }
        }

        return $contactData;
    }

    /**
     * Maps honorific prefix to old version.
     *
     * @param string|null $honorificPrefix
     *
     * @return string|null
     */
    public function mapHonorificPrefix(?string $honorificPrefix)
    {
        switch ($honorificPrefix) {
            case 'Dr':
                return 'DR';
            case 'Madam':
                return 'MDM';
            case 'Miss':
                return 'MISS';
            case 'Mr.':
                return 'MR';
            case 'Mrs.':
                return 'MRS';
            default:
                return null;
        }
    }

    /**
     * Maps identifier by key.
     *
     * @param array  $identifiers
     * @param string $key
     *
     * @return string|null
     */
    public function mapIdentifierByKey(array $identifiers, string $key)
    {
        $now = new \DateTime();

        foreach ($identifiers as $identifier) {
            if ($key === $identifier->getName()->getValue()
                && (null === $identifier->getValidThrough()
                    || $now < $identifier->getValidThrough())
            ) {
                return $identifier->getValue();
            }
        }

        return null;
    }

    /**
     * Denormalize social media urls to old version.
     *
     * @param array $urls
     *
     * @return array
     */
    public function denormalizeSocialMediaUrls(array $urls)
    {
        $socialMediaUrls = [];

        foreach ($urls as $url) {
            if (false !== \strpos($url, 'facebook.')) {
                $socialMediaUrls[] = [
                    'social_type' => 'FACEBOOK',
                    'social_url' => $url,
                ];
                continue;
            }

            if (false !== \strpos($url, 'twitter.')) {
                $socialMediaUrls[] = [
                    'social_type' => 'TWITTER',
                    'social_url' => $url,
                ];
                continue;
            }

            if (false !== \strpos($url, 'linkedin.')) {
                $socialMediaUrls[] = [
                    'social_type' => 'LINKEDIN',
                    'social_url' => $url,
                ];
                continue;
            }
        }

        return $socialMediaUrls;
    }

    /**
     * Maps attachments to old version.
     *
     * @param array       $attachments
     * @param string|null $key
     *
     * @return array
     */
    public function mapAttachments(array $attachments, ?string $key = null)
    {
        $uploadedAttachments = [];

        foreach ($attachments as $attachment) {
            $bridgeUploaded = $this->uploadApi->uploadFile($attachment, 'upload/quotation');

            if (null !== $bridgeUploaded) {
                if (null !== $key) {
                    if (!empty($bridgeUploaded[$key])) {
                        $uploadedAttachments[] = $bridgeUploaded[$key];
                    }
                } else {
                    $uploadedAttachments[] = $bridgeUploaded;
                }
            }
        }

        return $uploadedAttachments;
    }

    /**
     * Maps application request type to old version.
     *
     * @param string $applicationRequestType
     *
     * @return string
     */
    public function mapApplicationRequestType(string $applicationRequestType)
    {
        switch ($applicationRequestType) {
            case ApplicationRequestType::ACCOUNT_CLOSURE:
                return 'ACCOUNT_CLOSURE';
            case ApplicationRequestType::CONTRACT_APPLICATION:
                return 'CONTRACT_APP';
            case ApplicationRequestType::GIRO_TERMINATION:
                return 'GIRO_TERMINATION';
            case ApplicationRequestType::TRANSFER_OUT:
                return 'TRANSFER_OUT';
            default:
                return '';
        }
    }

    /**
     * Maps contract sub type to old version.
     *
     * @param string|null $contractSubType
     *
     * @return string|null
     */
    public function mapContractSubType(?string $contractSubType)
    {
        switch ($contractSubType) {
            case DwellingType::ONE_ROOM_FLAT_HDB:
                return 'ROOM1';
            case DwellingType::TWO_ROOM_FLAT_HDB:
                return 'ROOM2';
            case DwellingType::THREE_ROOM_FLAT_HDB:
                return 'ROOM3';
            case DwellingType::FOUR_ROOM_FLAT_HDB:
                return 'ROOM4';
            case DwellingType::FIVE_ROOM_FLAT_HDB:
                return 'ROOM5';
            case DwellingType::CONDOMINIUM:
                return 'CONDO';
            case DwellingType::LANDED:
                return 'LANDED';
            default:
                return $contractSubType;
        }
    }

    /**
     * Maps consumption unit.
     *
     * @param string|null $consumptionUnit
     *
     * @return string|null
     */
    public function mapConsumptionUnit(?string $consumptionUnit)
    {
        $unitOfMeasurements = $this->settingsApi->getGlobalConfiguration('unit_of_measurement');

        foreach ($unitOfMeasurements as $unitOfMeasurement) {
            if (\strtoupper($unitOfMeasurement['key']) === \strtoupper($consumptionUnit)) {
                return $unitOfMeasurement['_id'];
            }
        }

        return null;
    }

    /**
     * Maps lead note type to old version.
     *
     * @param string $type
     *
     * @return string
     */
    public function mapLeadNoteType(string $type)
    {
        switch ($type) {
            case NoteType::FOLLOW_UP:
                return 'FOLLOW_UP';
            case NoteType::REJECT_REASON:
                return 'REJECTION';
            case NoteType::TASK:
                return 'TASK';
            default:
                return 'GENERAL';
        }
    }
}
