<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 16/4/19
 * Time: 3:54 PM.
 */

namespace App\Tests\WebService\Billing\Provider\Anacle\Domain\Command\WithdrawCreditsAction;

use App\Entity\Contract;
use App\Entity\CreditsTransaction;
use App\Entity\CustomerAccount;
use App\Entity\MonetaryAmount;
use App\Entity\Payment;
use App\Entity\WithdrawCreditsAction;
use App\WebService\Billing\Provider\Anacle\Domain\Command\WithdrawCreditsAction\BuildWithdrawCreditsTransactionData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\WithdrawCreditsAction\BuildWithdrawCreditsTransactionDataHandler;
use App\WebService\Billing\Services\DataMapper;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;

class BuildWithdrawCreditsTransactionDataHandlerTest extends TestCase
{
    public function testWithdrawCreditsTransactionWithContractAndBankDetails()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('C123456');
        $contractProphecy = $contractProphecy->reveal();

        $monetaryAmountProphecy = $this->prophesize(MonetaryAmount::class);
        $monetaryAmountProphecy->getValue()->willReturn('01');
        $monetaryAmountProphecy = $monetaryAmountProphecy->reveal();

        $creditsTransactionProphecy = $this->prophesize(CreditsTransaction::class);
        $creditsTransactionProphecy->getCreditsTransactionNumber()->willReturn('CT123456');
        $creditsTransactionProphecy = $creditsTransactionProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->getBankAccountHolderName()->willReturn('testHolderName');
        $paymentProphecy->getBankAccountNumber()->willReturn('testAccountNumber');
        $paymentProphecy->getBankCode()->willReturn('testBankCode');
        $paymentProphecy->getContactNumber()->willReturn($phoneNumberProphecy);
        $paymentProphecy->getEmail()->willReturn('test@test.com');
        $paymentProphecy->getAmount()->willReturn($monetaryAmountProphecy);
        $paymentProphecy = $paymentProphecy->reveal();

        $withdrawCreditsActionProphecy = $this->prophesize(WithdrawCreditsAction::class);
        $withdrawCreditsActionProphecy->getInstrument()->willReturn($paymentProphecy);
        $withdrawCreditsActionProphecy->getDateCreated()->willReturn($now);
        $withdrawCreditsActionProphecy->getContract()->willReturn($contractProphecy);
        $withdrawCreditsActionProphecy->getCreditsTransaction()->willReturn($creditsTransactionProphecy);

        $withdrawCreditsAction = $withdrawCreditsActionProphecy->reveal();

        $expectedCustomerRefundData = [
            'CRMReferenceNumber' => 'CT123456',
            'RefundAmount' => \number_format((float) 01, 2, '.', ''),
            'RequestDate' => $now->format('Ymd'),
            'FRCContractNumber' => 'C123456',
            'ToRefundImmediately' => 1,
            'BankCode' => 'testBankCode',
            'BankAccountName' => 'testHolderName',
            'BankAccountNumber' => 'testAccountNumber',
            'Email' => 'test@test.com',
            'ContactNumber' => '111111',
        ];

        $buildWithdrawCreditsTransactionData = new BuildWithdrawCreditsTransactionData($withdrawCreditsAction);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildWithdrawCreditsTransactionDataHandler = new BuildWithdrawCreditsTransactionDataHandler($dataMapperProphecy);
        $actualCustomerRefundData = $buildWithdrawCreditsTransactionDataHandler->handle($buildWithdrawCreditsTransactionData);

        $this->assertEquals($expectedCustomerRefundData, $actualCustomerRefundData);
    }

    public function testWithdrawCreditsTransactionWithContractAndNoBankDetails()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('C123456');
        $contractProphecy = $contractProphecy->reveal();

        $monetaryAmountProphecy = $this->prophesize(MonetaryAmount::class);
        $monetaryAmountProphecy->getValue()->willReturn('01');
        $monetaryAmountProphecy = $monetaryAmountProphecy->reveal();

        $creditsTransactionProphecy = $this->prophesize(CreditsTransaction::class);
        $creditsTransactionProphecy->getCreditsTransactionNumber()->willReturn('CT123456');
        $creditsTransactionProphecy = $creditsTransactionProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->getBankAccountHolderName()->willReturn(null);
        $paymentProphecy->getBankAccountNumber()->willReturn(null);
        $paymentProphecy->getBankCode()->willReturn(null);
        $paymentProphecy->getContactNumber()->willReturn($phoneNumberProphecy);
        $paymentProphecy->getEmail()->willReturn('test@test.com');
        $paymentProphecy->getAmount()->willReturn($monetaryAmountProphecy);
        $paymentProphecy = $paymentProphecy->reveal();

        $withdrawCreditsActionProphecy = $this->prophesize(WithdrawCreditsAction::class);
        $withdrawCreditsActionProphecy->getInstrument()->willReturn($paymentProphecy);
        $withdrawCreditsActionProphecy->getDateCreated()->willReturn($now);
        $withdrawCreditsActionProphecy->getContract()->willReturn($contractProphecy);
        $withdrawCreditsActionProphecy->getCreditsTransaction()->willReturn($creditsTransactionProphecy);

        $withdrawCreditsAction = $withdrawCreditsActionProphecy->reveal();

        $expectedCustomerRefundData = [
            'CRMReferenceNumber' => 'CT123456',
            'RefundAmount' => \number_format((float) 01, 2, '.', ''),
            'RequestDate' => $now->format('Ymd'),
            'FRCContractNumber' => 'C123456',
            'ToRefundImmediately' => 0,
        ];

        $buildWithdrawCreditsTransactionData = new BuildWithdrawCreditsTransactionData($withdrawCreditsAction);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildWithdrawCreditsTransactionDataHandler = new BuildWithdrawCreditsTransactionDataHandler($dataMapperProphecy);
        $actualCustomerRefundData = $buildWithdrawCreditsTransactionDataHandler->handle($buildWithdrawCreditsTransactionData);

        $this->assertEquals($expectedCustomerRefundData, $actualCustomerRefundData);
    }

    public function testWithdrawCreditsTransactionWithNoContract()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn(['CUSTOMER']);
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $creditsTransactionProphecy = $this->prophesize(CreditsTransaction::class);
        $creditsTransactionProphecy->getCreditsTransactionNumber()->willReturn('CT123456');
        $creditsTransactionProphecy = $creditsTransactionProphecy->reveal();

        $monetaryAmountProphecy = $this->prophesize(MonetaryAmount::class);
        $monetaryAmountProphecy->getValue()->willReturn('01');
        $monetaryAmountProphecy = $monetaryAmountProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->getBankAccountHolderName()->willReturn('testHolderName');
        $paymentProphecy->getBankAccountNumber()->willReturn('testAccountNumber');
        $paymentProphecy->getBankCode()->willReturn('testBankCode');
        $paymentProphecy->getContactNumber()->willReturn($phoneNumberProphecy);
        $paymentProphecy->getEmail()->willReturn('test@test.com');
        $paymentProphecy->getAmount()->willReturn($monetaryAmountProphecy);
        $paymentProphecy = $paymentProphecy->reveal();

        $withdrawCreditsActionProphecy = $this->prophesize(WithdrawCreditsAction::class);
        $withdrawCreditsActionProphecy->getContract()->willReturn(null);
        $withdrawCreditsActionProphecy->getCreditsTransaction()->willReturn($creditsTransactionProphecy);
        $withdrawCreditsActionProphecy->getDateCreated()->willReturn($now);
        $withdrawCreditsActionProphecy->getInstrument()->willReturn($paymentProphecy);
        $withdrawCreditsActionProphecy->getObject()->willReturn($customerAccountProphecy);

        $withdrawCreditsAction = $withdrawCreditsActionProphecy->reveal();

        $expectedCustomerRefundData = [
            'CRMReferenceNumber' => 'CT123456',
            'RefundAmount' => \number_format((float) 01, 2, '.', ''),
            'RequestDate' => $now->format('Ymd'),
            'ToRefundImmediately' => 1,
            'PaytoParty' => 'testHolderName',
            'BankCode' => 'testBankCode',
            'BankAccountName' => 'testHolderName',
            'BankAccountNumber' => 'testAccountNumber',
            'Email' => 'test@test.com',
            'ContactNumber' => '111111',
        ];

        $buildWithdrawCreditsTransactionData = new BuildWithdrawCreditsTransactionData($withdrawCreditsAction);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildWithdrawCreditsTransactionDataHandler = new BuildWithdrawCreditsTransactionDataHandler($dataMapperProphecy);
        $actualCustomerRefundData = $buildWithdrawCreditsTransactionDataHandler->handle($buildWithdrawCreditsTransactionData);

        $this->assertEquals($expectedCustomerRefundData, $actualCustomerRefundData);
    }
}
