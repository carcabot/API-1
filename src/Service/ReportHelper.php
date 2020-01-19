<?php

declare(strict_types=1);

namespace App\Service;

class ReportHelper
{
    const EMAIL_CONTACT_POINT = 'emails';
    const FAX_NUMBER_CONTACT_POINT = 'faxNumbers';
    const MOBILE_NUMBER_CONTACT_POINT = 'mobilePhoneNumbers';
    const TELEPHONE_CONTACT_POINT = 'telephoneNumbers';

    public static function mapContactPoints(array $contactPoints, string $type)
    {
        $getterFunctions = [
            self::EMAIL_CONTACT_POINT => 'getEmails',
            self::FAX_NUMBER_CONTACT_POINT => 'getFaxNumbers',
            self::MOBILE_NUMBER_CONTACT_POINT => 'getMobilePhoneNumbers',
            self::TELEPHONE_CONTACT_POINT => 'getTelephoneNumbers',
        ];

        if (isset($getterFunctions[$type])) {
            foreach ($contactPoints as $contactPoint) {
                if (\count($contactPoint->{$getterFunctions[$type]}()) > 0) {
                    return \array_values(\array_slice($contactPoint->{$getterFunctions[$type]}(), -1))[0];
                }
            }
        }

        return null;
    }

    public static function mapIdentifiers(array $identifiers, string $type)
    {
        foreach ($identifiers as $identifier) {
            if (true === $identifier->isValid() && $type === $identifier->getName()->getValue()) {
                return $identifier->getValue();
            }
        }

        return null;
    }

    public static function mapUnitCodes(?string $unitCode)
    {
        switch ($unitCode) {
            case 'KWH':
                return 'kWh';
            case 'MWH':
                return 'MWh';
            default:
                return null;
        }
    }
}
