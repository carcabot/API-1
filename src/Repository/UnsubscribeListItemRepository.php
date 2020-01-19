<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class UnsubscribeListItemRepository extends EntityRepository
{
    public function findAllEmails(): array
    {
        $dbData = $this->createQueryBuilder('u')->select('u.email')
            ->getQuery()->getResult();
        $emails = \array_map(function ($unsubscribeItem) { return $unsubscribeItem['email']; }, $dbData);

        return $emails;
    }

    public function findEmailById(string $id): ?string
    {
        try {
            $dbData = $this->createQueryBuilder('u')->select('u.email')
                ->where('u.id = ?1')
                ->setParameter(1, $id)
                ->getQuery()->getSingleResult();

            return !empty($dbData) ? $dbData['email'] : null;
        } catch (\Exception $exception) {
            return null;
        }
    }
}
