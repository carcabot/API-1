<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Enworkz\Domain\Command\ContractPostalAddress;

use App\Entity\ContractPostalAddress;

/**
 * Builds the mailing address data for web service consumption.
 */
class BuildMailingAddressData
{
    /**
     * @var ContractPostalAddress
     */
    private $contractPostalAddress;

    /**
     * @param ContractPostalAddress $contractPostalAddress
     */
    public function __construct(ContractPostalAddress $contractPostalAddress)
    {
        $this->contractPostalAddress = $contractPostalAddress;
    }

    /**
     * Gets the contractPostalAddress.
     *
     * @return ContractPostalAddress
     */
    public function getContractPostalAddress(): ContractPostalAddress
    {
        return $this->contractPostalAddress;
    }
}
