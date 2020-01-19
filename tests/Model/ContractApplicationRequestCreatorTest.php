<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestNumber;
use App\Domain\Command\CustomerAccount\UpdateAccountNumber;
use App\Domain\Command\CustomerAccount\UpdateReferralCode;
use App\Entity\ApplicationRequest;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\Person;
use App\Entity\PostalAddress;
use App\Entity\TariffRate;
use App\Enum\AccountCategory;
use App\Enum\AccountType;
use App\Enum\CustomerAccountStatus;
use App\Model\ContractApplicationRequestCreator;
use App\WebService\Billing\Services\DataMapper;
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

class ContractApplicationRequestCreatorTest extends TestCase
{
    public function testProcessArrayDataIndividual()
    {
        $timeZone = new \DateTimeZone('Asia/Singapore');
        $startDate = '2022-05-05';
        $endDate = '2019-05-05';
        $nric = [];
        $nric['name'] = 'nric';
        $nric['value'] = 'nric1';
        $applicationRequestData = [];
        $applicationRequestData['tariffRate']['tariffRateNumber'] = '123';
        $applicationRequestData['acquirerCode'] = 'ac1';
        $applicationRequestData['customer']['personDetails']['identifiers'] = [$nric];
        $applicationRequestData['customer']['personDetails']['name'] = 'shaboo';
        $applicationRequestData['preferredStartDate'] = $startDate;
        $applicationRequestData['preferredEndDate'] = $endDate;

        $applicationRequestDataToDeserialize = [];
        $applicationRequestDataToDeserialize['acquirerCode'] = 'ac1';
        $applicationRequestDataToDeserialize['preferredStartDate'] = $startDate;
        $applicationRequestDataToDeserialize['preferredEndDate'] = $endDate;

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->setIsBasedOn($tariffRateProphecy)->shouldBeCalled();
        $tariffRate = $tariffRateProphecy->reveal();

        $tariffRateRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => '123', 'isBasedOn' => null])->willReturn($tariffRate);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $acquiredFromProphecy = $this->prophesize(CustomerAccount::class);
        $acquiredFrom = $acquiredFromProphecy->reveal();

        $existingCustomerPersonDetailsProphecy = $this->prophesize(Person::class);
        $existingCustomerPersonDetails = $existingCustomerPersonDetailsProphecy->reveal();

        $existingCustomerProphecy = $this->prophesize(CustomerAccount::class);
        $existingCustomerProphecy->getPersonDetails()->willReturn($existingCustomerPersonDetails);
        $existingCustomerProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $existingCustomer = $existingCustomerProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->findOneBy(['accountNumber' => 'ac1'])->willReturn($acquiredFrom);

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('details.name', ':name')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identifiers.name', ':identityName')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identifiers.value', ':value')->shouldBeCalled();
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([$existingCustomer]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.personDetails', 'details')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('details.identifiers', 'identifiers')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderExpression->eq('details.name', ':name'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identifiers.name', ':identityName'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identifiers.value', ':value'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', 'shaboo')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('identityName', $nric['name'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('value', $nric['value'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getAddressCountry()->willReturn(null);
        $postalAddressProphecy->getAddressLocality()->willReturn(null);
        $postalAddressProphecy->setAddressCountry('SG')->shouldBeCalled();
        $postalAddressProphecy->setAddressLocality('SINGAPORE')->shouldBeCalled();
        $postalAddress = $postalAddressProphecy->reveal();

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapContractSubtype('LANDED')->willReturn('Landed');
        $dataMapper = $dataMapperProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setCustomer($existingCustomer)->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($existingCustomer->getType())->shouldBeCalled();
        $applicationRequestProphecy->setPersonDetails($existingCustomerPersonDetails)->shouldBeCalled();
        $applicationRequestProphecy->setContactPerson($existingCustomer)->shouldBeCalled();
        $applicationRequestProphecy->setPreferredStartDate(new \DateTime($startDate, $timeZone))->shouldBeCalled();
        $applicationRequestProphecy->setPreferredEndDate(new \DateTime($endDate, $timeZone))->shouldBeCalled();
        $applicationRequestProphecy->setAcquiredFrom($acquiredFrom)->shouldBeCalled();
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddress]);
        $applicationRequestProphecy->getContractSubtype()->willReturn('LANDED');
        $applicationRequestProphecy->setContractSubtype('Landed')->shouldBeCalled();
        $applicationRequestProphecy->setTariffRate($tariffRate)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('ex1');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('ap1');
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode($applicationRequestDataToDeserialize), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
            'digital_document_write',
            'person_write',
            'identification_write',
            'contact_point_write',
        ]])->willReturn($applicationRequest);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerInterfaceProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerInterfaceProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManagerInterfaceProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);
        $entityManagerInterfaceProphecy->persist($tariffRate)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($existingCustomerPersonDetails)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerInterfaceProphecy->getConnection()->willReturn($connection);
        $entityManagerInterfaceProphecy->flush()->shouldBeCalled();
        $entityManagerInterface = $entityManagerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $expectedOutput = [[
            'FRCContractApplicationNumber' => 'ex1',
            'CRMContractApplicationNumber' => 'ap1',
            'ProcessStatus' => 1,
            'Message' => 'New Application Create Successful.',
        ]];

        $contractApplicationRequestCreator = new ContractApplicationRequestCreator('', 'Asia/Singapore', $commandBus, $entityManagerInterface, $serializerInterface, $dataMapper);
        $actualOutput = $contractApplicationRequestCreator->processArrayData([['applicationRequest' => $applicationRequestData]]);

        $this->assertEquals($actualOutput, $expectedOutput);
    }

    public function testProcessArrayDataCorporate()
    {
        $timeZone = new \DateTimeZone('Asia/Singapore');
        $startDate = '2022-05-05';
        $endDate = '2019-05-05';
        $nric = [];
        $nric['name'] = 'nric';
        $nric['value'] = 'nric1';
        $applicationRequestData = [];
        $applicationRequestData['tariffRate']['tariffRateNumber'] = '123';
        $applicationRequestData['acquirerCode'] = 'ac1';
        $applicationRequestData['customer']['corporationDetails']['identifiers'] = [$nric];
        $applicationRequestData['customer']['corporationDetails']['name'] = 'shaboo';
        $applicationRequestData['preferredStartDate'] = $startDate;
        $applicationRequestData['preferredEndDate'] = $endDate;
        $applicationRequestData['contactPerson']['personDetails']['contactPoints']['0']['emails'][0] = 'shaboo@shaboo.com';
        $applicationRequestData['contactPerson']['personDetails']['name'] = 'Mo Shaboo';

        $applicationRequestDataToDeserialize = [];
        $applicationRequestDataToDeserialize['acquirerCode'] = 'ac1';
        $applicationRequestDataToDeserialize['preferredStartDate'] = $startDate;
        $applicationRequestDataToDeserialize['preferredEndDate'] = $endDate;

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->setIsBasedOn($tariffRateProphecy)->shouldBeCalled();
        $tariffRate = $tariffRateProphecy->reveal();

        $tariffRateRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => '123', 'isBasedOn' => null])->willReturn($tariffRate);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $acquiredFromProphecy = $this->prophesize(CustomerAccount::class);
        $acquiredFrom = $acquiredFromProphecy->reveal();

        $existingCustomerCorporationDetailsProphecy = $this->prophesize(Corporation::class);
        $existingCustomerCorporationDetails = $existingCustomerCorporationDetailsProphecy->reveal();

        $existingCustomerProphecy = $this->prophesize(CustomerAccount::class);
        $existingCustomerProphecy->getCorporationDetails()->willReturn($existingCustomerCorporationDetails);
        $existingCustomerProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $existingCustomer = $existingCustomerProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->findOneBy(['accountNumber' => 'ac1'])->willReturn($acquiredFrom);

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('details.name', ':name')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identifiers.name', ':identityName')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identifiers.value', ':value')->shouldBeCalled();
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([$existingCustomer]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.corporationDetails', 'details')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('details.identifiers', 'identifiers')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderExpression->eq('details.name', ':name'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identifiers.name', ':identityName'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identifiers.value', ':value'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', 'shaboo')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('identityName', $nric['name'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('value', $nric['value'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);

        // Contact Person Section.
        $contactPersonPersonDetailsProphecy = $this->prophesize(Person::class);
        $contactPersonPersonDetails = $contactPersonPersonDetailsProphecy->reveal();

        $contactPersonProphecy = $this->prophesize(CustomerAccount::class);
        $contactPersonProphecy->getPersonDetails()->willReturn($contactPersonPersonDetails);
        $contactPerson = $contactPersonProphecy->reveal();

        $expressionComparisonProphecy = $this->prophesize(Expr\Comparison::class);
        $expressionComparison = $expressionComparisonProphecy->reveal();

        $expressionAndXProphecy = $this->prophesize(Expr\Andx::class);
        $expressionAndX = $expressionAndXProphecy->reveal();

        $contactPersonQueryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $contactPersonQueryBuilderExpressionProphecy->literal(true)->shouldBeCalled()->willReturn(new Expr\Literal());
        $contactPersonQueryBuilderExpressionProphecy->eq('person.name', ':contactPersonName')->shouldBeCalled()->willReturn($expressionComparison);
        $contactPersonQueryBuilderExpressionProphecy->eq(\sprintf(<<<'SQL'
                        jsonb_contains(CAST(lower(CAST(%s.%s AS text)) AS jsonb), :%s)
SQL
            , 'contactPoint', 'emails', 'email'), new Expr\Literal())->shouldBeCalled()->willReturn($expressionComparison);
        $contactPersonQueryBuilderExpressionProphecy->andX($expressionComparison, $expressionComparison)->shouldBeCalled()->willReturn($expressionAndX);
        $contactPersonQueryBuilderExpression = $contactPersonQueryBuilderExpressionProphecy->reveal();

        $contactPersonQueryProphecy = $this->prophesize(AbstractQuery::class);
        $contactPersonQueryProphecy->getResult()->willReturn([$contactPerson]);
        $contactPersonQuery = $contactPersonQueryProphecy->reveal();

        $contactPersonQueryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $contactPersonQueryBuilderProphecy->expr()->willReturn($contactPersonQueryBuilderExpression);
        $contactPersonQueryBuilderProphecy->leftJoin('customerAccount.personDetails', 'person')->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->leftJoin('person.contactPoints', 'contactPoint')->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->where($expressionAndX)->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->setParameter('email', \json_encode(\strtolower('shaboo@shaboo.com')))->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->setParameter('contactPersonName', 'Mo Shaboo')->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->getQuery()->willReturn($contactPersonQuery);
        $contactPersonQueryBuilder = $contactPersonQueryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy->createQueryBuilder('customerAccount')->willReturn($contactPersonQueryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();
        //Contact Person Section.

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getAddressCountry()->willReturn(null);
        $postalAddressProphecy->getAddressLocality()->willReturn(null);
        $postalAddressProphecy->setAddressCountry('SG')->shouldBeCalled();
        $postalAddressProphecy->setAddressLocality('SINGAPORE')->shouldBeCalled();
        $postalAddress = $postalAddressProphecy->reveal();

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapContractSubtype('LANDED')->willReturn('Landed');
        $dataMapper = $dataMapperProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setCustomer($existingCustomer)->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($existingCustomer->getType())->shouldBeCalled();
        $applicationRequestProphecy->setPersonDetails($contactPersonPersonDetails)->shouldBeCalled();
        $applicationRequestProphecy->setCorporationDetails($existingCustomerCorporationDetails)->shouldBeCalled();
        $applicationRequestProphecy->setContactPerson($contactPerson)->shouldBeCalled();
        $applicationRequestProphecy->setPreferredStartDate(new \DateTime($startDate, $timeZone))->shouldBeCalled();
        $applicationRequestProphecy->setPreferredEndDate(new \DateTime($endDate, $timeZone))->shouldBeCalled();
        $applicationRequestProphecy->setAcquiredFrom($acquiredFrom)->shouldBeCalled();
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddress]);
        $applicationRequestProphecy->getContractSubtype()->willReturn('LANDED');
        $applicationRequestProphecy->setContractSubtype('Landed')->shouldBeCalled();
        $applicationRequestProphecy->setTariffRate($tariffRate)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('ex1');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('ap1');
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode($applicationRequestDataToDeserialize), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
            'digital_document_write',
            'person_write',
            'identification_write',
            'contact_point_write',
        ]])->willReturn($applicationRequest);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerInterfaceProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerInterfaceProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManagerInterfaceProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);
        $entityManagerInterfaceProphecy->persist($tariffRate)->shouldBeCalled();
//        $entityManagerInterfaceProphecy->persist($contactPersonPersonDetails)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($existingCustomerCorporationDetails)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerInterfaceProphecy->getConnection()->willReturn($connection);
        $entityManagerInterfaceProphecy->flush()->shouldBeCalled();
        $entityManagerInterface = $entityManagerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $expectedOutput = [[
            'FRCContractApplicationNumber' => 'ex1',
            'CRMContractApplicationNumber' => 'ap1',
            'ProcessStatus' => 1,
            'Message' => 'New Application Create Successful.',
        ]];

        $contractApplicationRequestCreator = new ContractApplicationRequestCreator('', 'Asia/Singapore', $commandBus, $entityManagerInterface, $serializerInterface, $dataMapper);
        $actualOutput = $contractApplicationRequestCreator->processArrayData([['applicationRequest' => $applicationRequestData]]);

        $this->assertEquals($actualOutput, $expectedOutput);
    }

    public function testProcessArrayDataCorporateNewContactPerson()
    {
        $timeZone = new \DateTimeZone('Asia/Singapore');
        $startDate = '2022-05-05';
        $endDate = '2019-05-05';
        $nric = [];
        $nric['name'] = 'nric';
        $nric['value'] = 'nric1';
        $applicationRequestData = [];
        $applicationRequestData['tariffRate']['tariffRateNumber'] = '123';
        $applicationRequestData['acquirerCode'] = 'ac1';
        $applicationRequestData['customer']['corporationDetails']['identifiers'] = [$nric];
        $applicationRequestData['customer']['corporationDetails']['name'] = 'shaboo';
        $applicationRequestData['preferredStartDate'] = $startDate;
        $applicationRequestData['preferredEndDate'] = $endDate;
        $applicationRequestData['contactPerson']['personDetails']['contactPoints']['0']['emails'][0] = 'shaboo@shaboo.com';
        $applicationRequestData['contactPerson']['personDetails']['name'] = 'Mo Shaboo';

        $applicationRequestDataToDeserialize = [];
        $applicationRequestDataToDeserialize['acquirerCode'] = 'ac1';
        $applicationRequestDataToDeserialize['preferredStartDate'] = $startDate;
        $applicationRequestDataToDeserialize['preferredEndDate'] = $endDate;

        $contactPersonDataToDeserialize = [];
        $contactPersonDataToDeserialize['personDetails']['contactPoints']['0']['emails'][0] = 'shaboo@shaboo.com';
        $contactPersonDataToDeserialize['personDetails']['name'] = 'Mo Shaboo';

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->setIsBasedOn($tariffRateProphecy)->shouldBeCalled();
        $tariffRate = $tariffRateProphecy->reveal();

        $tariffRateRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => '123', 'isBasedOn' => null])->willReturn($tariffRate);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $acquiredFromProphecy = $this->prophesize(CustomerAccount::class);
        $acquiredFrom = $acquiredFromProphecy->reveal();

        $existingCustomerCorporationDetailsProphecy = $this->prophesize(Corporation::class);
        $existingCustomerCorporationDetails = $existingCustomerCorporationDetailsProphecy->reveal();

        $existingCustomerProphecy = $this->prophesize(CustomerAccount::class);
        $existingCustomerProphecy->getCorporationDetails()->willReturn($existingCustomerCorporationDetails);
        $existingCustomerProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $existingCustomer = $existingCustomerProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->findOneBy(['accountNumber' => 'ac1'])->willReturn($acquiredFrom);

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('details.name', ':name')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identifiers.name', ':identityName')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identifiers.value', ':value')->shouldBeCalled();
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([$existingCustomer]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.corporationDetails', 'details')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('details.identifiers', 'identifiers')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderExpression->eq('details.name', ':name'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identifiers.name', ':identityName'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identifiers.value', ':value'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', 'shaboo')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('identityName', $nric['name'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('value', $nric['value'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);

        // Contact Person Section.
        $contactPersonPersonDetailsProphecy = $this->prophesize(Person::class);
        $contactPersonPersonDetails = $contactPersonPersonDetailsProphecy->reveal();

        $contactPersonProphecy = $this->prophesize(CustomerAccount::class);
        $contactPersonProphecy->addCategory(AccountCategory::CONTACT_PERSON)->shouldBeCalled();
        $contactPersonProphecy->setType(new AccountType(AccountType::INDIVIDUAL))->shouldBeCalled();
        $contactPersonProphecy->setStatus(new CustomerAccountStatus(CustomerAccountStatus::INACTIVE))->shouldBeCalled();
        $contactPersonProphecy->getPersonDetails()->willReturn($contactPersonPersonDetails);
        $contactPerson = $contactPersonProphecy->reveal();

        $expressionComparisonProphecy = $this->prophesize(Expr\Comparison::class);
        $expressionComparison = $expressionComparisonProphecy->reveal();

        $expressionAndXProphecy = $this->prophesize(Expr\Andx::class);
        $expressionAndX = $expressionAndXProphecy->reveal();

        $contactPersonQueryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $contactPersonQueryBuilderExpressionProphecy->literal(true)->shouldBeCalled()->willReturn(new Expr\Literal());
        $contactPersonQueryBuilderExpressionProphecy->eq('person.name', ':contactPersonName')->shouldBeCalled()->willReturn($expressionComparison);
        $contactPersonQueryBuilderExpressionProphecy->eq(\sprintf(<<<'SQL'
                        jsonb_contains(CAST(lower(CAST(%s.%s AS text)) AS jsonb), :%s)
SQL
            , 'contactPoint', 'emails', 'email'), new Expr\Literal())->shouldBeCalled()->willReturn($expressionComparison);
        $contactPersonQueryBuilderExpressionProphecy->andX($expressionComparison, $expressionComparison)->shouldBeCalled()->willReturn($expressionAndX);
        $contactPersonQueryBuilderExpression = $contactPersonQueryBuilderExpressionProphecy->reveal();

        $contactPersonQueryProphecy = $this->prophesize(AbstractQuery::class);
        $contactPersonQueryProphecy->getResult()->willReturn([]);
        $contactPersonQuery = $contactPersonQueryProphecy->reveal();

        $contactPersonQueryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $contactPersonQueryBuilderProphecy->expr()->willReturn($contactPersonQueryBuilderExpression);
        $contactPersonQueryBuilderProphecy->leftJoin('customerAccount.personDetails', 'person')->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->leftJoin('person.contactPoints', 'contactPoint')->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->where($expressionAndX)->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->setParameter('email', \json_encode(\strtolower('shaboo@shaboo.com')))->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->setParameter('contactPersonName', 'Mo Shaboo')->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->getQuery()->willReturn($contactPersonQuery);
        $contactPersonQueryBuilder = $contactPersonQueryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy->createQueryBuilder('customerAccount')->willReturn($contactPersonQueryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();
        //Contact Person Section.

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getAddressCountry()->willReturn(null);
        $postalAddressProphecy->getAddressLocality()->willReturn(null);
        $postalAddressProphecy->setAddressCountry('SG')->shouldBeCalled();
        $postalAddressProphecy->setAddressLocality('SINGAPORE')->shouldBeCalled();
        $postalAddress = $postalAddressProphecy->reveal();

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapContractSubtype('LANDED')->willReturn('Landed');
        $dataMapper = $dataMapperProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setCustomer($existingCustomer)->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($existingCustomer->getType())->shouldBeCalled();
        $applicationRequestProphecy->setPersonDetails($contactPersonPersonDetails)->shouldBeCalled();
        $applicationRequestProphecy->setCorporationDetails($existingCustomerCorporationDetails)->shouldBeCalled();
        $applicationRequestProphecy->setContactPerson($contactPerson)->shouldBeCalled();
        $applicationRequestProphecy->setPreferredStartDate(new \DateTime($startDate, $timeZone))->shouldBeCalled();
        $applicationRequestProphecy->setPreferredEndDate(new \DateTime($endDate, $timeZone))->shouldBeCalled();
        $applicationRequestProphecy->setAcquiredFrom($acquiredFrom)->shouldBeCalled();
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddress]);
        $applicationRequestProphecy->getContractSubtype()->willReturn('LANDED');
        $applicationRequestProphecy->setContractSubtype('Landed')->shouldBeCalled();
        $applicationRequestProphecy->setTariffRate($tariffRate)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('ex1');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('ap1');
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode($applicationRequestDataToDeserialize), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
            'digital_document_write',
            'person_write',
            'identification_write',
            'contact_point_write',
        ]])->willReturn($applicationRequest);
        $serializerInterfaceProphecy->deserialize(\json_encode($contactPersonDataToDeserialize), CustomerAccount::class, 'json', ['groups' => [
            'customer_account_write',
            'person_write',
            'identification_write',
            'contact_point_write',
        ]])->willReturn($contactPerson);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerInterfaceProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerInterfaceProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManagerInterfaceProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);
        $entityManagerInterfaceProphecy->persist($tariffRate)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($contactPersonPersonDetails)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($existingCustomerCorporationDetails)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($contactPerson)->shouldBeCalled();
        $entityManagerInterfaceProphecy->getConnection()->willReturn($connection);
        $entityManagerInterfaceProphecy->flush()->shouldBeCalled();
        $entityManagerInterface = $entityManagerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateAccountNumber($contactPerson))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateReferralCode($contactPerson))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $expectedOutput = [[
            'FRCContractApplicationNumber' => 'ex1',
            'CRMContractApplicationNumber' => 'ap1',
            'ProcessStatus' => 1,
            'Message' => 'New Application Create Successful.',
        ]];

        $contractApplicationRequestCreator = new ContractApplicationRequestCreator('', 'Asia/Singapore', $commandBus, $entityManagerInterface, $serializerInterface, $dataMapper);
        $actualOutput = $contractApplicationRequestCreator->processArrayData([['applicationRequest' => $applicationRequestData]]);

        $this->assertEquals($actualOutput, $expectedOutput);
    }

    public function testProcessArrayDataIndividualNewCustomer()
    {
        $timeZone = new \DateTimeZone('Asia/Singapore');
        $startDate = '2022-05-05';
        $endDate = '2019-05-05';
        $nric = [];
        $nric['name'] = 'nric';
        $nric['value'] = 'nric1';
        $applicationRequestData = [];
        $applicationRequestData['tariffRate']['tariffRateNumber'] = '123';
        $applicationRequestData['acquirerCode'] = 'ac1';
        $applicationRequestData['customer']['personDetails']['identifiers'] = [$nric];
        $applicationRequestData['customer']['personDetails']['name'] = 'shaboo';
        $applicationRequestData['preferredStartDate'] = $startDate;
        $applicationRequestData['preferredEndDate'] = $endDate;

        $applicationRequestDataToDeserialize = [];
        $applicationRequestDataToDeserialize['acquirerCode'] = 'ac1';
        $applicationRequestDataToDeserialize['preferredStartDate'] = $startDate;
        $applicationRequestDataToDeserialize['preferredEndDate'] = $endDate;

        $customerDataToDeserialize = [];
        $customerDataToDeserialize['personDetails']['identifiers'] = [$nric];
        $customerDataToDeserialize['personDetails']['name'] = 'shaboo';

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->setIsBasedOn($tariffRateProphecy)->shouldBeCalled();
        $tariffRate = $tariffRateProphecy->reveal();

        $tariffRateRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => '123', 'isBasedOn' => null])->willReturn($tariffRate);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $acquiredFromProphecy = $this->prophesize(CustomerAccount::class);
        $acquiredFrom = $acquiredFromProphecy->reveal();

        $existingCustomerPersonDetailsProphecy = $this->prophesize(Person::class);
        $existingCustomerPersonDetails = $existingCustomerPersonDetailsProphecy->reveal();

        $existingCustomerProphecy = $this->prophesize(CustomerAccount::class);
        $existingCustomerProphecy->setStatus(new CustomerAccountStatus(CustomerAccountStatus::INACTIVE))->shouldBeCalled();
        $existingCustomerProphecy->addCategory(AccountCategory::CUSTOMER)->shouldBeCalled();
        $existingCustomerProphecy->getPersonDetails()->willReturn($existingCustomerPersonDetails);
        $existingCustomerProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $existingCustomer = $existingCustomerProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->findOneBy(['accountNumber' => 'ac1'])->willReturn($acquiredFrom);

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('details.name', ':name')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identifiers.name', ':identityName')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identifiers.value', ':value')->shouldBeCalled();
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.personDetails', 'details')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('details.identifiers', 'identifiers')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderExpression->eq('details.name', ':name'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identifiers.name', ':identityName'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identifiers.value', ':value'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', 'shaboo')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('identityName', $nric['name'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('value', $nric['value'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getAddressCountry()->willReturn(null);
        $postalAddressProphecy->getAddressLocality()->willReturn(null);
        $postalAddressProphecy->setAddressCountry('SG')->shouldBeCalled();
        $postalAddressProphecy->setAddressLocality('SINGAPORE')->shouldBeCalled();
        $postalAddress = $postalAddressProphecy->reveal();

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapContractSubtype('LANDED')->willReturn('Landed');
        $dataMapper = $dataMapperProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setCustomer($existingCustomer)->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($existingCustomer->getType())->shouldBeCalled();
        $applicationRequestProphecy->setPersonDetails($existingCustomerPersonDetails)->shouldBeCalled();
        $applicationRequestProphecy->setContactPerson($existingCustomer)->shouldBeCalled();
        $applicationRequestProphecy->setPreferredStartDate(new \DateTime($startDate, $timeZone))->shouldBeCalled();
        $applicationRequestProphecy->setPreferredEndDate(new \DateTime($endDate, $timeZone))->shouldBeCalled();
        $applicationRequestProphecy->setAcquiredFrom($acquiredFrom)->shouldBeCalled();
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddress]);
        $applicationRequestProphecy->getContractSubtype()->willReturn('LANDED');
        $applicationRequestProphecy->setContractSubtype('Landed')->shouldBeCalled();
        $applicationRequestProphecy->setTariffRate($tariffRate)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('ex1');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('ap1');
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode($applicationRequestDataToDeserialize), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
            'digital_document_write',
            'person_write',
            'identification_write',
            'contact_point_write',
        ]])->willReturn($applicationRequest);
        $serializerInterfaceProphecy->deserialize(\json_encode($customerDataToDeserialize), CustomerAccount::class, 'json', ['groups' => [
            'customer_account_write',
            'person_write',
            'corporation_write',
            'identification_write',
            'contact_point_write',
        ]])->willReturn($existingCustomer);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerInterfaceProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerInterfaceProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManagerInterfaceProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);
        $entityManagerInterfaceProphecy->persist($tariffRate)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($existingCustomerPersonDetails)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($existingCustomer)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerInterfaceProphecy->getConnection()->willReturn($connection);
        $entityManagerInterfaceProphecy->flush()->shouldBeCalled();
        $entityManagerInterface = $entityManagerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateAccountNumber($existingCustomer))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateReferralCode($existingCustomer))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $expectedOutput = [[
            'FRCContractApplicationNumber' => 'ex1',
            'CRMContractApplicationNumber' => 'ap1',
            'ProcessStatus' => 1,
            'Message' => 'New Application Create Successful.',
        ]];

        $contractApplicationRequestCreator = new ContractApplicationRequestCreator('', 'Asia/Singapore', $commandBus, $entityManagerInterface, $serializerInterface, $dataMapper);
        $actualOutput = $contractApplicationRequestCreator->processArrayData([['applicationRequest' => $applicationRequestData]]);

        $this->assertEquals($actualOutput, $expectedOutput);
    }

    public function testProcessArrayDataCorporateNewCustomer()
    {
        $timeZone = new \DateTimeZone('Asia/Singapore');
        $startDate = '2022-05-05';
        $endDate = '2019-05-05';
        $nric = [];
        $nric['name'] = 'nric';
        $nric['value'] = 'nric1';
        $applicationRequestData = [];
        $applicationRequestData['tariffRate']['tariffRateNumber'] = '123';
        $applicationRequestData['acquirerCode'] = 'ac1';
        $applicationRequestData['customer']['corporationDetails']['identifiers'] = [$nric];
        $applicationRequestData['customer']['corporationDetails']['name'] = 'shaboo';
        $applicationRequestData['preferredStartDate'] = $startDate;
        $applicationRequestData['preferredEndDate'] = $endDate;
        $applicationRequestData['contactPerson']['personDetails']['contactPoints']['0']['emails'][0] = 'shaboo@shaboo.com';
        $applicationRequestData['contactPerson']['personDetails']['name'] = 'Mo Shaboo';

        $applicationRequestDataToDeserialize = [];
        $applicationRequestDataToDeserialize['acquirerCode'] = 'ac1';
        $applicationRequestDataToDeserialize['preferredStartDate'] = $startDate;
        $applicationRequestDataToDeserialize['preferredEndDate'] = $endDate;

        $customerDataToDeserialize = [];
        $customerDataToDeserialize['corporationDetails']['identifiers'] = [$nric];
        $customerDataToDeserialize['corporationDetails']['name'] = 'shaboo';

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->setIsBasedOn($tariffRateProphecy)->shouldBeCalled();
        $tariffRate = $tariffRateProphecy->reveal();

        $tariffRateRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => '123', 'isBasedOn' => null])->willReturn($tariffRate);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $acquiredFromProphecy = $this->prophesize(CustomerAccount::class);
        $acquiredFrom = $acquiredFromProphecy->reveal();

        $existingCustomerCorporationDetailsProphecy = $this->prophesize(Corporation::class);
        $existingCustomerCorporationDetails = $existingCustomerCorporationDetailsProphecy->reveal();

        $existingCustomerProphecy = $this->prophesize(CustomerAccount::class);
        $existingCustomerProphecy->setStatus(new CustomerAccountStatus(CustomerAccountStatus::INACTIVE))->shouldBeCalled();
        $existingCustomerProphecy->addCategory(AccountCategory::CUSTOMER)->shouldBeCalled();
        $existingCustomerProphecy->getCorporationDetails()->willReturn($existingCustomerCorporationDetails);
        $existingCustomerProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $existingCustomer = $existingCustomerProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->findOneBy(['accountNumber' => 'ac1'])->willReturn($acquiredFrom);

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('details.name', ':name')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identifiers.name', ':identityName')->shouldBeCalled();
        $queryBuilderExpressionProphecy->eq('identifiers.value', ':value')->shouldBeCalled();
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.corporationDetails', 'details')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('details.identifiers', 'identifiers')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderExpression->eq('details.name', ':name'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identifiers.name', ':identityName'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('identifiers.value', ':value'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', 'shaboo')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('identityName', $nric['name'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('value', $nric['value'])->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);

        // Contact Person Section.
        $contactPersonPersonDetailsProphecy = $this->prophesize(Person::class);
        $contactPersonPersonDetails = $contactPersonPersonDetailsProphecy->reveal();

        $contactPersonProphecy = $this->prophesize(CustomerAccount::class);
        $contactPersonProphecy->getPersonDetails()->willReturn($contactPersonPersonDetails);
        $contactPerson = $contactPersonProphecy->reveal();

        $expressionComparisonProphecy = $this->prophesize(Expr\Comparison::class);
        $expressionComparison = $expressionComparisonProphecy->reveal();

        $expressionAndXProphecy = $this->prophesize(Expr\Andx::class);
        $expressionAndX = $expressionAndXProphecy->reveal();

        $contactPersonQueryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $contactPersonQueryBuilderExpressionProphecy->literal(true)->shouldBeCalled()->willReturn(new Expr\Literal());
        $contactPersonQueryBuilderExpressionProphecy->eq('person.name', ':contactPersonName')->shouldBeCalled()->willReturn($expressionComparison);
        $contactPersonQueryBuilderExpressionProphecy->eq(\sprintf(<<<'SQL'
                        jsonb_contains(CAST(lower(CAST(%s.%s AS text)) AS jsonb), :%s)
SQL
            , 'contactPoint', 'emails', 'email'), new Expr\Literal())->shouldBeCalled()->willReturn($expressionComparison);
        $contactPersonQueryBuilderExpressionProphecy->andX($expressionComparison, $expressionComparison)->shouldBeCalled()->willReturn($expressionAndX);
        $contactPersonQueryBuilderExpression = $contactPersonQueryBuilderExpressionProphecy->reveal();

        $contactPersonQueryProphecy = $this->prophesize(AbstractQuery::class);
        $contactPersonQueryProphecy->getResult()->willReturn([$contactPerson]);
        $contactPersonQuery = $contactPersonQueryProphecy->reveal();

        $contactPersonQueryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $contactPersonQueryBuilderProphecy->expr()->willReturn($contactPersonQueryBuilderExpression);
        $contactPersonQueryBuilderProphecy->leftJoin('customerAccount.personDetails', 'person')->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->leftJoin('person.contactPoints', 'contactPoint')->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->where($expressionAndX)->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->setParameter('email', \json_encode(\strtolower('shaboo@shaboo.com')))->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->setParameter('contactPersonName', 'Mo Shaboo')->shouldBeCalled()->willReturn($contactPersonQueryBuilderProphecy);
        $contactPersonQueryBuilderProphecy->getQuery()->willReturn($contactPersonQuery);
        $contactPersonQueryBuilder = $contactPersonQueryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy->createQueryBuilder('customerAccount')->willReturn($contactPersonQueryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();
        //Contact Person Section.

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getAddressCountry()->willReturn(null);
        $postalAddressProphecy->getAddressLocality()->willReturn(null);
        $postalAddressProphecy->setAddressCountry('SG')->shouldBeCalled();
        $postalAddressProphecy->setAddressLocality('SINGAPORE')->shouldBeCalled();
        $postalAddress = $postalAddressProphecy->reveal();

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapContractSubtype('LANDED')->willReturn('Landed');
        $dataMapper = $dataMapperProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setCustomer($existingCustomer)->shouldBeCalled();
        $applicationRequestProphecy->setCustomerType($existingCustomer->getType())->shouldBeCalled();
        $applicationRequestProphecy->setPersonDetails($contactPersonPersonDetails)->shouldBeCalled();
        $applicationRequestProphecy->setCorporationDetails($existingCustomerCorporationDetails)->shouldBeCalled();
        $applicationRequestProphecy->setContactPerson($contactPerson)->shouldBeCalled();
        $applicationRequestProphecy->setPreferredStartDate(new \DateTime($startDate, $timeZone))->shouldBeCalled();
        $applicationRequestProphecy->setPreferredEndDate(new \DateTime($endDate, $timeZone))->shouldBeCalled();
        $applicationRequestProphecy->setAcquiredFrom($acquiredFrom)->shouldBeCalled();
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddress]);
        $applicationRequestProphecy->getContractSubtype()->willReturn('LANDED');
        $applicationRequestProphecy->setContractSubtype('Landed')->shouldBeCalled();
        $applicationRequestProphecy->setTariffRate($tariffRate)->shouldBeCalled();
        $applicationRequestProphecy->getExternalApplicationRequestNumber()->willReturn('ex1');
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('ap1');
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode($applicationRequestDataToDeserialize), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
            'digital_document_write',
            'person_write',
            'identification_write',
            'contact_point_write',
        ]])->willReturn($applicationRequest);
        $serializerInterfaceProphecy->deserialize(\json_encode($customerDataToDeserialize), CustomerAccount::class, 'json', ['groups' => [
            'customer_account_write',
            'person_write',
            'corporation_write',
            'identification_write',
            'contact_point_write',
        ]])->willReturn($existingCustomer);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerInterfaceProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerInterfaceProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManagerInterfaceProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);
        $entityManagerInterfaceProphecy->persist($tariffRate)->shouldBeCalled();
//        $entityManagerInterfaceProphecy->persist($contactPersonPersonDetails)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($existingCustomerCorporationDetails)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($existingCustomer)->shouldBeCalled();
        $entityManagerInterfaceProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerInterfaceProphecy->getConnection()->willReturn($connection);
        $entityManagerInterfaceProphecy->flush()->shouldBeCalled();
        $entityManagerInterface = $entityManagerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateAccountNumber($existingCustomer))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateReferralCode($existingCustomer))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $expectedOutput = [[
            'FRCContractApplicationNumber' => 'ex1',
            'CRMContractApplicationNumber' => 'ap1',
            'ProcessStatus' => 1,
            'Message' => 'New Application Create Successful.',
        ]];

        $contractApplicationRequestCreator = new ContractApplicationRequestCreator('', 'Asia/Singapore', $commandBus, $entityManagerInterface, $serializerInterface, $dataMapper);
        $actualOutput = $contractApplicationRequestCreator->processArrayData([['applicationRequest' => $applicationRequestData]]);

        $this->assertEquals($actualOutput, $expectedOutput);
    }

    public function testProcessArrayDataNoData()
    {
        $data = [[]];

        $expectedOutput = [[
            'FRCContractApplicationNumber' => '',
            'CRMContractApplicationNumber' => '',
            'ProcessStatus' => 0,
            'Message' => 'No data.',
        ]];

        $entityManagerInterfaceProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerInterfaceProphecy->clear()->shouldBeCalled();
        $entityManagerInterface = $entityManagerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapper = $dataMapperProphecy->reveal();

        $contractApplicationRequestCreator = new ContractApplicationRequestCreator('', 'Asia/Singapore', $commandBus, $entityManagerInterface, $serializerInterface, $dataMapper);
        $actualOutput = $contractApplicationRequestCreator->processArrayData($data);

        $this->assertEquals($actualOutput, $expectedOutput);
    }

    public function testProcessArrayCannotCreatApplicationRequest()
    {
        $applicationRequestData = [];
        $applicationRequestData['externalApplicationRequestNumber'] = '1';
        $data = [];
        $data[] = ['applicationRequest' => $applicationRequestData];

        $applicationRequestProphecy = $this->prophesize(Person::class);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode($applicationRequestData), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
            'digital_document_write',
            'person_write',
            'identification_write',
            'contact_point_write',
        ]])->willReturn($applicationRequest);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $entityManagerInterfaceProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerInterfaceProphecy->clear()->shouldBeCalled();
        $entityManagerInterface = $entityManagerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapper = $dataMapperProphecy->reveal();

        $contractApplicationRequestCreator = new ContractApplicationRequestCreator('', 'Asia/Singapore', $commandBus, $entityManagerInterface, $serializerInterface, $dataMapper);
        $actualOutput = $contractApplicationRequestCreator->processArrayData($data);

        $expectedOutput = [[
            'FRCContractApplicationNumber' => '1',
            'CRMContractApplicationNumber' => '',
            'ProcessStatus' => 0,
            'Message' => 'Cannot create application request.',
        ]];

        $this->assertEquals($actualOutput, $expectedOutput);
    }

    public function testProcessArrayCannotCreatCustomer()
    {
        $applicationRequestData = [];
        $applicationRequestData['externalApplicationRequestNumber'] = '1';
        $data = [];
        $data[] = ['applicationRequest' => $applicationRequestData];

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode($applicationRequestData), ApplicationRequest::class, 'json', ['groups' => [
            'application_request_write',
            'postal_address_write',
            'digital_document_write',
            'person_write',
            'identification_write',
            'contact_point_write',
        ]])->willReturn($applicationRequest);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $entityManagerInterfaceProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerInterfaceProphecy->clear()->shouldBeCalled();
        $entityManagerInterface = $entityManagerInterfaceProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapper = $dataMapperProphecy->reveal();

        $contractApplicationRequestCreator = new ContractApplicationRequestCreator('', 'Asia/Singapore', $commandBus, $entityManagerInterface, $serializerInterface, $dataMapper);
        $actualOutput = $contractApplicationRequestCreator->processArrayData($data);

        $expectedOutput = [[
            'FRCContractApplicationNumber' => '1',
            'CRMContractApplicationNumber' => '',
            'ProcessStatus' => 0,
            'Message' => 'No customer found or created.',
        ]];

        $this->assertEquals($actualOutput, $expectedOutput);
    }
}
