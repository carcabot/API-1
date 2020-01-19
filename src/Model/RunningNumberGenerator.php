<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\RunningNumber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

class RunningNumberGenerator
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
     * Gets the next number in the specified type and series.
     *
     * @param string $type
     * @param string $series
     */
    public function getNextNumber(string $type, string $series)
    {
        $em = $this->entityManager;

        $runningNo = $em->getRepository(RunningNumber::class)->createQueryBuilder('rn');

        $runningNo = $runningNo->select('rn')
            ->where($runningNo->expr()->eq('rn.type', ':type'))
            ->andWhere($runningNo->expr()->eq('rn.series', ':series'))
            ->setParameters([
                'type' => $type,
                'series' => $series,
            ])
            ->getQuery()
            ->getOneOrNullResult(Query::HYDRATE_OBJECT);

        if (null !== $runningNo) {
            $seq = $runningNo->getNumber() + 1;
            $runningNo->setNumber($seq);
        } else {
            $runningNo = new RunningNumber();
            $runningNo->setType($type);
            $runningNo->setSeries($series);
            $runningNo->setNumber(1);
        }
        $em->persist($runningNo);

        return $runningNo->getNumber();
    }
}
