<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CustomerAccount;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Zend\Diactoros\Response\JsonResponse;

class CustomerCustomSearchController
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
     * @Route("/search/customer_accounts", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function search(ServerRequestInterface $request): HttpResponseInterface
    {
        try {
            $alias = 'customerAccount';
            $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder($alias);
            $params = $request->getQueryParams() ?? [];
            $keyword = $params['keyword'];
            $search = [];
            $mappedJoins = [];

            foreach ($params['parameters'] as $field) {
                $joins = \explode('.', $field);

                for ($alias = 'customerAccount', $i = 0, $num = \count($joins); $i < $num; ++$i) {
                    $currentAlias = $joins[$i];

                    if ($i === $num - 1) {
                        $search[] = "{$alias}.{$currentAlias} LIKE :keyword";
                    } else {
                        $join = "{$alias}.{$currentAlias}";
                        if (!\in_array($join, $mappedJoins, true)) {
                            $qb->leftJoin($join, $currentAlias);
                            $mappedJoins[] = $join;
                        }
                    }

                    $alias = $currentAlias;
                }
            }

            $qb->andWhere(\implode(' OR ', $search));
            $qb->setParameter('keyword', $keyword.'%');

            $result = $qb->getQuery()->getResult();
            $customerAccounts = [];

            foreach ($result as $customerAccount) {
                $customerAccounts[] = \json_decode($this->serializer->serialize($customerAccount, 'jsonld', ['customer_account_read']));
            }

            return new JsonResponse($customerAccounts, 200);
        } catch (\Exception $ex) {
            throw new BadRequestHttpException($ex->getMessage());
        }
    }
}
