<?php

declare(strict_types=1);

namespace App\Tests\WebService\Billing\Controller;

use App\Entity\Promotion;
use App\Entity\PromotionCategory;
use App\Entity\QuantitativeValue;
use App\WebService\Billing\Controller\PromotionController;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response\JsonResponse;

class PromotionControllerTest extends TestCase
{
    public function testCreateActionWithoutRequiredFields()
    {
        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn('{"discount_code": "testCode", "discount_name": "testName","discount_type": "testType", "discount_start_date": "testStartDate", "discount_end_date": "testEndDate"}');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Error while creating discount!',
            'data' => [
                'discount_value' => 'This value is required.',
            ],
        ], 400);

        $promotionController = new PromotionController($entityManager, 'Asia/Singapore');
        $actualData = $promotionController->createAction($request);

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testCreateActionWithExistingPromotion()
    {
        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotion = $promotionProphecy->reveal();

        $promotionRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $promotionRepositoryProphecy->findOneBy(['promotionNumber' => 'testCode', 'isBasedOn' => null])->willReturn($promotion);
        $promotionRepository = $promotionRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Promotion::class)->willReturn($promotionRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn('{"discount_code": "testCode", "discount_name": "testName","discount_type": "testType", "discount_start_date": "testStartDate", "discount_end_date": "testEndDate", "discount_value": 123}');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Error while creating discount!',
            'data' => [
                'discount_code' => 'Discount code has already been used.',
            ],
        ], 400);

        $promotionController = new PromotionController($entityManager, 'Asia/Singapore');
        $actualData = $promotionController->createAction($request);

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testCreateActionWithNewCategoryWithPercentageAsDiscountType()
    {
        $promotionRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $promotionRepositoryProphecy->findOneBy(['promotionNumber' => 'testCode', 'isBasedOn' => null])->willReturn(null);
        $promotionRepository = $promotionRepositoryProphecy->reveal();

        $promotionCategoryRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $promotionCategoryRepositoryProphecy->findOneBy(['name' => 'Percentage'])->willReturn(null);
        $promotionCategoryRepository = $promotionCategoryRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Promotion::class)->willReturn($promotionRepository);
        $entityManagerProphecy->getRepository(PromotionCategory::class)->willReturn($promotionCategoryRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn('{"discount_code": "testCode", "discount_name": "testName","discount_type": "Percentage", "discount_start_date": "testStartDate", "discount_end_date": "testEndDate",  "discount_value": 30, "max_discount_value": 20, "number_of_discount":1 }');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Error while creating discount code!',
            'data' => [
                'discount_start_date' => 'Invalid date format.',
                'discount_end_date' => 'Invalid date format.',
            ],
        ], 400);

        $promotionController = new PromotionController($entityManager, 'Asia/Singapore');
        $actualData = $promotionController->createAction($request);

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testCreateActionWithNewCategoryWithPercentageAsDiscountTypeandWithoutMaxDiscountValue()
    {
        $promotionRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $promotionRepositoryProphecy->findOneBy(['promotionNumber' => 'testCode', 'isBasedOn' => null])->willReturn(null);
        $promotionRepository = $promotionRepositoryProphecy->reveal();

        $promotionCategoryRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $promotionCategoryRepositoryProphecy->findOneBy(['name' => 'Percentage'])->willReturn(null);
        $promotionCategoryRepository = $promotionCategoryRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Promotion::class)->willReturn($promotionRepository);
        $entityManagerProphecy->getRepository(PromotionCategory::class)->willReturn($promotionCategoryRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn('{"discount_code": "testCode", "discount_name": "testName","discount_type": "Percentage", "discount_start_date": "testStartDate", "discount_end_date": "testEndDate", "discount_value": 30, "number_of_discount":1}');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Error while creating discount code!',
            'data' => [
                'discount_start_date' => 'Invalid date format.',
                'discount_end_date' => 'Invalid date format.',
            ],
        ], 400);

        $promotionController = new PromotionController($entityManager, 'Asia/Singapore');
        $actualData = $promotionController->createAction($request);

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testCreateActionWithNewCategoryWithFixedAmountAsDiscountType()
    {
        $promotionRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $promotionRepositoryProphecy->findOneBy(['promotionNumber' => 'testCode', 'isBasedOn' => null])->willReturn(null);
        $promotionRepository = $promotionRepositoryProphecy->reveal();

        $promotionCategoryRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $promotionCategoryRepositoryProphecy->findOneBy(['name' => 'Fixed Amount'])->willReturn(null);
        $promotionCategoryRepository = $promotionCategoryRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Promotion::class)->willReturn($promotionRepository);
        $entityManagerProphecy->getRepository(PromotionCategory::class)->willReturn($promotionCategoryRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn('{"discount_code": "testCode", "discount_name": "testName","discount_type": "Fixed Amount", "discount_start_date": "testStartDate", "discount_end_date": "testEndDate", "min_discount_month": 1, "discount_value": 30}');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Error while creating discount code!',
            'data' => [
                'discount_start_date' => 'Invalid date format.',
                'discount_end_date' => 'Invalid date format.',
            ],
        ], 400);

        $promotionController = new PromotionController($entityManager, 'Asia/Singapore');
        $actualData = $promotionController->createAction($request);

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testUpdateActionWithoutExistingPromotion()
    {
        $promotionRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $promotionRepositoryProphecy->findOneBy(['promotionNumber' => 'testCode', 'isBasedOn' => null])->willReturn(null);
        $promotionRepository = $promotionRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Promotion::class)->willReturn($promotionRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn('{"discount_code": "testCode", "discount_name": "testName","discount_type": "testType", "discount_start_date": "testStartDate", "discount_end_date": "testEndDate", "min_discount_month": "1"}');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Discount code testCode not found.'], 404);

        $promotionController = new PromotionController($entityManager, 'Asia/Singapore');
        $actualData = $promotionController->updateAction($request, 'testCode');

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testUpdateActionWithInvalidDateFormat()
    {
        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->setName('testName')->shouldBeCalled();
        $promotionProphecy->setCurrency('SGD')->shouldBeCalled();
        $promotionProphecy->setAmount(new QuantitativeValue('30', null, null, null))->shouldBeCalled();
        $promotionProphecy->setRecurringDuration(new QuantitativeValue('1', null, null, 'MON'))->shouldBeCalled();
        $promotion = $promotionProphecy->reveal();

        $promotionRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $promotionRepositoryProphecy->findOneBy(['promotionNumber' => 'testCode', 'isBasedOn' => null])->willReturn($promotion);
        $promotionRepository = $promotionRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Promotion::class)->willReturn($promotionRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $requestBodyProphecy = $this->prophesize(StreamInterface::class);
        $requestBodyProphecy->getContents()->willReturn('{"discount_code": "testCode", "discount_name": "testName","discount_type": "Fixed Amount", "discount_start_date": "testStartDate", "discount_end_date": "testEndDate", "number_of_discount":1, "discount_value": 30}');
        $requestBody = $requestBodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($requestBody);
        $request = $requestProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Error while creating discount code!',
            'data' => [
                'discount_start_date' => 'Invalid date format.',
                'discount_end_date' => 'Invalid date format.',
            ],
        ], 400);

        $promotionController = new PromotionController($entityManager, 'Asia/Singapore');
        $actualData = $promotionController->updateAction($request, 'testCode');

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }
}
