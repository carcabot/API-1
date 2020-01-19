<?php

declare(strict_types=1);

namespace App\Tests\WebService\PaymentGateway\Provider\Wirecard;

use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\MonetaryAmount;
use App\Entity\Payment;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use App\Enum\PaymentMode;
use App\WebService\PaymentGateway\Provider\Wirecard\Client;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ClientTest extends TestCase
{
    public function testIswitchGetPaymentUrlRCCS()
    {
        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->getPaymentNumber()->willReturn('PY0000000001');
        $paymentProphecy->getAmount()->willReturn(new MonetaryAmount('50', 'SGD'));
        $paymentProphecy->getInvoiceNumber()->willReturn('INV0001');
        $payment = $paymentProphecy->reveal();

        $applicationRequestProphecy1 = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy2 = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy3 = $this->prophesize(ApplicationRequest::class);

        $customerProphecy = $this->prophesize(CustomerAccount::class);
        $customerProphecy->getApplicationRequests()->willReturn([$applicationRequestProphecy1, $applicationRequestProphecy2, $applicationRequestProphecy3]);
        $customer = $customerProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SW001');
        $contractProphecy->getMsslAccountNumber()->willReturn('MS001');
        $contractProphecy->getEbsAccountNumber()->willReturn('EBS001');
        $contractProphecy->getCustomer()->willReturn($customer);
        $contractProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::MANUAL));
        $contract = $contractProphecy->reveal();

        $applicationRequestProphecy1->getDateModified()->willReturn(new \DateTime('2019-01-01'));
        $applicationRequestProphecy1->getContract()->willReturn($contract);
        $applicationRequestProphecy1->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy1->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy1->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequest1 = $applicationRequestProphecy1->reveal();

        $applicationRequestProphecy2->getDateModified()->willReturn(new \DateTime('2019-05-01'));
        $applicationRequestProphecy2->getContract()->willReturn($contract);
        $applicationRequestProphecy2->getPaymentMode()->willReturn(null);
        $applicationRequestProphecy2->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy2->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequest2 = $applicationRequestProphecy2->reveal();

        $applicationRequestProphecy3->getDateModified()->willReturn(new \DateTime('2019-06-01'));
        $applicationRequestProphecy3->getContract()->willReturn($contract);
        $applicationRequestProphecy3->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::RCCS));
        $applicationRequestProphecy3->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy3->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequest3 = $applicationRequestProphecy3->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $loggerInterfaceProphecy = $this->prophesize(LoggerInterface::class);
        $loggerInterface = $loggerInterfaceProphecy->reveal();

        $dateTimeZone = new \DateTimeZone('Asia/Singapore');

        $config = [];
        $config['merchant_id'] = '123';
        $config['merchant_recurring_id'] = '123';
        $config['merchant_secret'] = 's123';
        $config['return_url'] = 'https://return.url';
        $config['status_url'] = 'https://status.url';
        $config['merchant_url'] = 'https://merchant.url';
        $config['profile'] = 'iswitch';

        $client = new Client($config, $dateTimeZone, $commandBus, $loggerInterface);
        $expectedOutput = $this->createExpectedOutputsRCCS(new \DateTime('now', $dateTimeZone));
        $actualOutput = $client->getPaymentUrl($payment, $contract, new PaymentMode(PaymentMode::RCCS));
        $this->assertContains($actualOutput, $expectedOutput);
    }

    public function testIswitchGetPaymentUrlRCCS2()
    {
        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->getPaymentNumber()->willReturn('PY0000000001');
        $paymentProphecy->getAmount()->willReturn(new MonetaryAmount('50', 'SGD'));
        $paymentProphecy->getInvoiceNumber()->willReturn('INV0001');
        $payment = $paymentProphecy->reveal();

        $applicationRequestProphecy1 = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy2 = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy3 = $this->prophesize(ApplicationRequest::class);

        $customerProphecy = $this->prophesize(CustomerAccount::class);
        $customerProphecy->getApplicationRequests()->willReturn([$applicationRequestProphecy1, $applicationRequestProphecy2, $applicationRequestProphecy3]);
        $customer = $customerProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SW001');
        $contractProphecy->getMsslAccountNumber()->willReturn('MS001');
        $contractProphecy->getEbsAccountNumber()->willReturn('EBS001');
        $contractProphecy->getCustomer()->willReturn($customer);
        $contractProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::MANUAL));
        $contract = $contractProphecy->reveal();

        $applicationRequestProphecy1->getDateModified()->willReturn(new \DateTime('2019-01-01'));
        $applicationRequestProphecy1->getContract()->willReturn($contract);
        $applicationRequestProphecy1->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy1->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy1->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequest1 = $applicationRequestProphecy1->reveal();

        $applicationRequestProphecy2->getDateModified()->willReturn(new \DateTime('2019-05-01'));
        $applicationRequestProphecy2->getContract()->willReturn($contract);
        $applicationRequestProphecy2->getPaymentMode()->willReturn(null);
        $applicationRequestProphecy2->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy2->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequest2 = $applicationRequestProphecy2->reveal();

        $applicationRequestProphecy3->getDateModified()->willReturn(new \DateTime('2019-06-01'));
        $applicationRequestProphecy3->getContract()->willReturn($contract);
        $applicationRequestProphecy3->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::RCCS));
        $applicationRequestProphecy3->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy3->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequest3 = $applicationRequestProphecy3->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $loggerInterfaceProphecy = $this->prophesize(LoggerInterface::class);
        $loggerInterface = $loggerInterfaceProphecy->reveal();

        $dateTimeZone = new \DateTimeZone('Asia/Singapore');

        $config = [];
        $config['merchant_id'] = '123';
        $config['merchant_recurring_id'] = '123';
        $config['merchant_secret'] = 's123';
        $config['return_url'] = 'https://return.url';
        $config['status_url'] = 'https://status.url';
        $config['merchant_url'] = 'https://merchant.url';
        $config['profile'] = 'iswitch';

        $client = new Client($config, $dateTimeZone, $commandBus, $loggerInterface);
        $expectedOutput = $this->createExpectedOutputsRCCS(new \DateTime('now', $dateTimeZone));
        $actualOutput = $client->getPaymentUrl($payment, $contract);
        $this->assertContains($actualOutput, $expectedOutput);
    }

    public function testIswitchGetPaymentUrlRCCS3()
    {
        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->getPaymentNumber()->willReturn('PY0000000001');
        $paymentProphecy->getAmount()->willReturn(new MonetaryAmount('50', 'SGD'));
        $paymentProphecy->getInvoiceNumber()->willReturn('INV0001');
        $payment = $paymentProphecy->reveal();

        $applicationRequestProphecy1 = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy2 = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy3 = $this->prophesize(ApplicationRequest::class);

        $customerProphecy = $this->prophesize(CustomerAccount::class);
        $customerProphecy->getApplicationRequests()->willReturn([$applicationRequestProphecy1, $applicationRequestProphecy2, $applicationRequestProphecy3]);
        $customer = $customerProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SW001');
        $contractProphecy->getMsslAccountNumber()->willReturn('MS001');
        $contractProphecy->getEbsAccountNumber()->willReturn('EBS001');
        $contractProphecy->getCustomer()->willReturn($customer);
        $contractProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::MANUAL));
        $contract = $contractProphecy->reveal();

        $applicationRequestProphecy1->getDateModified()->willReturn(new \DateTime('2019-01-01'));
        $applicationRequestProphecy1->getContract()->willReturn($contract);
        $applicationRequestProphecy1->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::RCCS));
        $applicationRequestProphecy1->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy1->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::ACCOUNT_CLOSURE));
        $applicationRequest1 = $applicationRequestProphecy1->reveal();

        $applicationRequestProphecy2->getDateModified()->willReturn(new \DateTime('2019-05-01'));
        $applicationRequestProphecy2->getContract()->willReturn($contract);
        $applicationRequestProphecy2->getPaymentMode()->willReturn(null);
        $applicationRequestProphecy2->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy2->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::TRANSFER_OUT));
        $applicationRequest2 = $applicationRequestProphecy2->reveal();

        $applicationRequestProphecy3->getDateModified()->willReturn(new \DateTime('2019-06-01'));
        $applicationRequestProphecy3->getContract()->willReturn($contract);
        $applicationRequestProphecy3->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::RCCS));
        $applicationRequestProphecy3->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy3->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::GIRO_TERMINATION));
        $applicationRequest3 = $applicationRequestProphecy3->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $loggerInterfaceProphecy = $this->prophesize(LoggerInterface::class);
        $loggerInterface = $loggerInterfaceProphecy->reveal();

        $dateTimeZone = new \DateTimeZone('Asia/Singapore');

        $config = [];
        $config['merchant_id'] = '123';
        $config['merchant_recurring_id'] = '123';
        $config['merchant_secret'] = 's123';
        $config['return_url'] = 'https://return.url';
        $config['status_url'] = 'https://status.url';
        $config['merchant_url'] = 'https://merchant.url';
        $config['profile'] = 'iswitch';

        $client = new Client($config, $dateTimeZone, $commandBus, $loggerInterface);
        $expectedOutput = $this->createExpectedOutputsRCCS(new \DateTime('now', $dateTimeZone));
        $actualOutput = $client->getPaymentUrl($payment, $contract, new PaymentMode(PaymentMode::RCCS));
        $this->assertContains($actualOutput, $expectedOutput);
    }

    public function testIswitchGetPaymentUrlThrowException()
    {
        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->getPaymentNumber()->willReturn('PY0000000001');
        $paymentProphecy->getAmount()->willReturn(new MonetaryAmount('50', 'SGD'));
        $paymentProphecy->getInvoiceNumber()->willReturn('INV0001');
        $payment = $paymentProphecy->reveal();

        $applicationRequestProphecy1 = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy2 = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy3 = $this->prophesize(ApplicationRequest::class);

        $customerProphecy = $this->prophesize(CustomerAccount::class);
        $customerProphecy->getApplicationRequests()->willReturn([$applicationRequestProphecy1, $applicationRequestProphecy2, $applicationRequestProphecy3]);
        $customer = $customerProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SW001');
        $contractProphecy->getMsslAccountNumber()->willReturn('MS001');
        $contractProphecy->getEbsAccountNumber()->willReturn('EBS001');
        $contractProphecy->getCustomer()->willReturn($customer);
        $contractProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::RCCS));
        $contract = $contractProphecy->reveal();

        $applicationRequestProphecy1->getDateModified()->willReturn(new \DateTime('2019-01-01'));
        $applicationRequestProphecy1->getContract()->willReturn($contract);
        $applicationRequestProphecy1->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::RCCS));
        $applicationRequestProphecy1->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy1->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::ACCOUNT_CLOSURE));
        $applicationRequest1 = $applicationRequestProphecy1->reveal();

        $applicationRequestProphecy2->getDateModified()->willReturn(new \DateTime('2019-05-01'));
        $applicationRequestProphecy2->getContract()->willReturn($contract);
        $applicationRequestProphecy2->getPaymentMode()->willReturn(null);
        $applicationRequestProphecy2->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy2->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::TRANSFER_OUT));
        $applicationRequest2 = $applicationRequestProphecy2->reveal();

        $applicationRequestProphecy3->getDateModified()->willReturn(new \DateTime('2019-06-01'));
        $applicationRequestProphecy3->getContract()->willReturn($contract);
        $applicationRequestProphecy3->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::RCCS));
        $applicationRequestProphecy3->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy3->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::GIRO_TERMINATION));
        $applicationRequest3 = $applicationRequestProphecy3->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $loggerInterfaceProphecy = $this->prophesize(LoggerInterface::class);
        $loggerInterface = $loggerInterfaceProphecy->reveal();

        $dateTimeZone = new \DateTimeZone('Asia/Singapore');

        $config = [];
        $config['merchant_id'] = '123';
        $config['merchant_recurring_id'] = '123';
        $config['merchant_secret'] = 's123';
        $config['return_url'] = 'https://return.url';
        $config['status_url'] = 'https://status.url';
        $config['merchant_url'] = 'https://merchant.url';
        $config['profile'] = 'iswitch';

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('RCCS is activated.');

        $client = new Client($config, $dateTimeZone, $commandBus, $loggerInterface);
        $actualOutput = $client->getPaymentUrl($payment, $contract, new PaymentMode(PaymentMode::RCCS));
    }

    public function testIswitchGetPaymentUrlManual()
    {
        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->getPaymentNumber()->willReturn('PY0000000001');
        $paymentProphecy->getAmount()->willReturn(new MonetaryAmount('50', 'SGD'));
        $paymentProphecy->getInvoiceNumber()->willReturn('INV0001');
        $payment = $paymentProphecy->reveal();

        $applicationRequestProphecy1 = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy2 = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy3 = $this->prophesize(ApplicationRequest::class);

        $customerProphecy = $this->prophesize(CustomerAccount::class);
        $customerProphecy->getApplicationRequests()->willReturn([$applicationRequestProphecy1, $applicationRequestProphecy2, $applicationRequestProphecy3]);
        $customer = $customerProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SW001');
        $contractProphecy->getMsslAccountNumber()->willReturn('MS001');
        $contractProphecy->getEbsAccountNumber()->willReturn('EBS001');
        $contractProphecy->getCustomer()->willReturn($customer);
        $contractProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::MANUAL));
        $contract = $contractProphecy->reveal();

        $applicationRequestProphecy1->getDateModified()->willReturn(new \DateTime('2019-01-01'));
        $applicationRequestProphecy1->getContract()->willReturn($contract);
        $applicationRequestProphecy1->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy1->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy1->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequest1 = $applicationRequestProphecy1->reveal();

        $applicationRequestProphecy2->getDateModified()->willReturn(new \DateTime('2019-05-01'));
        $applicationRequestProphecy2->getContract()->willReturn($contract);
        $applicationRequestProphecy2->getPaymentMode()->willReturn(null);
        $applicationRequestProphecy2->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy2->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequest2 = $applicationRequestProphecy2->reveal();

        $applicationRequestProphecy3->getDateModified()->willReturn(new \DateTime('2019-06-01'));
        $applicationRequestProphecy3->getContract()->willReturn($contract);
        $applicationRequestProphecy3->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::RCCS));
        $applicationRequestProphecy3->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy3->getType()->willReturn(new ApplicationRequestType(ApplicationRequestType::CONTRACT_APPLICATION));
        $applicationRequest3 = $applicationRequestProphecy3->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $loggerInterfaceProphecy = $this->prophesize(LoggerInterface::class);
        $loggerInterface = $loggerInterfaceProphecy->reveal();

        $dateTimeZone = new \DateTimeZone('Asia/Singapore');

        $config = [];
        $config['merchant_id'] = '123';
        $config['merchant_recurring_id'] = '123';
        $config['merchant_secret'] = 's123';
        $config['return_url'] = 'https://return.url';
        $config['status_url'] = 'https://status.url';
        $config['merchant_url'] = 'https://merchant.url';
        $config['profile'] = 'iswitch';

        $client = new Client($config, $dateTimeZone, $commandBus, $loggerInterface);
        $expectedOutput = $this->createExpectedOutputsNotRCCS(new \DateTime('now', $dateTimeZone));
        $actualOutput = $client->getPaymentUrl($payment, $contract, new PaymentMode(PaymentMode::MANUAL));
        $this->assertContains($actualOutput, $expectedOutput);
    }

    public function testNotIswitchGetPaymentUrlRCCS()
    {
        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->getPaymentNumber()->willReturn('PY0000000001');
        $paymentProphecy->getAmount()->willReturn(new MonetaryAmount('50', 'SGD'));
        $paymentProphecy->getInvoiceNumber()->willReturn('INV0001');
        $payment = $paymentProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SW001');
        $contractProphecy->getMsslAccountNumber()->willReturn('MS001');
        $contractProphecy->getEbsAccountNumber()->willReturn('EBS001');
        $contractProphecy->isRecurringOption()->willReturn(true);
        $contract = $contractProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $loggerInterfaceProphecy = $this->prophesize(LoggerInterface::class);
        $loggerInterface = $loggerInterfaceProphecy->reveal();

        $dateTimeZone = new \DateTimeZone('Asia/Singapore');

        $config = [];
        $config['merchant_id'] = '123';
        $config['merchant_recurring_id'] = '123';
        $config['merchant_secret'] = 's123';
        $config['return_url'] = 'https://return.url';
        $config['status_url'] = 'https://status.url';
        $config['merchant_url'] = 'https://merchant.url';
        $config['profile'] = 'unionpower';

        $client = new Client($config, $dateTimeZone, $commandBus, $loggerInterface);
        $expectedOutput = $this->createNotIswitchExpectedOutputsRCCS(new \DateTime('now', $dateTimeZone));
        $actualOutput = $client->getPaymentUrl($payment, $contract);
        $this->assertContains($actualOutput, $expectedOutput);
    }

    public function testNotIswitchGetPaymentUrlNOTRCCS()
    {
        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->getPaymentNumber()->willReturn('PY0000000001');
        $paymentProphecy->getAmount()->willReturn(new MonetaryAmount('50', 'SGD'));
        $paymentProphecy->getInvoiceNumber()->willReturn('INV0001');
        $payment = $paymentProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SW001');
        $contractProphecy->getMsslAccountNumber()->willReturn('MS001');
        $contractProphecy->getEbsAccountNumber()->willReturn('EBS001');
        $contractProphecy->isRecurringOption()->willReturn(false);
        $contract = $contractProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $loggerInterfaceProphecy = $this->prophesize(LoggerInterface::class);
        $loggerInterface = $loggerInterfaceProphecy->reveal();

        $dateTimeZone = new \DateTimeZone('Asia/Singapore');

        $config = [];
        $config['merchant_id'] = '123';
        $config['merchant_recurring_id'] = '123';
        $config['merchant_secret'] = 's123';
        $config['return_url'] = 'https://return.url';
        $config['status_url'] = 'https://status.url';
        $config['merchant_url'] = 'https://merchant.url';
        $config['profile'] = 'unionpower';

        $client = new Client($config, $dateTimeZone, $commandBus, $loggerInterface);
        $expectedOutput = $this->createNotIswitchExpectedOutputsNotRCCS(new \DateTime('now', $dateTimeZone));
        $actualOutput = $client->getPaymentUrl($payment, $contract);
        $this->assertContains($actualOutput, $expectedOutput);
    }

    private function createExpectedOutputsRCCS(\DateTime $now): array
    {
        $now->modify('+5 minutes');

        $urls = [];
        $urls[] = \sprintf('https://merchant.url?mid=123&ref=PY0000000001&cur=SGD&amt=50.00&transtype=sale&rcard=04&version=2&validity=%s&userfield1=&userfield2=SW001&userfield3=&returnurl=https://return.url&statusurl=https://status.url&recurrentid=INIT&subsequentmid=123&userfield4=INV0001&signature=f57ddd32ba76d89b085423536e568aecb5fc638de7242e0e0bc71570e6f1cc45f3b30107cabc61a360a3f41a3651dcdf1c3c20230758dc22e9ec23940c9a1f03', $now->format('Y-m-d-H:i:s'));

        for ($i = 0; $i < 5; ++$i) {
            $urls[] = \sprintf('https://merchant.url?mid=123&ref=PY0000000001&cur=SGD&amt=50.00&transtype=sale&rcard=04&version=2&validity=%s&userfield1=&userfield2=SW001&userfield3=&returnurl=https://return.url&statusurl=https://status.url&recurrentid=INIT&subsequentmid=123&userfield4=INV0001&signature=f57ddd32ba76d89b085423536e568aecb5fc638de7242e0e0bc71570e6f1cc45f3b30107cabc61a360a3f41a3651dcdf1c3c20230758dc22e9ec23940c9a1f03', $now->modify('+1 seconds')->format('Y-m-d-H:i:s'));
        }

        return $urls;
    }

    private function createExpectedOutputsNotRCCS(\DateTime $now): array
    {
        $now->modify('+5 minutes');
        $urls = [];
        $urls[] = \sprintf('https://merchant.url?mid=123&ref=PY0000000001&cur=SGD&amt=50.00&transtype=sale&rcard=04&version=2&validity=%s&userfield1=SW001&userfield2=&userfield3=&returnurl=https://return.url&statusurl=https://status.url&userfield4=INV0001&signature=f57ddd32ba76d89b085423536e568aecb5fc638de7242e0e0bc71570e6f1cc45f3b30107cabc61a360a3f41a3651dcdf1c3c20230758dc22e9ec23940c9a1f03', $now->format('Y-m-d-H:i:s'));

        for ($i = 0; $i < 5; ++$i) {
            $urls[] = \sprintf('https://merchant.url?mid=123&ref=PY0000000001&cur=SGD&amt=50.00&transtype=sale&rcard=04&version=2&validity=%s&userfield1=SW001&userfield2=&userfield3=&returnurl=https://return.url&statusurl=https://status.url&userfield4=INV0001&signature=f57ddd32ba76d89b085423536e568aecb5fc638de7242e0e0bc71570e6f1cc45f3b30107cabc61a360a3f41a3651dcdf1c3c20230758dc22e9ec23940c9a1f03', $now->modify('+1 seconds')->format('Y-m-d-H:i:s'));
        }

        return $urls;
    }

    private function createNotIswitchExpectedOutputsRCCS(\DateTime $now): array
    {
        $now->modify('+5 minutes');

        $urls = [];
        $urls[] = \sprintf('https://merchant.url?mid=123&ref=PY0000000001&cur=SGD&amt=50.00&transtype=sale&rcard=04&version=2&validity=%s&userfield1=SW001&userfield2=MS001&userfield3=EBS001&returnurl=https://return.url&statusurl=https://status.url&recurrentid=INIT&subsequentmid=123&userfield4=INV0001&signature=f57ddd32ba76d89b085423536e568aecb5fc638de7242e0e0bc71570e6f1cc45f3b30107cabc61a360a3f41a3651dcdf1c3c20230758dc22e9ec23940c9a1f03', $now->format('Y-m-d-H:i:s'));

        for ($i = 0; $i < 5; ++$i) {
            $urls[] = \sprintf('https://merchant.url?mid=123&ref=PY0000000001&cur=SGD&amt=50.00&transtype=sale&rcard=04&version=2&validity=%s&userfield1=SW001&userfield2=MS001&userfield3=EBS001&returnurl=https://return.url&statusurl=https://status.url&recurrentid=INIT&subsequentmid=123&userfield4=INV0001&signature=f57ddd32ba76d89b085423536e568aecb5fc638de7242e0e0bc71570e6f1cc45f3b30107cabc61a360a3f41a3651dcdf1c3c20230758dc22e9ec23940c9a1f03', $now->modify('+1 seconds')->format('Y-m-d-H:i:s'));
        }

        return $urls;
    }

    private function createNotIswitchExpectedOutputsNotRCCS(\DateTime $now): array
    {
        $now->modify('+5 minutes');

        $urls = [];
        $urls[] = \sprintf('https://merchant.url?mid=123&ref=PY0000000001&cur=SGD&amt=50.00&transtype=sale&rcard=04&version=2&validity=%s&userfield1=SW001&userfield2=MS001&userfield3=EBS001&returnurl=https://return.url&statusurl=https://status.url&userfield4=INV0001&signature=f57ddd32ba76d89b085423536e568aecb5fc638de7242e0e0bc71570e6f1cc45f3b30107cabc61a360a3f41a3651dcdf1c3c20230758dc22e9ec23940c9a1f03', $now->format('Y-m-d-H:i:s'));

        for ($i = 0; $i < 5; ++$i) {
            $urls[] = \sprintf('https://merchant.url?mid=123&ref=PY0000000001&cur=SGD&amt=50.00&transtype=sale&rcard=04&version=2&validity=%s&userfield1=SW001&userfield2=MS001&userfield3=EBS001&returnurl=https://return.url&statusurl=https://status.url&userfield4=INV0001&signature=f57ddd32ba76d89b085423536e568aecb5fc638de7242e0e0bc71570e6f1cc45f3b30107cabc61a360a3f41a3651dcdf1c3c20230758dc22e9ec23940c9a1f03', $now->modify('+1 seconds')->format('Y-m-d-H:i:s'));
        }

        return $urls;
    }
}
