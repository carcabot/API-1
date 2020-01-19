<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\WithdrawCreditsAction;

use App\Enum\AccountCategory;
use App\WebService\Billing\Services\DataMapper;

class BuildWithdrawCreditsTransactionDataHandler
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

    public function handle(BuildWithdrawCreditsTransactionData $command): array
    {
        $withdrawCreditsAction = $command->getWithdrawCreditsAction();

        $amount = $withdrawCreditsAction->getInstrument()->getAmount()->getValue();
        $dateCreated = null !== $withdrawCreditsAction->getDateCreated() ? $withdrawCreditsAction->getDateCreated()->setTimezone($this->timezone)->format('Ymd') : null;
        $email = null;

        $customerRefundData = [
            'CRMReferenceNumber' => $withdrawCreditsAction->getCreditsTransaction()->getCreditsTransactionNumber(),
            'RefundAmount' => \number_format((float) $amount, 2, '.', ''),
            'RequestDate' => $dateCreated,
        ];

        if (null !== $withdrawCreditsAction->getContract()) {
            $customerRefundData['FRCContractNumber'] = $withdrawCreditsAction->getContract()->getContractNumber();
            $customerRefundData['ToRefundImmediately'] = 0;

            if (null !== $withdrawCreditsAction->getInstrument()->getBankCode() && null !== $withdrawCreditsAction->getInstrument()->getBankAccountHolderName()
                && null !== $withdrawCreditsAction->getInstrument()->getBankAccountNumber() && null !== $withdrawCreditsAction->getInstrument()->getEmail()) {
                $customerRefundData['ToRefundImmediately'] = 1;
                $customerRefundData['BankCode'] = $withdrawCreditsAction->getInstrument()->getBankCode();
                $customerRefundData['BankAccountName'] = $withdrawCreditsAction->getInstrument()->getBankAccountHolderName();
                $customerRefundData['BankAccountNumber'] = $withdrawCreditsAction->getInstrument()->getBankAccountNumber();
                $customerRefundData['Email'] = $withdrawCreditsAction->getInstrument()->getEmail();

                if (null !== $withdrawCreditsAction->getInstrument()->getContactNumber()) {
                    $customerRefundData['ContactNumber'] = $withdrawCreditsAction->getInstrument()->getContactNumber()->getNationalNumber();
                }
            }
        } else {
            if (\in_array(AccountCategory::CUSTOMER, $withdrawCreditsAction->getObject()->getCategories(), true)) {
                $customerRefundData['ToRefundImmediately'] = 1;
            }
            $customerRefundData['PaytoParty'] = $withdrawCreditsAction->getInstrument()->getBankAccountHolderName();
            $customerRefundData['BankCode'] = $withdrawCreditsAction->getInstrument()->getBankCode();
            $customerRefundData['BankAccountName'] = $withdrawCreditsAction->getInstrument()->getBankAccountHolderName();
            $customerRefundData['BankAccountNumber'] = $withdrawCreditsAction->getInstrument()->getBankAccountNumber();
            $customerRefundData['Email'] = $withdrawCreditsAction->getInstrument()->getEmail();

            if (null !== $withdrawCreditsAction->getInstrument()->getContactNumber()) {
                $customerRefundData['ContactNumber'] = $withdrawCreditsAction->getInstrument()->getContactNumber()->getNationalNumber();
            }
        }

        return $customerRefundData;
    }
}
