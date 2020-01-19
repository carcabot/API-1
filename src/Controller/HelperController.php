<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Command\Ticket\GetMatchingServiceLevelAgreement;
use App\Entity\TicketCategory;
use App\Entity\TicketType;
use App\Enum\Priority;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * @Route("/helper")
 */
class HelperController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/match_sla", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function ticketsResolveSLAAction(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = $request->getQueryParams() ?? [];
        $sla = null;

        if (!empty($params['category']) && !empty($params['subcategory']) && !empty($params['priority']) && !empty($params['type'])) {
            $category = $this->entityManager->getRepository(TicketCategory::class)->find($params['category']);
            $subcategory = $this->entityManager->getRepository(TicketCategory::class)->find($params['subcategory']);
            $priority = new Priority($params['priority']);
            $type = $this->entityManager->getRepository(TicketType::class)->find($params['type']);

            if (null === $category) {
                throw new BadRequestHttpException('Category not found.');
            }

            if (null === $subcategory) {
                throw new BadRequestHttpException('Subcategory not found.');
            }

            if (null === $type) {
                throw new BadRequestHttpException('Ticket type not found.');
            }

            $sla = $this->commandBus->handle(new GetMatchingServiceLevelAgreement($category, $subcategory, $priority, $type));
        }

        if (null !== $sla) {
            $sla = \json_decode($this->serializer->serialize($sla, 'jsonld', [
                'groups' => [
                    'service_level_agreement_read',
                ],
            ]));
        }

        return new JsonResponse($sla, 200);
    }
}
