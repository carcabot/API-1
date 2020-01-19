<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Enworkz\Domain\Command\ContractPostalAddress;

use App\Enum\PostalAddressType;

class BuildMailingAddressDataHandler
{
    /**
     * @var \DateTimeZone
     */
    private $timezone;

    public function __construct()
    {
        $this->timezone = new \DateTimeZone('Asia/Singapore');
    }

    public function handle(BuildMailingAddressData $command): array
    {
        $contractPostalAddress = $command->getContractPostalAddress();
        $contract = $contractPostalAddress->getContract();
        $contractNumber = null;
        $validFrom = null;

        $addressData = [];
        $postalAddress = $contractPostalAddress->getAddress();
        if (PostalAddressType::MAILING_ADDRESS === $postalAddress->getType()->getValue()) {
            if (null !== $contractPostalAddress->getValidFrom()) {
                $validFrom = $contractPostalAddress->getValidFrom();
                $validFrom->setTimezone($this->timezone);
                $validFrom = $validFrom->format('Ymd');
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
                'AddressLine1' => $addressString,
                'AddressLine2' => $addressBuildingName,
                'AddressLine3' => $unitNoString,
                'PostalCode' => $addressPostalCode,
            ];
        } else {
            return [];
        }

        $contractNumber = $contract->getContractNumber();

        $mailingAddressData = [
            'FRCContractNumber' => $contractNumber,
            'EffectiveDate' => $validFrom,
        ];

        $mailingAddressData += $addressData;

        return $mailingAddressData;
    }
}
