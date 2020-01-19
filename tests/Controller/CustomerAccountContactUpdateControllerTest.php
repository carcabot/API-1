<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 3/5/19
 * Time: 11:28 AM.
 */

namespace App\Tests\Controller;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Controller\CustomerAccountContactUpdateController;
use App\Disque\JobType;
use App\Entity\ContactPoint;
use App\Entity\CustomerAccount;
use App\Entity\Person;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class CustomerAccountContactUpdateControllerTest extends TestCase
{
    public function testContactUpdateInvokeFnWithDefaultValues()
    {
        $data = [
            'id' => 'testId',
            'personDetails' => 'testPersonDetails',
        ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $customerPhoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $customerPhoneNumberProphecy->getNationalNumber()->willReturn(111111);
        $customerPhoneNumber = $customerPhoneNumberProphecy->reveal();

        $customerContactPointProphecy = $this->prophesize(ContactPoint::class);
        $customerContactPointProphecy->getEmails()->willReturn(['testemail@email.com']);
        $customerContactPointProphecy->getTelephoneNumbers()->willReturn([$customerPhoneNumber]);
        $customerContactPointProphecy->getMobilePhoneNumbers()->willReturn([$customerPhoneNumber]);
        $customerContactPointProphecy->getFaxNumbers()->willReturn([$customerPhoneNumber]);
        $customerContactPoint = $customerContactPointProphecy->reveal();

        $customerPersonProphecy = $this->prophesize(Person::class);
        $customerPersonProphecy->getName()->willReturn('testCurrentPerson');
        $customerPersonProphecy->getContactPoints()->willReturn([$customerContactPoint]);
        $customerPerson = $customerPersonProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123456);
        $customerAccountProphecy->getPersonDetails()->willReturn($customerPerson);
        $customerAccount = $customerAccountProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getNationalNumber()->willReturn(222222);
        $phoneNumber = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumber]);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumber]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumber]);
        $contactPoint = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPerson');
        $personProphecy->getContactPoints()->willReturn([$contactPoint]);
        $person = $personProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('testId')->willReturn($customerAccount);
        $iriConverter = $iriConverterProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->deserialize(\json_encode('testPersonDetails'), Person::class, 'json', ['groups' => [
            'person_write',
            'contact_point_write',
            'identification_write',
        ]])->willReturn($person);
        $serializer = $serializerProphecy->reveal();

        $webServiceQueueProphecy = $this->prophesize(Queue::class);
        $webServiceQueueProphecy->push(new Job([
            'data' => [
                'id' => 123456,
                'previousName' => 'testPerson',
            ],
            'type' => JobType::CUSTOMER_ACCOUNT_CONTACT_UPDATE,
        ]))->shouldBeCalled();
        $webServiceQueue = $webServiceQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountContactUpdateController = new CustomerAccountContactUpdateController($iriConverter, $entityManager, $serializer, $webServiceQueue);
        $customerAccountContactUpdateController->__invoke($request);
    }

    public function testContactUpdateInvokeFnWithoutCustomerAccount()
    {
        $data = [
            'id' => 'testId',
            'personDetails' => 'testPersonDetails',
        ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('testId')->willReturn(null);
        $iriConverter = $iriConverterProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializer = $serializerProphecy->reveal();

        $webServiceQueueProphecy = $this->prophesize(Queue::class);
        $webServiceQueue = $webServiceQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $expectedData = new BadRequestHttpException('Customer Account not found');

        $customerAccountContactUpdateController = new CustomerAccountContactUpdateController($iriConverter, $entityManager, $serializer, $webServiceQueue);
        $actualData = $customerAccountContactUpdateController->__invoke($request);

        $this->assertEquals($expectedData, $actualData);
    }

    public function testContactUpdateInvokeFnWithoutId()
    {
        $data = [
            'personDetails' => 'testPersonDetails',
        ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializer = $serializerProphecy->reveal();

        $webServiceQueueProphecy = $this->prophesize(Queue::class);
        $webServiceQueue = $webServiceQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $expectedData = new BadRequestHttpException('The id field is required.');

        $customerAccountContactUpdateController = new CustomerAccountContactUpdateController($iriConverter, $entityManager, $serializer, $webServiceQueue);
        $actualData = $customerAccountContactUpdateController->__invoke($request);

        $this->assertEquals($expectedData, $actualData);
    }
}
