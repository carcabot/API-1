<?php

declare(strict_types=1);

namespace App\Tests\WebService\Billing\Controller;

use App\Entity\QuantitativeValue;
use App\Entity\TariffRate;
use App\Enum\ContractType;
use App\Enum\ModuleType;
use App\Enum\TariffRateStatus;
use App\WebService\Billing\Controller\TariffRateController;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response\JsonResponse;

class TariffRateControllerTest extends TestCase
{
    public function testCreateActionWithoutRequiredFields()
    {
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn('{"available_from": "testDate", "contract_types": "testContractTypes","min_contract_term": "testMinContractTerm", "promotion_code": "testCode"}');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Error while creating promotion code!',
            'data' => [
                'promotion_name' => 'This value is required.',
                'promotion_start_date' => 'This value is required.',
            ],
        ], 400);

        $tariffRateController = new TariffRateController($entityManager, 'Asia/Singapore');
        $actualData = $tariffRateController->createAction($request);

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testCreateActionExistingTariffRate()
    {
        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRate = $tariffRateProphecy->reveal();

        $tariffRateRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => 'testCode', 'isBasedOn' => null])->willReturn($tariffRate);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn('{"available_from": "testDate", "contract_types": "testContractTypes","min_contract_term": "testMinContractTerm", "promotion_code": "testCode", "promotion_name": "testName", "promotion_start_date": "testStartDate"}');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Error while creating promotion code!',
            'data' => [
                'promotion_code' => 'Promotion code has already been used.',
            ],
        ], 400);

        $tariffRateController = new TariffRateController($entityManager, 'Asia/Singapore');
        $actualData = $tariffRateController->createAction($request);

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testCreateActionWithInvalidDateFormat()
    {
//        $tariffRateProphecy = new TariffRate();
//        $tariffRateProphecy->setStatus(new TariffRateStatus(TariffRateStatus::NEW));
//        $tariffRateProphecy->setChargeDescription('testChargeDescription');
//        $tariffRateProphecy->clearContractTypes();
//        $tariffRateProphecy->addContractType(new ContractType(ContractType::RESIDENTIAL));
//        $tariffRateProphecy->setCustomizable(true);
//        $tariffRateProphecy->setDescription('testDescirption');
//        $tariffRateProphecy->setInternalUseOnly(true);
//        $tariffRateProphecy->setMinContractTerm(new QuantitativeValue('testMinContractTerm', null, null, 'MON'));
//        $tariffRateProphecy->setName('testName');
//        $tariffRateProphecy->setTariffRateNumber('testCode');
//        $tariffRateProphecy->addUsedIn(ModuleType::PARTNERSHIP_PORTAL);
//        $tariffRateProphecy->addUsedIn(ModuleType::QUOTATION_CONTRACT);

        $tariffRateRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => 'testCode', 'isBasedOn' => null])->willReturn(null);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn(
            '{"available_from": "testDate", "min_contract_term": 123, "promotion_code": "testCode", "promotion_name": "testName", "promotion_start_date": "testStartDate", "charge_description": "testChargeDescription", "contract_types": ["RESIDENTIAL"], 
            "promotion_customized": "true", "promotion_desc": "testDescription", "promotion_internal_only": "false", "promotion_limit": 123, "promotion_end_date": "testEndDate", "promotion_remark": "testRemark", "application_for_use": ["campaigns"]}');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Error while creating promotion code!',
            'data' => [
                'available_from' => 'Invalid date format.',
                'promotion_start_date' => 'Invalid date format.',
                'promotion_end_date' => 'Invalid date format.',
            ],
        ], 400);

        $tariffRateController = new TariffRateController($entityManager, 'Asia/Singapore');
        $actualData = $tariffRateController->createAction($request);

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testUpdateActionWithoutExistingTariffRate()
    {
        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRate = $tariffRateProphecy->reveal();

        $tariffRateRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => 'testCode', 'isBasedOn' => null])->willReturn(null);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn('{"available_from": "testDate", "contract_types": "testContractTypes","min_contract_term": "testMinContractTerm", "promotion_code": "testCode", "promotion_name": "testName", "promotion_start_date": "testStartDate"}');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Promotion code testCode not found'], 404);

        $tariffRateController = new TariffRateController($entityManager, 'Asia/Singapore');
        $actualData = $tariffRateController->updateAction($request, 'testCode');

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testUpdateActionWithInvalidDateFormat()
    {
        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getId()->willReturn(123);
        $tariffRateProphecy->setChargeDescription('testChargeDescription')->shouldBeCalled();
        $tariffRateProphecy->clearContractTypes()->shouldBeCalled();
        $tariffRateProphecy->addContractType('RESIDENTIAL')->shouldBeCalled();
        $tariffRateProphecy->setCustomizable(true)->shouldBeCalled();
        $tariffRateProphecy->setDescription('testDescription')->shouldBeCalled();
        $tariffRateProphecy->setInternalUseOnly(false)->shouldBeCalled();
        $tariffRateProphecy->isInternalUseOnly()->willReturn(false);
        $tariffRateProphecy->setMinContractTerm(new QuantitativeValue('123', null, null, 'MON'))->shouldBeCalled();
        $tariffRateProphecy->setInventoryLevel(new QuantitativeValue(null, null, '123', null))->shouldBeCalled();
        $tariffRateProphecy->setName('testName')->shouldBeCalled();
        $tariffRateProphecy->setTariffRateNumber('testCode')->shouldBeCalled();
        $tariffRateProphecy->addUsedIn('CAMPAIGN')->shouldBeCalled();
        $tariffRateProphecy->setRemark('testRemark')->shouldBeCalled();
        $tariffRateProphecy->clearUsedIn()->shouldBeCalled();
        $tariffRate = $tariffRateProphecy->reveal();

        $tariffRateRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $tariffRateRepositoryProphecy->findOneBy(['tariffRateNumber' => 'testCode', 'isBasedOn' => null])->willReturn($tariffRate);
        $tariffRateRepository = $tariffRateRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(TariffRate::class)->willReturn($tariffRateRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn(
            '{"available_from": "testDate", "min_contract_term": 123, "promotion_code": "testCode", "promotion_name": "testName", "promotion_start_date": "testStartDate", "charge_description": "testChargeDescription", "contract_types": ["RESIDENTIAL"], 
            "promotion_customized": true, "promotion_desc": "testDescription", "promotion_internal_only": false, "promotion_limit": 123, "promotion_end_date": "testEndDate", "promotion_remark": "testRemark", "application_for_use": ["campaigns"]}');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Error while updating promotion code!',
            'data' => [
                'available_from' => 'Invalid date format.',
                'promotion_start_date' => 'Invalid date format.',
                'promotion_end_date' => 'Invalid date format.',
            ],
        ], 400);

        $tariffRateController = new TariffRateController($entityManager, 'Asia/Singapore');
        $actualData = $tariffRateController->updateAction($request, 'testCode');

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }
}
