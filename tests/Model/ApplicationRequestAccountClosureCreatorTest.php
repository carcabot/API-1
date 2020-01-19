<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestNumber;
use App\Domain\Command\CustomerAccount\UpdateAccountNumber;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\ContractPostalAddress;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountPostalAddress;
use App\Entity\Identification;
use App\Entity\Person;
use App\Entity\PostalAddress;
use App\Entity\User;
use App\Enum\AccountType;
use App\Enum\CustomerAccountStatus;
use App\Enum\IdentificationName;
use App\Model\ApplicationRequestAccountClosureCreator;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class ApplicationRequestAccountClosureCreatorTest extends TestCase
{
    public function test()
    {
        $applicationRequestData = [];
        $applicationRequestData['contractNumber'] = '123';
        $applicationRequestData['differentPayeeIndicator'] = false;
        $applicationRequestData['refundeeDetails'] = '';
        $applicationRequestData['preferredEndDate'] = '2019-05-05T16:00:00.000Z';
        $data = ['applicationRequest' => $applicationRequestData];

        $timezone = new \DateTimeZone('Asia/Singapore');

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddress = $postalAddressProphecy->reveal();

        $contractPostalAddressProphecy = $this->prophesize(ContractPostalAddress::class);
        $contractPostalAddressProphecy->getAddress()->willReturn($postalAddress);
        $contractPostalAddress = $contractPostalAddressProphecy->reveal();

        $personDetailsProphecy = $this->prophesize(Person::class);
        $personDetails = $personDetailsProphecy->reveal();

        $contractCustomerProphecy = $this->prophesize(CustomerAccount::class);
        $contractCustomerProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $contractCustomerProphecy->getPersonDetails()->willReturn($personDetails);
        $contractCustomer = $contractCustomerProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getCustomer()->willReturn($contractCustomer);
        $contractProphecy->getAddresses()->willReturn([$contractPostalAddress]);
        $contract = $contractProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setPreferredEndDate(new \DateTime('2019-05-05T16:00:00.000Z', $timezone))->shouldBeCalled();
        $applicationRequestProphecy->setContract($contract)->shouldBeCalled();
        $applicationRequestProphecy->setCustomer($contract->getCustomer())->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($contract->getCustomer()->getType())->shouldBeCalled();
        $applicationRequestProphecy->addAddress($postalAddress)->shouldBeCalled();
        $applicationRequestProphecy->setRefundee($contractCustomer)->shouldBeCalled();
        $applicationRequestProphecy->setRefundeeDetails($personDetails)->shouldBeCalled();
        $applicationRequestProphecy->setPersonDetails($personDetails)->shouldBeCalled();
        $applicationRequestProphecy->setCorporationDetails(null)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('1');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('2');
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode(['preferredEndDate' => '2019-05-05T16:00:00.000Z']), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
        ]])->willReturn($applicationRequest);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $contractRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => '123'])->willReturn($contract);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->persist($personDetails)->shouldBeCalled();
        $entityManagerProphecy->persist($postalAddress)->shouldBeCalled();
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedOutput = [
            'FRCContractClosureNumber' => '1',
            'CRMContractClosureNumber' => '2',
            'ProcessStatus' => 1,
            'Message' => 'New Application Create Successful.',
        ];

        $applicationRequestAccountClosureCreator = new ApplicationRequestAccountClosureCreator($commandBus, $entityManager, $serializerInterface, 'Asia/Singapore');
        $actualOutput = $applicationRequestAccountClosureCreator->createApplicationRequest($data);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function test1()
    {
        $applicationRequestData = [];
        $applicationRequestData['contractNumber'] = '123';
        $applicationRequestData['differentPayeeIndicator'] = false;
        $applicationRequestData['refundeeDetails'] = '';
        $applicationRequestData['preferredEndDate'] = '2019-05-05T16:00:00.000Z';
        $data = ['applicationRequest' => $applicationRequestData];

        $timezone = new \DateTimeZone('Asia/Singapore');

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddress = $postalAddressProphecy->reveal();

        $contractPostalAddressProphecy = $this->prophesize(ContractPostalAddress::class);
        $contractPostalAddressProphecy->getAddress()->willReturn($postalAddress);
        $contractPostalAddress = $contractPostalAddressProphecy->reveal();

        $CorporationDetailsProphecy = $this->prophesize(Corporation::class);
        $corporationDetails = $CorporationDetailsProphecy->reveal();

        $contractCustomerProphecy = $this->prophesize(CustomerAccount::class);
        $contractCustomerProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $contractCustomerProphecy->getCorporationDetails()->willReturn($corporationDetails);
        $contractCustomer = $contractCustomerProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getCustomer()->willReturn($contractCustomer);
        $contractProphecy->getAddresses()->willReturn([$contractPostalAddress]);
        $contract = $contractProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setPreferredEndDate(new \DateTime('2019-05-05T16:00:00.000Z', $timezone))->shouldBeCalled();
        $applicationRequestProphecy->setContract($contract)->shouldBeCalled();
        $applicationRequestProphecy->setCustomer($contract->getCustomer())->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($contract->getCustomer()->getType())->shouldBeCalled();
        $applicationRequestProphecy->addAddress($postalAddress)->shouldBeCalled();
        $applicationRequestProphecy->setRefundee($contractCustomer)->shouldBeCalled();
        $applicationRequestProphecy->setRefundeeDetails(null)->shouldBeCalled();
        $applicationRequestProphecy->setPersonDetails(null)->shouldBeCalled();
        $applicationRequestProphecy->setCorporationDetails($corporationDetails)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('1');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('2');
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode(['preferredEndDate' => '2019-05-05T16:00:00.000Z']), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
        ]])->willReturn($applicationRequest);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $contractRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => '123'])->willReturn($contract);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->persist($corporationDetails)->shouldBeCalled();
        $entityManagerProphecy->persist($postalAddress)->shouldBeCalled();
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedOutput = [
            'FRCContractClosureNumber' => '1',
            'CRMContractClosureNumber' => '2',
            'ProcessStatus' => 1,
            'Message' => 'New Application Create Successful.',
        ];

        $applicationRequestAccountClosureCreator = new ApplicationRequestAccountClosureCreator($commandBus, $entityManager, $serializerInterface, 'Asia/Singapore');
        $actualOutput = $applicationRequestAccountClosureCreator->createApplicationRequest($data);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function test2()
    {
        $applicationRequestData = [];
        $applicationRequestData['contractNumber'] = '123';
        $applicationRequestData['differentPayeeIndicator'] = true;
        $applicationRequestData['refundeeDetails']['identifiers'][0]['value'] = '';
        $applicationRequestData['preferredEndDate'] = '2019-05-05T16:00:00.000Z';
        $data = ['applicationRequest' => $applicationRequestData];

        $timezone = new \DateTimeZone('Asia/Singapore');

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddress = $postalAddressProphecy->reveal();

        $contractPostalAddressProphecy = $this->prophesize(ContractPostalAddress::class);
        $contractPostalAddressProphecy->getAddress()->willReturn($postalAddress);
        $contractPostalAddress = $contractPostalAddressProphecy->reveal();

        $personDetailsProphecy = $this->prophesize(Person::class);
        $personDetails = $personDetailsProphecy->reveal();

        $contractCustomerProphecy = $this->prophesize(CustomerAccount::class);
        $contractCustomerProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $contractCustomerProphecy->getPersonDetails()->willReturn($personDetails);
        $contractCustomer = $contractCustomerProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getCustomer()->willReturn($contractCustomer);
        $contractProphecy->getAddresses()->willReturn([$contractPostalAddress]);
        $contract = $contractProphecy->reveal();

        $customerPersonDetailsProphecy = $this->prophesize(Person::class);
        $customerPersonDetails = $customerPersonDetailsProphecy->reveal();

        $customerProphecy = $this->prophesize(CustomerAccount::class);
        $customerProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerProphecy->getPersonDetails()->willReturn($customerPersonDetails);
        $customer = $customerProphecy->reveal();

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('identity.name', ':name')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identity.value', ':nric')->shouldBeCalled();
        $queryBuilderExpressionProphecy->literal(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD)->shouldBeCalled();
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([$customer]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.personDetails', 'person')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('person.identifiers', 'identity')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderExpression->eq('identity.value', ':nric'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identity.name', ':name'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('nric', $applicationRequestData['refundeeDetails']['identifiers'][0]['value'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', $queryBuilderExpression->literal(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setPreferredEndDate(new \DateTime('2019-05-05T16:00:00.000Z', $timezone))->shouldBeCalled();
        $applicationRequestProphecy->setContract($contract)->shouldBeCalled();
        $applicationRequestProphecy->setCustomer($contract->getCustomer())->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($contract->getCustomer()->getType())->shouldBeCalled();
        $applicationRequestProphecy->addAddress($postalAddress)->shouldBeCalled();
        $applicationRequestProphecy->setRefundee($customerProphecy)->shouldBeCalled();
        $applicationRequestProphecy->setRefundeeDetails($customerPersonDetails)->shouldBeCalled();
        $applicationRequestProphecy->setPersonDetails($customerPersonDetails)->shouldBeCalled();
        $applicationRequestProphecy->setCorporationDetails(null)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('1');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('2');
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode(['preferredEndDate' => '2019-05-05T16:00:00.000Z']), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
        ]])->willReturn($applicationRequest);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $contractRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => '123'])->willReturn($contract);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->persist($postalAddress)->shouldBeCalled();
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManagerProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);
        $entityManagerProphecy->persist($customerPersonDetails)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedOutput = [
            'FRCContractClosureNumber' => '1',
            'CRMContractClosureNumber' => '2',
            'ProcessStatus' => 1,
            'Message' => 'New Application Create Successful.',
        ];

        $applicationRequestAccountClosureCreator = new ApplicationRequestAccountClosureCreator($commandBus, $entityManager, $serializerInterface, 'Asia/Singapore');
        $actualOutput = $applicationRequestAccountClosureCreator->createApplicationRequest($data);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function test3()
    {
        $applicationRequestData = [];
        $applicationRequestData['contractNumber'] = '123';
        $applicationRequestData['differentPayeeIndicator'] = true;
        $applicationRequestData['refundeeDetails']['identifiers'][0]['value'] = '';
        $applicationRequestData['preferredEndDate'] = '2019-05-05T16:00:00.000Z';
        $data = ['applicationRequest' => $applicationRequestData];

        $timezone = new \DateTimeZone('Asia/Singapore');

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddress = $postalAddressProphecy->reveal();

        $contractPostalAddressProphecy = $this->prophesize(ContractPostalAddress::class);
        $contractPostalAddressProphecy->getAddress()->willReturn($postalAddress);
        $contractPostalAddress = $contractPostalAddressProphecy->reveal();

        $corporationDetailsProphecy = $this->prophesize(Corporation::class);
        $corporationDetails = $corporationDetailsProphecy->reveal();

        $contractCustomerProphecy = $this->prophesize(CustomerAccount::class);
        $contractCustomerProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $contractCustomerProphecy->getCorporationDetails()->willReturn($corporationDetails);
        $contractCustomer = $contractCustomerProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getCustomer()->willReturn($contractCustomer);
        $contractProphecy->getAddresses()->willReturn([$contractPostalAddress]);
        $contract = $contractProphecy->reveal();

        $customerCorporationDetailsProphecy = $this->prophesize(Corporation::class);
        $customerCorporationDetails = $customerCorporationDetailsProphecy->reveal();

        $customerProphecy = $this->prophesize(CustomerAccount::class);
        $customerProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $customerProphecy->getCorporationDetails()->willReturn($customerCorporationDetails);
        $customer = $customerProphecy->reveal();

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('identity.name', ':name')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identity.value', ':nric')->shouldBeCalled();
        $queryBuilderExpressionProphecy->literal(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD)->shouldBeCalled();
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([$customer]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.personDetails', 'person')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('person.identifiers', 'identity')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderExpression->eq('identity.value', ':nric'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identity.name', ':name'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('nric', $applicationRequestData['refundeeDetails']['identifiers'][0]['value'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', $queryBuilderExpression->literal(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setPreferredEndDate(new \DateTime('2019-05-05T16:00:00.000Z', $timezone))->shouldBeCalled();
        $applicationRequestProphecy->setContract($contract)->shouldBeCalled();
        $applicationRequestProphecy->setCustomer($contract->getCustomer())->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($contract->getCustomer()->getType())->shouldBeCalled();
        $applicationRequestProphecy->addAddress($postalAddress)->shouldBeCalled();
        $applicationRequestProphecy->setRefundee($customerProphecy)->shouldBeCalled();
        $applicationRequestProphecy->setRefundeeDetails(null)->shouldBeCalled();
        $applicationRequestProphecy->setPersonDetails(null)->shouldBeCalled();
        $applicationRequestProphecy->setCorporationDetails($customerCorporationDetails)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('1');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('2');
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode(['preferredEndDate' => '2019-05-05T16:00:00.000Z']), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
        ]])->willReturn($applicationRequest);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $contractRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => '123'])->willReturn($contract);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->persist($postalAddress)->shouldBeCalled();
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManagerProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);
        $entityManagerProphecy->persist($customerCorporationDetails)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedOutput = [
            'FRCContractClosureNumber' => '1',
            'CRMContractClosureNumber' => '2',
            'ProcessStatus' => 1,
            'Message' => 'New Application Create Successful.',
        ];

        $applicationRequestAccountClosureCreator = new ApplicationRequestAccountClosureCreator($commandBus, $entityManager, $serializerInterface, 'Asia/Singapore');
        $actualOutput = $applicationRequestAccountClosureCreator->createApplicationRequest($data);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function test4()
    {
        $addressesData = [];
        $addressesData[0] = ['postalCode' => '59200'];

        $applicationRequestData = [];
        $applicationRequestData['contractNumber'] = '123';
        $applicationRequestData['differentPayeeIndicator'] = true;
        $applicationRequestData['refundeeDetails']['identifiers'][0]['value'] = '03090003888';
        $applicationRequestData['refundeeDetails']['name'] = 'asd';
        $applicationRequestData['preferredEndDate'] = '2019-05-05T16:00:00.000Z';
        $applicationRequestData['addresses'] = $addressesData;
        $data = ['applicationRequest' => $applicationRequestData];

        $timezone = new \DateTimeZone('Asia/Singapore');
        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $commandBusProphecy = $this->prophesize(CommandBus::class);

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('identity.name', ':name')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identity.value', ':nric')->shouldBeCalled();
        $queryBuilderExpressionProphecy->literal(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD)->shouldBeCalled();
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.personDetails', 'person')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('person.identifiers', 'identity')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderExpression->eq('identity.value', ':nric'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identity.name', ':name'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('nric', $applicationRequestData['refundeeDetails']['identifiers'][0]['value'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', $queryBuilderExpression->literal(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);

        $identification = new Identification();
        $identification->setName(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identification->setValue($applicationRequestData['refundeeDetails']['identifiers'][0]['value']);
        $entityManagerProphecy->persist($identification)->shouldBeCalled();

        $refundeePersonDetails = new Person();
        $refundeePersonDetails->addIdentifier($identification);
        $refundeePersonDetails->setName($applicationRequestData['refundeeDetails']['name']);
        $entityManagerProphecy->persist($refundeePersonDetails)->shouldBeCalled();

        $address = new PostalAddress();
        $address->setPostalCode('59200');

        $serializerInterfaceProphecy->deserialize(\json_encode(['postalCode' => '59200']), PostalAddress::class, 'json', ['groups' => ['postal_address_write']])->willReturn($address);
        $entityManagerProphecy->persist($address)->shouldBeCalled();

        $customerAccountPostalAddress = new CustomerAccountPostalAddress();
        $customerAccountPostalAddress->setAddress($address);

        $refundee = new CustomerAccount();
        $refundee->setPersonDetails($refundeePersonDetails);
        $refundee->addAddress($customerAccountPostalAddress);
        $refundee->setStatus(new CustomerAccountStatus(CustomerAccountStatus::ACTIVE));
        $refundee->setType(new AccountType(AccountType::INDIVIDUAL));

        $customerAccountPostalAddress->setCustomerAccount($refundee);

        $entityManagerProphecy->persist($refundeePersonDetails)->shouldBeCalled();
        $entityManagerProphecy->persist($refundee)->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateAccountNumber($refundee))->shouldBeCalled();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddress = $postalAddressProphecy->reveal();

        $contractPostalAddressProphecy = $this->prophesize(ContractPostalAddress::class);
        $contractPostalAddressProphecy->getAddress()->willReturn($postalAddress);
        $contractPostalAddress = $contractPostalAddressProphecy->reveal();

        $contractCustomerProphecy = $this->prophesize(CustomerAccount::class);
        $contractCustomerProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $contractCustomer = $contractCustomerProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getCustomer()->willReturn($contractCustomer);
        $contractProphecy->getAddresses()->willReturn([$contractPostalAddress]);
        $contract = $contractProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setPreferredEndDate(new \DateTime('2019-05-05T16:00:00.000Z', $timezone))->shouldBeCalled();
        $applicationRequestProphecy->setContract($contract)->shouldBeCalled();
        $applicationRequestProphecy->setCustomer($contract->getCustomer())->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($contract->getCustomer()->getType())->shouldBeCalled();
        $applicationRequestProphecy->addAddress($postalAddress)->shouldBeCalled();
        $applicationRequestProphecy->setRefundee($refundee)->shouldBeCalled();
        $applicationRequestProphecy->setRefundeeDetails($refundeePersonDetails)->shouldBeCalled();
        $applicationRequestProphecy->setPersonDetails($refundeePersonDetails)->shouldBeCalled();
        $applicationRequestProphecy->setCorporationDetails(null)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('1');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('2');
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy->deserialize(\json_encode(['preferredEndDate' => '2019-05-05T16:00:00.000Z', 'addresses' => [['postalCode' => '59200']]]), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
        ]])->willReturn($applicationRequest);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $contractRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => '123'])->willReturn($contract);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->persist($postalAddress)->shouldBeCalled();
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedOutput = [
            'FRCContractClosureNumber' => '1',
            'CRMContractClosureNumber' => '2',
            'ProcessStatus' => 1,
            'Message' => 'New Application Create Successful.',
        ];

        $applicationRequestAccountClosureCreator = new ApplicationRequestAccountClosureCreator($commandBus, $entityManager, $serializerInterface, 'Asia/Singapore');
        $actualOutput = $applicationRequestAccountClosureCreator->createApplicationRequest($data);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function test5()
    {
        $data = [];

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->clear()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedOutput = [
            'FRCContractClosureNumber' => '',
            'CRMContractClosureNumber' => '',
            'ProcessStatus' => 0,
            'Message' => 'No data.',
        ];

        $applicationRequestAccountClosureCreator = new ApplicationRequestAccountClosureCreator($commandBus, $entityManager, $serializerInterface, 'Asia/Singapore');
        $actualOutput = $applicationRequestAccountClosureCreator->createApplicationRequest($data);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function test6()
    {
        $applicationRequestData = [];
        $applicationRequestData['externalApplicationRequestNumber'] = '1';
        $data = ['applicationRequest' => $applicationRequestData];

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->clear()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedOutput = [
            'FRCContractClosureNumber' => '1',
            'CRMContractClosureNumber' => '',
            'ProcessStatus' => 0,
            'Message' => 'ContractNumber is required.',
        ];

        $applicationRequestAccountClosureCreator = new ApplicationRequestAccountClosureCreator($commandBus, $entityManager, $serializerInterface, 'Asia/Singapore');
        $actualOutput = $applicationRequestAccountClosureCreator->createApplicationRequest($data);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function test7()
    {
        $applicationRequestData = [];
        $applicationRequestData['contractNumber'] = '123';
        $applicationRequestData['preferredEndDate'] = '2019-05-05T16:00:00.000Z';
        $applicationRequestData['externalApplicationRequestNumber'] = '1';
        $data = ['applicationRequest' => $applicationRequestData];

        $contractProphecy = $this->prophesize(Contract::class);
        $contract = $contractProphecy->reveal();

        $contractRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => '123'])->willReturn($contract);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $dummyWrongDataToThrowExceptionProphecy = $this->prophesize(User::class);
        $dummyWrongDataToThrowException = $dummyWrongDataToThrowExceptionProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode(['preferredEndDate' => '2019-05-05T16:00:00.000Z', 'externalApplicationRequestNumber' => '1']), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
        ]])->willReturn($dummyWrongDataToThrowException);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->clear()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedOutput = [
            'FRCContractClosureNumber' => '1',
            'CRMContractClosureNumber' => '',
            'ProcessStatus' => 0,
            'Message' => 'Cannot create application request.',
        ];

        $applicationRequestAccountClosureCreator = new ApplicationRequestAccountClosureCreator($commandBus, $entityManager, $serializerInterface, 'Asia/Singapore');
        $actualOutput = $applicationRequestAccountClosureCreator->createApplicationRequest($data);

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function test8()
    {
        $data = [[]];

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->clear()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedOutput = [[
            'FRCContractClosureNumber' => '',
            'CRMContractClosureNumber' => '',
            'ProcessStatus' => 0,
            'Message' => 'No data.',
        ]];

        $applicationRequestAccountClosureCreator = new ApplicationRequestAccountClosureCreator($commandBus, $entityManager, $serializerInterface, 'Asia/Singapore');
        $actualOutput = $applicationRequestAccountClosureCreator->processArrayData($data);

        $this->assertEquals($expectedOutput, $actualOutput);
    }
}
