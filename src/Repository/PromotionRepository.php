<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApplicationRequest;
use App\Entity\Promotion;
use App\Enum\ApplicationRequestStatus;
use Doctrine\ORM\EntityRepository;

class PromotionRepository extends EntityRepository
{
    use KeywordSearchRepositoryTrait;

    /**
     * Finds the current inventory level for promotion repository.
     *
     * @param mixed $promotion
     *
     * @return string $data.
     */
    public function findCurrentInventoryLevel($promotion)
    {
        $qb = $this->getEntityManager()->getRepository(ApplicationRequest::class)->createQueryBuilder('applicationRequest');

        $applications = $qb->select($qb->expr()->count('applicationRequest'))
            ->leftJoin('applicationRequest.promotion', 'promotion')
            ->where($qb->expr()->orX(
                $qb->expr()->eq('promotion.id', ':promotion'),
                $qb->expr()->eq('promotion.isBasedOn', ':promotion')
            ))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('applicationRequest.status', ':statusCompleted'),
                $qb->expr()->eq('applicationRequest.status', ':statusInProgress')
            ))
            ->setParameter('promotion', $promotion->getId())
            ->setParameter('statusInProgress', new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS))
            ->setParameter('statusCompleted', new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED))
            ->getQuery()
            ->getSingleScalarResult();

        $currentInventoryLevel = (int) $promotion->getInventoryLevel()->getMaxValue() - $applications;

        $data = (string) \number_format($currentInventoryLevel, 4, '.', '');

        return $data;
    }
}
