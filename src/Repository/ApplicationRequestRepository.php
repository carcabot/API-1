<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class ApplicationRequestRepository extends EntityRepository
{
    use KeywordSearchRepositoryTrait;
}
