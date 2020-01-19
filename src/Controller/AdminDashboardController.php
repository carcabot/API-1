<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Zend\Diactoros\Response\JsonResponse;

class AdminDashboardController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     */
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/tickets_by_assignees", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function search(ServerRequestInterface $request): HttpResponseInterface
    {
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('user');
        $userCaseStatistics = $qb->select('userDetails.name, ticket.status, COUNT(ticket.id) as totalCases')
            ->join('user.customerAccount', 'userAccount')
            ->join('userAccount.personDetails', 'userDetails')
            ->join(Ticket::class, 'ticket', Join::WITH, 'ticket.assignee = user.id')
            ->groupBy('userDetails.name, ticket.status')
            ->getQuery()
            ->getResult();

        return new JsonResponse($userCaseStatistics);
    }
}
