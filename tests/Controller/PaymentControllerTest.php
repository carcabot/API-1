<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 3/5/19
 * Time: 4:27 PM.
 */

namespace App\Tests\Controller;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Controller\PaymentController;
use App\Domain\Command\Payment\UpdatePaymentNumber;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\Payment;
use App\Enum\PaymentMode;
use App\Enum\PaymentStatus;
use App\Service\AuthenticationHelper;
use App\WebService\PaymentGateway\ClientInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Zend\Diactoros\Response\JsonResponse;

class PaymentControllerTest extends TestCase
{
    public function testPaymentRequestAction()
    {
        $data = [
            'contract' => 'testContract',
            'paymentMode' => 'MANUAL',
            'amount' => 'testAmount',
        ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccount = $customerAccountProphecy->reveal();

        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->setPaymentUrl('testPaymentUrl')->shouldBeCalled();
        $paymentProphecy->getPaymentUrl()->willReturn('testPaymentUrl');
        $payment = $paymentProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->addPayment($payment)->shouldBeCalled();
        $contract = $contractProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelperProphecy->getCustomerAccount()->willReturn($customerAccount);
        $authHelper = $authHelperProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(\json_encode([
            'amount' => 'testAmount',
            'status' => PaymentStatus::PENDING,
        ]), Payment::class, 'jsonld', ['payment_write'])->willReturn($payment);
        $serializer = $serializerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('testContract')->willReturn($contract);
        $iriConverter = $iriConverterProphecy->reveal();

        $paymentGatewayClientProphecy = $this->prophesize(ClientInterface::class);
        $paymentGatewayClientProphecy->getPaymentUrl($payment, $contract, PaymentMode::MANUAL)->willReturn('testPaymentUrl');
        $paymentGatewayClient = $paymentGatewayClientProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdatePaymentNumber($payment))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($payment)->shouldBeCalled();
        $entityManagerProphecy->persist($contract)->shouldBeCalled();
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedData = new JsonResponse(['url' => 'testPaymentUrl']);

        $paymentController = new PaymentController($authHelper, $commandBus, $entityManager, $iriConverter, $serializer, $paymentGatewayClient);
        $actualData = $paymentController->paymentRequestAction($request);

        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testPaymentRequestActionWithoutContract()
    {
        $data = [
            'paymentMode' => 'MANUAL',
            'amount' => 'testAmount',
        ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelper = $authHelperProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $serializer = $serializerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $paymentGatewayClientProphecy = $this->prophesize(ClientInterface::class);
        $paymentGatewayClient = $paymentGatewayClientProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Not enough information.');

        $paymentController = new PaymentController($authHelper, $commandBus, $entityManager, $iriConverter, $serializer, $paymentGatewayClient);
        $paymentController->paymentRequestAction($request);
    }

    public function testPaymentRequestActionWithoutCustomer()
    {
        $data = [
            'contract' => 'testContract',
            'paymentMode' => 'MANUAL',
            'amount' => 'testAmount',
        ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelperProphecy->getCustomerAccount()->willReturn(null);
        $authHelper = $authHelperProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializer = $serializerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $paymentGatewayClientProphecy = $this->prophesize(ClientInterface::class);
        $paymentGatewayClient = $paymentGatewayClientProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Not authorized.'], 400);

        $paymentController = new PaymentController($authHelper, $commandBus, $entityManager, $iriConverter, $serializer, $paymentGatewayClient);
        $actualData = $paymentController->paymentRequestAction($request);

        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
    }

    public function testPaymentRequestActionWithoutPayment()
    {
        $data = [
            'contract' => 'testContract',
            'paymentMode' => 'MANUAL',
            'amount' => 'testAmount',
        ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccount = $customerAccountProphecy->reveal();

        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->getPaymentUrl()->willReturn('testPaymentUrl');
        $payment = $paymentProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contract = $contractProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelperProphecy->getCustomerAccount()->willReturn($customerAccount);
        $authHelper = $authHelperProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(\json_encode([
            'amount' => 'testAmount',
            'status' => PaymentStatus::PENDING,
        ]), Payment::class, 'jsonld', ['payment_write'])->willReturn(null);
        $serializer = $serializerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('testContract')->willReturn($contract);
        $iriConverter = $iriConverterProphecy->reveal();

        $paymentGatewayClientProphecy = $this->prophesize(ClientInterface::class);
        $paymentGatewayClient = $paymentGatewayClientProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist(null)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Unknown error occured.'], 400);

        $paymentController = new PaymentController($authHelper, $commandBus, $entityManager, $iriConverter, $serializer, $paymentGatewayClient);
        $actualData = $paymentController->paymentRequestAction($request);

        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
    }
}
