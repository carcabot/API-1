<?php

declare(strict_types=1);

namespace App\DataFixtures\Processor;

use App\Entity\CommissionRate;
use App\Entity\Partner;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Fidry\AliceDataFixtures\ProcessorInterface;

class CommissionRateProcessor extends CommissionRate implements ProcessorInterface
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
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function preProcess(string $id, $object): void
    {
        if (!$object instanceof CommissionRate) {
            return;
        }

        $em = $this->entityManager;
        $partner = $em->getRepository(Partner::class)->createQueryBuilder('cm');
        $partnerDESCId = $partner
            ->select('DISTINCT cm.id')
            ->orderBy('cm.id', 'DESC')
            ->getQuery()
            ->getResult();
        $partnerStart = $partnerDESCId[0]['id'];
        $partnerEnd = $partnerDESCId[\count($partnerDESCId) - 1]['id'];
        $randomPartnerId = \rand($partnerStart, $partnerEnd);
        $partnerResult = $partner->select('cm')
            ->where($partner->expr()->eq('cm.id', ':id'))
            ->setParameters([
                'id' => $randomPartnerId,
            ])
            ->getQuery()
            ->getOneOrNullResult(Query::HYDRATE_OBJECT);
        $object->partners = [$partnerResult];
    }

    public function postProcess(string $id, $object): void
    {
        // TODO: Implement postProcess() method.
    }
}
