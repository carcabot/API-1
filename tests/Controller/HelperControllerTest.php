<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\HelperController;
use App\Domain\Command\Ticket\GetMatchingServiceLevelAgreement;
use App\Entity\TicketCategory;
use App\Entity\TicketType;
use App\Enum\Priority;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Zend\Diactoros\Response\JsonResponse;

class HelperControllerTest extends TestCase
{
    public function testTicketsResolveSLAAction()
    {
        $data = [
            'category' => 'testCategory',
            'subcategory' => 'testSubCategory',
            'priority' => 'HIGH',
            'type' => 'testType',
        ];

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getQueryParams()->willReturn($data);
        $request = $requestProphecy->reveal();

        $categoryProphecy = $this->prophesize(TicketCategory::class);
        $category = $categoryProphecy->reveal();

        $categoryRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $categoryRepositoryProphecy->find('testCategory')->willReturn($category);
        $categoryRepositoryProphecy->find('testSubCategory')->willReturn($category);
        $categoryRepository = $categoryRepositoryProphecy->reveal();

        $ticketTypeProphecy = $this->prophesize(TicketType::class);
        $ticketType = $ticketTypeProphecy->reveal();

        $ticketTypeRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $ticketTypeRepositoryProphecy->find('testType')->willReturn($ticketType);
        $ticketTypeRepository = $ticketTypeRepositoryProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new GetMatchingServiceLevelAgreement($category, $category, new Priority('HIGH'), $ticketType))->shouldBeCalled()->willReturn('testSla');
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(TicketCategory::class)->willReturn($categoryRepository);
        $entityManagerProphecy->getRepository(TicketType::class)->willReturn($ticketTypeRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize('testSla', 'jsonld', [
            'groups' => [
                'service_level_agreement_read',
            ],
        ])->willReturn('{"test": "testSla"}');
        $serializer = $serializerProphecy->reveal();

        $expectedData = new JsonResponse(\json_decode('{"test": "testSla"}'), 200);

        $helperController = new HelperController($commandBus, $entityManager, $serializer);
        $actualData = $helperController->ticketsResolveSLAAction($request);

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
    }

    public function testTicketsResolveSLAActionWithoutCategory()
    {
        $data = [
            'category' => 'testCategory',
            'subcategory' => 'testSubCategory',
            'priority' => 'HIGH',
            'type' => 'testType',
        ];

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getQueryParams()->willReturn($data);
        $request = $requestProphecy->reveal();

        $categoryProphecy = $this->prophesize(TicketCategory::class);
        $category = $categoryProphecy->reveal();

        $categoryRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $categoryRepositoryProphecy->find('testCategory')->willReturn(null);
        $categoryRepositoryProphecy->find('testSubCategory')->willReturn($category);
        $categoryRepository = $categoryRepositoryProphecy->reveal();

        $ticketTypeProphecy = $this->prophesize(TicketType::class);
        $ticketType = $ticketTypeProphecy->reveal();

        $ticketTypeRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $ticketTypeRepositoryProphecy->find('testType')->willReturn($ticketType);
        $ticketTypeRepository = $ticketTypeRepositoryProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(TicketCategory::class)->willReturn($categoryRepository);
        $entityManagerProphecy->getRepository(TicketType::class)->willReturn($ticketTypeRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializer = $serializerProphecy->reveal();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Category not found.');

        $helperController = new HelperController($commandBus, $entityManager, $serializer);
        $helperController->ticketsResolveSLAAction($request);
    }

    public function testTicketsResolveSLAActionWithoutSubCategory()
    {
        $data = [
            'category' => 'testCategory',
            'subcategory' => 'testSubCategory',
            'priority' => 'HIGH',
            'type' => 'testType',
        ];

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getQueryParams()->willReturn($data);
        $request = $requestProphecy->reveal();

        $categoryProphecy = $this->prophesize(TicketCategory::class);
        $category = $categoryProphecy->reveal();

        $categoryRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $categoryRepositoryProphecy->find('testCategory')->willReturn($category);
        $categoryRepositoryProphecy->find('testSubCategory')->willReturn(null);
        $categoryRepository = $categoryRepositoryProphecy->reveal();

        $ticketTypeProphecy = $this->prophesize(TicketType::class);
        $ticketType = $ticketTypeProphecy->reveal();

        $ticketTypeRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $ticketTypeRepositoryProphecy->find('testType')->willReturn($ticketType);
        $ticketTypeRepository = $ticketTypeRepositoryProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(TicketCategory::class)->willReturn($categoryRepository);
        $entityManagerProphecy->getRepository(TicketType::class)->willReturn($ticketTypeRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializer = $serializerProphecy->reveal();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Subcategory not found.');

        $helperController = new HelperController($commandBus, $entityManager, $serializer);
        $helperController->ticketsResolveSLAAction($request);
    }

    public function testTicketsResolveSLAActionWithoutTicketType()
    {
        $data = [
            'category' => 'testCategory',
            'subcategory' => 'testSubCategory',
            'priority' => 'HIGH',
            'type' => 'testType',
        ];

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getQueryParams()->willReturn($data);
        $request = $requestProphecy->reveal();

        $categoryProphecy = $this->prophesize(TicketCategory::class);
        $category = $categoryProphecy->reveal();

        $categoryRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $categoryRepositoryProphecy->find('testCategory')->willReturn($category);
        $categoryRepositoryProphecy->find('testSubCategory')->willReturn($category);
        $categoryRepository = $categoryRepositoryProphecy->reveal();

        $ticketTypeRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $ticketTypeRepositoryProphecy->find('testType')->willReturn(null);
        $ticketTypeRepository = $ticketTypeRepositoryProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(TicketCategory::class)->willReturn($categoryRepository);
        $entityManagerProphecy->getRepository(TicketType::class)->willReturn($ticketTypeRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializer = $serializerProphecy->reveal();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Ticket type not found.');

        $helperController = new HelperController($commandBus, $entityManager, $serializer);
        $helperController->ticketsResolveSLAAction($request);
    }
}
