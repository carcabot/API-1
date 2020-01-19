<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class TicketRepository extends EntityRepository
{
    use KeywordSearchRepositoryTrait;
}
