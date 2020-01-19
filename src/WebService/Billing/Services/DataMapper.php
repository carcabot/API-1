<?php

declare(strict_types=1);

namespace App\WebService\Billing\Services;

use App\Entity\DigitalDocument;
use App\Entity\PostalAddress;
use App\Enum\ContractType;
use App\Enum\DwellingType;
use App\Enum\Industry;
use App\Enum\PaymentMode;
use App\Enum\PostalAddressType;
use App\Enum\RefundType;
use Vich\UploaderBundle\Storage\StorageInterface as UploaderStorageInterface;

class DataMapper
{
    /**
     * @var UploaderStorageInterface
     */
    private $uploaderStorage;

    /**
     * @param UploaderStorageInterface $uploaderStorage
     */
    public function __construct(UploaderStorageInterface $uploaderStorage)
    {
        $this->uploaderStorage = $uploaderStorage;
    }

    /**
     * Maps address for application request.
     *
     * @param PostalAddress $address
     *
     * @return array
     */
    public function mapAddressFields(PostalAddress $address): array
    {
        $addressString = '';
        if (null !== $address->getHouseNumber()) {
            $addressString .= $address->getHouseNumber().' ';
        }

        if (null !== $address->getStreetAddress()) {
            $addressString .= $address->getStreetAddress().' ';
        }

        if (null !== $address->getBuildingName()) {
            $addressString .= $address->getBuildingName();
        }
        $addressString = \strtoupper(\trim($addressString));

        $unitNoString = '';
        if (null !== $address->getFloor() && '' !== \trim($address->getFloor())) {
            $unitNoString = ' '.$address->getFloor();
        }

        if (null !== $address->getUnitNumber() && '' !== \trim($address->getUnitNumber())) {
            $unitNoString .= '-'.$address->getUnitNumber();
        }

        if ('' !== $unitNoString) {
            $unitNoString = \substr_replace($unitNoString, '#', 0, 1);
        }
        $unitNoString = \strtoupper(\trim($unitNoString));

        $addressCountry = null;
        if (null !== $address->getAddressCountry()) {
            $addressCountry = $address->getAddressCountry();
        }

        $addressState = null;
        if (null !== $address->getAddressRegion()) {
            $addressState = $address->getAddressRegion();
        }

        $addressPostalCode = null;
        if (null !== $address->getPostalCode()) {
            $addressPostalCode = $address->getPostalCode();
        }

        $addressData = [];
        if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
            $addressData = [
                'MailingAddress' => $addressString,
                'MailingUnitNumber' => $unitNoString,
                'MailingPostalCode' => $address->getPostalCode(),
            ];
        } elseif (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
            $addressData = [
                'PremisesAddress' => $addressString,
                'PremisesUnitNumber' => $unitNoString,
                'PremisesPostalCode' => $address->getPostalCode(),
            ];
        } elseif (PostalAddressType::REFUND_ADDRESS === $address->getType()->getValue()) {
            $addressData = [
                'RefundAddressCountry' => $addressCountry,
                'RefundAddressState' => $addressState,
                'RefundAddressCity' => $addressState,
                'RefundPostalCode' => $addressPostalCode,
                'RefundAddressLine1' => $addressString,
                'RefundAddressLine2' => $unitNoString,
            ];
        }

        return $addressData;
    }

    /**
     * Map attachments for contract application.
     *
     * @param DigitalDocument $attachment
     *
     * @return array
     */
    public function mapAttachment(DigitalDocument $attachment)
    {
        $fileUrl = null;

        if (null !== $attachment->getContentPath()) {
            $fileUrl = $this->uploaderStorage->resolveUri($attachment, 'contentFile');
        } elseif (null !== $attachment->getUrl()) {
            $fileUrl = $attachment->getUrl();
        }

        if (null !== $fileUrl) {
            $ch = \curl_init($fileUrl);
            \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = \curl_exec($ch);

            if (false !== $result) {
                $contentType = \curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                \curl_close($ch);

                $filename = $attachment->getName();
                if (null === $filename) {
                    $urlParts = \parse_url($fileUrl);
                    $filename = \basename($urlParts['path']);
                }

                return [
                    'Attachment' => [
                        'FileName' => $filename,
                        'ContentType' => $contentType,
                        'FileBytes' => \base64_encode($result),
                    ],
                ];
            }
        }

        return [];
    }

    /**
     * Map contact points.
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

            if (\count($contactPoint->getFaxNumbers()) > 0) {
                $contactData['fax_number'] = [
                    'country_code' => '+'.$contactPoint->getFaxNumbers()[0]->getCountryCode(),
                    'number' => $contactPoint->getFaxNumbers()[0]->getNationalNumber(),
                ];
            }
        }

        return $contactData;
    }

    /**
     * Maps contract subtype to the enums accepted by billing provider.
     *
     * @param string|null $contractSubtype
     *
     * @return string|null
     */
    public function mapContractSubtype(?string $contractSubtype)
    {
        switch ($contractSubtype) {
            case DwellingType::ONE_ROOM_FLAT_HDB:
                return '1-Room HDB';
            case DwellingType::TWO_ROOM_FLAT_HDB:
                return '2-Room HDB';
            case DwellingType::THREE_ROOM_FLAT_HDB:
                return '3-Room HDB';
            case DwellingType::FOUR_ROOM_FLAT_HDB:
                return '4-Room HDB';
            case DwellingType::FIVE_ROOM_FLAT_HDB:
            case DwellingType::EXECUTIVE_FLAT_HDB:
                return '5-Room/ Executive - HDB';
            case DwellingType::CONDOMINIUM:
                return 'Condo';
            case DwellingType::LANDED:
                return 'Landed';
            case Industry::CHARITABLE_ORGANISATIONS:
                return 'Charitable Organisations';
            case Industry::CONSTRUCTION:
                return 'Construction';
            case Industry::DORMITORIES:
                return 'Dormitories';
            case Industry::EDUCATIONAL_INSTITUTIONS:
                return 'Educational Institutions';
            case Industry::ELECTRONICS_SEMICONDUCTORS:
                return 'Electronics/ Semiconductors';
            case Industry::F_B_OUTLETS:
                return 'F&B Outlets';
            case Industry::HOTELS:
                return 'Hotels';
            case Industry::LOGISTICS:
                return 'Logistics';
            case Industry::MCST_CONDOS:
                return 'MCST - Condos';
            case Industry::OFFICE_REAL_ESTATE:
                return 'Office Real Estate';
            case Industry::OTHER_HEAVY_MANUFACTURING:
                return 'Other Heavy Manufacturing';
            case Industry::OTHER_LIGHT_MANUFACTURING:
                return 'Other Light Manufacturing';
            case Industry::OTHERS:
                return 'Others';
            case Industry::PHARMACEUTICALS:
                return 'Pharmaceuticals';
            case Industry::PORTS:
                return 'Ports';
            case Industry::PRECISION_INDUSTRIES:
                return 'Precision Industries';
            case Industry::REFINERIES_PETROCHEMICALS:
                return 'Refineries & Petrochemicals';
            case Industry::RETAIL_OUTLETS:
                return 'Retail outlets';
            case Industry::SHOPPING_MALLS:
                return 'Shopping Malls';
            case Industry::TRANSPORTATION:
                return 'Transportation';
            default:
                return null;
        }
    }

    /**
     * Maps contract type to the enums accepted by billing provider.
     *
     * @param ContractType|null $contractType
     *
     * @return string|null
     */
    public function mapContractType(?ContractType $contractType)
    {
        if (null === $contractType) {
            return null;
        }

        $contractTypeString = $contractType->getValue();

        switch ($contractTypeString) {
            case ContractType::COMMERCIAL:
                return 'C';
            case ContractType::RESIDENTIAL:
                return 'R';
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
                return \strtoupper(\trim($identifier->getValue()));
            }
        }

        return null;
    }

    /**
     * Maps refund type to the enums accepted by billing provider.
     *
     * @param RefundType $refundType
     *
     * @return string|null
     */
    public function mapRefundType(RefundType $refundType)
    {
        $refundTypeString = $refundType->getValue();

        switch ($refundTypeString) {
            case RefundType::BILL_OFFSET:
                return 'O';
            case RefundType::FULL_REFUND:
                return 'R';
            default:
                return null;
        }
    }

    /**
     * Maps rccs status to standard statuses.
     *
     * @param string|null $rccsStatus
     *
     * @return string|null
     */
    public function mapRCCSStatus(?string $rccsStatus)
    {
        switch ($rccsStatus) {
            case 'Active':
                return 'ACTIVE';
            case 'Cancelled':
                return 'CANCELLED';
            case 'Disabled':
                return 'DISABLED';
            case 'Pending Approval':
                return 'PENDING_APPROVAL';
            case 'Pending Effective':
                return 'PENDING_EFFECTIVE';
            case 'Pending Review':
                return 'PENDING_REVIEW';
            case 'Pending Terminate':
                return 'PENDING_TERMINATION';
            case 'Terminated':
                return 'TERMINATED';
            default:
                return null;
        }
    }

    /**
     * Maps giro status to standard statuses.
     *
     * @param string|null $giroStatus
     *
     * @return string|null
     */
    public function mapGiroStatus(?string $giroStatus)
    {
        switch ($giroStatus) {
            case 'Active':
                return 'ACTIVE';
                break;
            case 'Approved':
                return 'APPROVED';
                break;
            case 'Cancelled':
                return 'CANCELLED';
                break;
            case 'Pending Bank Approval':
                return 'PENDING_BANK_APPROVAL';
                break;
            case 'Pending Document Review':
                return 'PENDING_DOCUMENT_REVIEW';
                break;
            case 'Pending Effective':
                return 'PENDING_EFFECTIVE';
                break;
            case 'Pending Internal Processing':
                return 'PENDING_INTERNAL_PROCESSING';
                break;
            case 'Pending Termination':
                return 'PENDING_TERMINATION';
                break;
            case 'Rejected':
                return 'REJECTED';
                break;
            case 'Terminated':
                return 'TERMINATED';
                break;
            case 'Withdrawn':
                return 'WITHDRAWN';
                break;
            default:
                null;
        }
    }

    /**
     * Maps payment mode.
     *
     * @param string|null $paymentMode
     *
     * @return string|null
     */
    public function mapPaymentMode(?string $paymentMode)
    {
        switch ($paymentMode) {
            case 'CASH':
                return PaymentMode::MANUAL;
            case 'RCCS':
                return PaymentMode::RCCS;
            case 'GIRO':
                return PaymentMode::GIRO;
            default:
                return null;
        }
    }
}
