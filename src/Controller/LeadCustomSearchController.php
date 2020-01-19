<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Diactoros\Response;

class LeadCustomSearchController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/search/leads", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function search(ServerRequestInterface $request): HttpResponseInterface
    {
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $params = $request->getQueryParams() ?? [];
            $keyword = $params['keyword'];
            $search = [];
            $mappedJoins = [];

            $qb->select('l')->from('Lead', 'l');

            foreach ($params['parameters'] as $field) {
                $joins = \explode('.', $field);

                for ($lastAlias = 'ca', $i = 0, $num = \count($joins); $i < $num; ++$i) {
                    $currentAlias = $joins[$i];

                    if ($i === $num - 1) {
                        $search[] = "{$lastAlias}.{$currentAlias} LIKE :keyword";
                    } else {
                        $join = "{$lastAlias}.{$currentAlias}";
                        if (!\in_array($join, $mappedJoins, true)) {
                            $qb->leftJoin($join, $currentAlias);
                            $mappedJoins[] = $join;
                        }
                    }

                    $lastAlias = $currentAlias;
                }
            }

            $qb->andWhere(\implode(' OR ', $search));
            $qb->setParameter('keyword', $keyword.'%');

            $result = $qb->getQuery()->getResult();

            $response = new Response($result, 200);

            return $response;
        } catch (\Exception $ex) {
            throw new BadRequestHttpException($ex->getMessage());
        }
    }
}
