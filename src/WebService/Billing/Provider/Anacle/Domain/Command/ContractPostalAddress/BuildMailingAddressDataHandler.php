<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\ContractPostalAddress;

use App\Enum\AccountType;
use App\Enum\PostalAddressType;
use App\WebService\Billing\Services\DataMapper;

class BuildMailingAddressDataHandler
{
    /**
     * @var DataMapper
     */
    private $dataMapper;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param DataMapper $dataMapper
     */
    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
        $this->timezone = new \DateTimeZone('Asia/Singapore');
    }

    public function handle(BuildMailingAddressData $command): array
    {
        $contractPostalAddress = $command->getContractPostalAddress();
        $contract = $contractPostalAddress->getContract();
        $customerAccount = $contract->getCustomer();
        $contactPoints = [];
        $contractNumber = null;
        $validFrom = null;
        $preferredEndDate = null;
        $customerName = null;
        $emailAddress = null;

        if (AccountType::CORPORATE === $customerAccount->getType()->getValue()) {
            $contactPersonDetails = null;
            $corporationDetails = $customerAccount->getCorporationDetails();

            if (null !== $corporationDetails) {
                $customerName = \strtoupper($corporationDetails->getName());
                $contactPoints = $this->dataMapper->mapContactPoints($corporationDetails->getContactPoints());
            }
        } else {
            $personDetails = $customerAccount->getPersonDetails();

            if (null !== $personDetails) {
                $customerName = \strtoupper($personDetails->getName());
                $contactPoints = $this->dataMapper->mapContactPoints($personDetails->getContactPoints());
            }
        }

        $addressData = [];
        $postalAddress = $contractPostalAddress->getAddress();
        if (PostalAddressType::MAILING_ADDRESS === $postalAddress->getType()->getValue()) {
            if (null !== $contractPostalAddress->getValidFrom()) {
                $validFrom = $contractPostalAddress->getValidFrom();
                $validFrom->setTimezone($this->timezone);
                $validFrom = $validFrom->format('Ymd');
            }

            $addressCountry = null;
            if (null !== $postalAddress->getAddressCountry()) {
                $addressCountry = $postalAddress->getAddressCountry();
            }

            $addressRegion = null;
            if (null !== $postalAddress->getAddressRegion()) {
                $addressRegion = $postalAddress->getAddressRegion();
            }

            $addressPostalCode = null;
            if (null !== $postalAddress->getPostalCode()) {
                $addressPostalCode = $postalAddress->getPostalCode();
            }

            $addressString = '';
            if (null !== $postalAddress->getHouseNumber()) {
                $addressString .= $postalAddress->getHouseNumber().' ';
            }

            if (null !== $postalAddress->getStreetAddress()) {
                $addressString .= $postalAddress->getStreetAddress().' ';
            }
            $addressString = \strtoupper(\trim($addressString));

            $unitNoString = '';
            if (null !== $postalAddress->getFloor() && '' !== \trim($postalAddress->getFloor())) {
                $unitNoString = ' '.$postalAddress->getFloor();
            }

            if (null !== $postalAddress->getUnitNumber() && '' !== \trim($postalAddress->getUnitNumber())) {
                $unitNoString .= '-'.$postalAddress->getUnitNumber();
            }

            if ('' !== $unitNoString) {
                $unitNoString = \substr_replace($unitNoString, '#', 0, 1);
            }
            $unitNoString = \strtoupper(\trim($unitNoString));

            $addressBuildingName = null;
            if (null !== $postalAddress->getBuildingName()) {
                $addressBuildingName = $postalAddress->getBuildingName();
            }

            $addressData = [
                'AddressCountry' => $addressCountry,
                'AddressState' => $addressRegion,
                'AddressCity' => $addressRegion,
                'AddressLine1' => $addressString,
                'AddressLine2' => $addressBuildingName,
                'AddressLine3' => $unitNoString,
                'PostalCode' => $addressPostalCode,
            ];
        } else {
            return [];
        }

        if (!empty($contactPoints['email'])) {
            $emailAddress = $contactPoints['email'];
        }

        if ('' !== $contract->getContractNumber()) {
            $contractNumber = $contract->getContractNumber();
        }

        $maillingAddressData = [
            'CustomerAccountNumber' => $contractNumber,
            'AttendTo' => $customerName,
            'EmailAddress' => $emailAddress,
            'EffectiveDate' => $validFrom,
        ];

        $maillingAddressData += $addressData;

        return $maillingAddressData;
    }
}
