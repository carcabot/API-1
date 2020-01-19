<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Enworkz\Domain\Command\CustomerAccount;

use App\Enum\AccountType;
use App\Enum\ContractStatus;
use App\Enum\Role;
use App\WebService\Billing\Services\DataMapper;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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

        $contactPoints = null;
        $corporationContactPoints = null;
        $customerName = null;
        $contactPerson = null;
        $emailAddress = null;
        $mobileNumber = null;
        $telephoneNumber = null;
        $faxNumber = null;
        $date = null;

        if (AccountType::CORPORATE === $customerAccount->getType()->getValue()) {
            $contactPersonDetails = null;
            $corporationDetails = $customerAccount->getCorporationDetails();

            if (null !== $corporationDetails) {
                $customerName = \strtoupper($corporationDetails->getName());

                $corporationContactPoints = $this->dataMapper->mapContactPoints($corporationDetails->getContactPoints());

                foreach ($corporationDetails->getEmployees() as $employeeRole) {
                    if (null !== $employeeRole->getRoleName() && Role::CONTACT_PERSON === $employeeRole->getRoleName()->getValue()) {
                        $contactPersonDetails = $employeeRole->getEmployee()->getPersonDetails();
                    }
                }

                if (null !== $contactPersonDetails) {
                    $contactPerson = \strtoupper($contactPersonDetails->getName());
                    $contactPoints = $this->dataMapper->mapContactPoints($contactPersonDetails->getContactPoints());
                }
            }
        } else {
            $personDetails = $customerAccount->getPersonDetails();

            if (null !== $personDetails) {
                $customerName = \strtoupper($personDetails->getName());
                $contactPoints = $this->dataMapper->mapContactPoints($personDetails->getContactPoints());
            }
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

        if (!empty($corporationContactPoints['fax_number'])) {
            $faxNumber = $corporationContactPoints['fax_number']['number'];
        }

        $now = new \DateTime();
        $now->setTimezone($this->timezone);
        $now = $now->format('Ymd');

        $contracts = $customerAccount->getContracts();
        $currentContract = null;

        foreach ($contracts as $contract) {
            if (ContractStatus::ACTIVE === $contract->getStatus()->getValue()) {
                $currentContract = $contract;
                break;
            }
        }

        if (null !== $currentContract) {
            $customerContactNumberData = [
                'FRCContractNumber' => $currentContract->getContractNumber(),
                'ContactName' => null !== $contactPerson ? $contactPerson : $customerName,
                'Phone' => $telephoneNumber,
                'Fax' => $faxNumber,
                'Email' => $emailAddress,
                'Mobile' => $mobileNumber,
                'EffectiveDate' => $now,
            ];

            return $customerContactNumberData;
        }

        throw new BadRequestHttpException('Customer has no active contract');
    }
}
