<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\CustomerAccount;

use App\Enum\AccountType;
use App\WebService\Billing\Services\DataMapper;

class BuildContactUpdateDataHandler
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

    public function handle(BuildContactUpdateData $command): array
    {
        $customerAccount = $command->getCustomerAccount();
        $previousCustomerName = $command->getPreviousCustomerName();

        $contactPoints = null;
        $corporationContactPoints = null;
        $customerName = null;
        $emailAddress = null;
        $mobileNumber = null;
        $telephoneNumber = null;
        $faxNumber = null;
        $date = null;

        if (AccountType::INDIVIDUAL === $customerAccount->getType()->getValue()) {
            $personDetails = $customerAccount->getPersonDetails();

            if (null !== $personDetails) {
                $customerName = \strtoupper($personDetails->getName());
                $contactPoints = $this->dataMapper->mapContactPoints($personDetails->getContactPoints());
            }
        }

        if (null === $previousCustomerName) {
            $previousCustomerName = $customerName;
        }

        if (!empty($contactPoints['email'])) {
            $emailAddress = $contactPoints['email'];
        }

        if (!empty($contactPoints['mobile_number'])) {
            $mobileNumber = $contactPoints['mobile_number']['number'];
        }

        if (!empty($contactPoints['phone_number'])) {
            $telephoneNumber = $contactPoints['phone_number']['number'];
        }

        if (!empty($contactPoints['fax_number'])) {
            $faxNumber = $contactPoints['fax_number']['number'];
        }

        $now = new \DateTime();
        $now->setTimezone($this->timezone);
        $now = $now->format('Ymd');

        $customerContactNumberData = [
            'CRMCustomerReferenceNumber' => $customerAccount->getAccountNumber(),
            'Contacts' => [
                'Contact' => [
                    'PreviousContactName' => $previousCustomerName,
                    'ContactName' => $customerName,
                    'Phone' => $telephoneNumber,
                    'Fax' => $faxNumber,
                    'Email' => $emailAddress,
                    'Mobile' => $mobileNumber,
                    'EffectiveDate' => $now,
                ],
            ],
        ];

        return $customerContactNumberData;
    }
}
