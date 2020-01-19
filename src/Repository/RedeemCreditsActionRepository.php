<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class RedeemCreditsActionRepository extends EntityRepository
{
    use KeywordSearchRepositoryTrait;
}
