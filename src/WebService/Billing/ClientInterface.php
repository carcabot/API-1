<?php

declare(strict_types=1);

namespace App\WebService\Billing;

use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\ContractPostalAddress;
use App\Entity\CustomerAccount;
use App\Entity\Ticket;
use App\Entity\WithdrawCreditsAction;

interface ClientInterface
{
    public function downloadXML(\DateTime $date, string $type);

    public function getAccountClosureStatusXMLFile(\DateTime $date);

    public function getApplicationRequestStatusHistory(ApplicationRequest $applicationRequest);

    public function getARInvoice(string $invoiceNumber, ?Contract $contract = null, ?ApplicationRequest $applicationRequest = null);

    public function getARInvoiceAttachment(string $invoiceNumber, ?Contract $contract = null, ?ApplicationRequest $applicationRequest = null);

    public function getContractApplicationXMLFile(\DateTime $date);

    public function getContractArrearsHistory(Contract $contract);

    public function getContractBasicBillingSummary(Contract $contract);

    public function getContractBillingInformation(Contract $contract);

    public function getContractConsumptionsByBillingPeriod(Contract $contract);

    public function getContractEmailMessageHistories(Contract $contract);

    public function getContractFinancialHistory(Contract $contract);

    public function getContractGiroHistory(Contract $contract);

    public function getContractRenewalApplicationXMLFile(\DateTime $date);

    public function getContractBillingSummary(Contract $contract, ?ApplicationRequest $applicationRequest = null);

    public function getContractEmailHistory(Contract $contract, ?string $id = null);

    public function getContractRCCSHistory(Contract $contract);

    public function getContractWelcomePackage(Contract $contract);

    public function getContractWelcomePackageAttachment(Contract $contract, int $fileKey);

    public function getCustomerBlackListXMLFile(\DateTime $date);

    public function getEventActivityXMLFile(\DateTime $date);

    public function getFtpSchedule(string $type);

    public function getGiroTerminationXMLFile(\DateTime $date);

    public function getMassContractApplicationRequestRenewalXMLFile(\DateTime $date);

    public function getMassContractApplicationRequestXMLFile(\DateTime $date);

    public function getMassContractClosureApplicationRequestXMLFile(\DateTime $date);

    public function getMassContractTransferOutApplicationRequestXMLFile(\DateTime $date);

    public function getPromotionCodeXMLFile(\DateTime $date);

    public function getProviderName();

    public function getRCCSTerminationXMLFile(\DateTime $date);

    public function getThirdPartyChargeConfiguration();

    public function getTransferOutApplicationXMLFile(\DateTime $date);

    public function submitApplicationRequest(ApplicationRequest $applicationRequest, ?string $date, bool $fail = false);

    public function submitRedeemCreditsActions(array $redeemedCreditsActions, bool $upload = true);

    public function submitWithdrawCreditsAction(WithdrawCreditsAction $withdrawCreditsAction, bool $fail = false);

    public function updateContractMailingAddress(ContractPostalAddress $contractPostalAddress);

    public function updateCustomerContact(CustomerAccount $customerAccount, ?string $previousName = null);

    public function uploadFailedApplicationRequestStatusUpdate(array $failedApplicationRequests, string $action);

    public function uploadCustomerBlacklistUpdateReturnFile(array $customerAccountsBlackListData);

    public function uploadReturnFile(array $data, \DateTime $date, string $type, bool $upload = true);

    public function uploadXML(\DateTime $date, string $type);

    public function createTask(Ticket $ticket, $fail = false);
}
