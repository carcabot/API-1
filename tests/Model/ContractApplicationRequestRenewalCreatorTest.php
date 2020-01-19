<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 23/4/19
 * Time: 12:01 PM.
 */

namespace App\Tests\Model;

use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestNumber;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\ContractPostalAddress;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\Person;
use App\Entity\PostalAddress;
use App\Enum\AccountType;
use App\Model\ContractApplicationRequestRenewalCreator;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class ContractApplicationRequestRenewalCreatorTest extends TestCase
{
    public function testCreateApplicationRequestFunctionWithApplicationRequestAndCustomerAccountAsIndividual()
    {
        $timeZone = new \DateTimeZone('Asia/Singapore');

        $data = [];
        $data['applicationRequest'] = [
            'preferredStartDate' => '2019-05-05T16:00:00.000Z',
            'externalApplicationRequestNumber' => 'ESWAP123456',
        ];
        $data['contract'] = [
            'contractNumber' => 'SWCC123456',
        ];

        $personDetailsProphecy = $this->prophesize(Person::class);
        $personDetails = $personDetailsProphecy->reveal();

        $contractContactPersonProphecy = $this->prophesize(CustomerAccount::class);
        $contractContactPerson = $contractContactPersonProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy->getPersonDetails()->willReturn($personDetails);
        $customerAccountProphecy->getCorporationDetails()->willReturn(null);
        $customerAccount = $customerAccountProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddress = $postalAddressProphecy->reveal();

        $contractPostalAddressProphecy = $this->prophesize(ContractPostalAddress::class);
        $contractPostalAddressProphecy->getAddress()->willReturn($postalAddress);
        $contractPostalAddress = $contractPostalAddressProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getAddresses()->willReturn([$contractPostalAddress]);
        $contractProphecy->getCustomer()->willReturn($customerAccount);
        $contractProphecy->getContactPerson()->willReturn($contractContactPerson);
        $contract = $contractProphecy->reveal();

        $contractRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => 'SWCC123456'])->willReturn($contract);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $startDate = new \DateTime('2019-05-05T16:00:00.000Z', $timeZone);
        $utcTimeZone = new \DateTimeZone('UTC');

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->addAddress($postalAddress)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('ESWAP123456');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->setContract($contract)->shouldBeCalled();
        $applicationRequestProphecy->setCustomer($customerAccount)->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($customerAccount->getType())->shouldBeCalled();
        $applicationRequestProphecy->setPreferredStartDate($startDate->setTimezone($utcTimeZone))->shouldBeCalled();
        $applicationRequestProphecy->setPersonDetails($personDetails)->shouldBeCalled();
        $applicationRequestProphecy->setContactPerson($contractContactPerson)->shouldBeCalled();
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode(['preferredStartDate' => '2019-05-05T16:00:00.000Z', 'externalApplicationRequestNumber' => 'ESWAP123456']), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'digital_document_write',
        ]])->willReturn($applicationRequest);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->persist($personDetails)->shouldBeCalled();
        $entityManagerProphecy->persist($postalAddress)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $loggerProphecy = $this->prophesize(Logger::class);
        $logger = $loggerProphecy->reveal();

        $expectedResult = [
            'FRCReContractNumber' => 'ESWAP123456',
            'CRMFRCReContractNumber' => 'SWAP123456',
            'ProcessStatus' => 1,
            'Message' => 'New Application Create Successful.',
        ];

        $contractApplicationRequestRenewalCreator = new ContractApplicationRequestRenewalCreator('', 'Asia/Singapore', $logger, $commandBus, $entityManager, $serializerInterface);
        $actualResult = $contractApplicationRequestRenewalCreator->createApplicationRequest($data);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testCreateApplicationRequestFunctionWithApplicationRequestAndNoContractFound()
    {
        $data = [];
        $data['applicationRequest'] = [
            'preferredStartDate' => '2019-05-05T16:00:00.000Z',
            'externalApplicationRequestNumber' => 'ESWAP123456',
        ];
        $data['contract'] = [
            'contractNumber' => 'SWCC123456',
        ];

        $expectedResult = [
            'FRCReContractNumber' => 'ESWAP123456',
            'CRMFRCReContractNumber' => '',
            'ProcessStatus' => 0,
            'Message' => 'Mass uploading contract renewal fail. Contract with number SWCC123456 does not exist.',
        ];

        $loggerProphecy = $this->prophesize(Logger::class);
        $logger = $loggerProphecy->reveal();

        $contractRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => 'SWCC123456'])->willReturn(null);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->clear()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $serializationInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializationInterface = $serializationInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $contractApplicationRequestRenewalCreator = new ContractApplicationRequestRenewalCreator('', 'Asia/Singapore', $logger, $commandBus, $entityManager, $serializationInterface);

        $actualResult = $contractApplicationRequestRenewalCreator->createApplicationRequest($data);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testCreateApplicationRequestFunctionWithApplicationRequestAndNoContractData()
    {
        $data = [];
        $data['applicationRequest'] = [
            'preferredStartDate' => '2019-05-05T16:00:00.000Z',
            'externalApplicationRequestNumber' => 'ESWAP123456',
        ];
        $data['contract'] = null;
        $expectedResult = [
            'FRCReContractNumber' => 'ESWAP123456',
            'CRMFRCReContractNumber' => '',
            'ProcessStatus' => 0,
            'Message' => 'Mass uploading contract renewal fail. No contract in file.',
        ];

        $loggerProphecy = $this->prophesize(Logger::class);
        $logger = $loggerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->clear()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $serializationInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializationInterface = $serializationInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $contractApplicationRequestRenewalCreator = new ContractApplicationRequestRenewalCreator('', 'Asia/Singapore', $logger, $commandBus, $entityManager, $serializationInterface);

        $actualResult = $contractApplicationRequestRenewalCreator->createApplicationRequest($data);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testCreateApplicationRequestFunctionWithoutApplicationRequest()
    {
        $data = [];
        $data['applicationRequest'] = null;
        $data['contract'] = null;
        $expectedResult = [
            'FRCReContractNumber' => '',
            'CRMFRCReContractNumber' => '',
            'ProcessStatus' => 0,
            'Message' => 'Mass uploading contract renewal fail. No data.',
        ];

        $loggerProphecy = $this->prophesize(Logger::class);
        $logger = $loggerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->clear()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $serializationInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializationInterface = $serializationInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $contractApplicationRequestRenewalCreator = new ContractApplicationRequestRenewalCreator('', 'Asia/Singapore', $logger, $commandBus, $entityManager, $serializationInterface);

        $actualResult = $contractApplicationRequestRenewalCreator->createApplicationRequest($data);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testProcessArrayDataFunction()
    {
        $timeZone = new \DateTimeZone('Asia/Singapore');

        $data = [];
        $data['applicationRequest'] = [
            'preferredStartDate' => '2019-05-05T16:00:00.000Z',
            'externalApplicationRequestNumber' => 'ESWAP123456',
        ];
        $data['contract'] = [
            'contractNumber' => 'SWCC123456',
        ];

        $corporationProphecy = $this->prophesize(Corporation::class);
        $corporation = $corporationProphecy->reveal();

        $contractContactPersonProphecy = $this->prophesize(CustomerAccount::class);
        $contractContactPerson = $contractContactPersonProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy->getPersonDetails()->willReturn(null);
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporation);
        $customerAccount = $customerAccountProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddress = $postalAddressProphecy->reveal();

        $contractPostalAddressProphecy = $this->prophesize(ContractPostalAddress::class);
        $contractPostalAddressProphecy->getAddress()->willReturn($postalAddress);
        $contractPostalAddress = $contractPostalAddressProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getAddresses()->willReturn([$contractPostalAddress]);
        $contractProphecy->getCustomer()->willReturn($customerAccount);
        $contractProphecy->getContactPerson()->willReturn($contractContactPerson);
        $contract = $contractProphecy->reveal();

        $contractRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => 'SWCC123456'])->willReturn($contract);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $startDate = new \DateTime('2019-05-05T16:00:00.000Z', $timeZone);
        $utcTimeZone = new \DateTimeZone('UTC');

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->addAddress($postalAddress)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('ESWAP123456');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->setContract($contract)->shouldBeCalled();
        $applicationRequestProphecy->setCustomer($customerAccount)->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($customerAccount->getType())->shouldBeCalled();
        $applicationRequestProphecy->setPreferredStartDate($startDate->setTimezone($utcTimeZone))->shouldBeCalled();
        $applicationRequestProphecy->setCorporationDetails($corporation)->shouldBeCalled();
        $applicationRequestProphecy->setContactPerson($contractContactPerson)->shouldBeCalled();
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode(['preferredStartDate' => '2019-05-05T16:00:00.000Z', 'externalApplicationRequestNumber' => 'ESWAP123456']), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'digital_document_write',
        ]])->willReturn($applicationRequest);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->persist($corporation)->shouldBeCalled();
        $entityManagerProphecy->persist($postalAddress)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $loggerProphecy = $this->prophesize(Logger::class);
        $logger = $loggerProphecy->reveal();

        $expectedResult = [
            [
                'FRCReContractNumber' => 'ESWAP123456',
                'CRMFRCReContractNumber' => 'SWAP123456',
                'ProcessStatus' => 1,
                'Message' => 'New Application Create Successful.',
            ],
        ];

        $contractApplicationRequestRenewalCreator = new ContractApplicationRequestRenewalCreator('', 'Asia/Singapore', $logger, $commandBus, $entityManager, $serializerInterface);
        $actualResult = $contractApplicationRequestRenewalCreator->processArrayData([$data]);

        $this->assertEquals($expectedResult, $actualResult);
    }
}
