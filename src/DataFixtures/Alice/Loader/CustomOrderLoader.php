<?php

declare(strict_types=1);

namespace App\DataFixtures\Alice\Loader;

use App\Entity\ApplicationRequest;
use App\Entity\CommissionRate;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\Lead;
use App\Entity\Module;
use App\Entity\RunningNumber;
use App\Entity\TariffRate;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Fidry\AliceDataFixtures\LoaderInterface;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Nelmio\Alice\IsAServiceTrait;

class CustomOrderLoader implements LoaderInterface
{
    use IsAServiceTrait;

    private $decoratedLoader;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param LoaderInterface        $decoratedLoader
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(LoaderInterface $decoratedLoader, EntityManagerInterface $entityManager)
    {
        $this->decoratedLoader = $decoratedLoader;
        $this->entityManager = $entityManager;
    }

    public function load(array $fixturesFiles, array $parameters = [], array $objects = [], PurgeMode $purgeMode = null): array
    {
        $objects = $this->decoratedLoader->load($fixturesFiles, $parameters, $objects, $purgeMode);

        $em = $this->entityManager;

        $runningNo = $em->getRepository(RunningNumber::class)->createQueryBuilder('rn');

        $orderedObjects = [];

        foreach ($objects as $item) {
            if ($item instanceof Module) {
                $key = \array_search($item, $objects, true);
                $orderedObjects[$key] = $item;
            }
        }

        foreach ($objects as $item) {
            if ($item instanceof RunningNumber) {
                $runningNoResult = $runningNo->select('rn')
                    ->where($runningNo->expr()->eq('rn.series', ':series'))
                    ->andWhere($runningNo->expr()->eq('rn.type', ':type'))
                    ->setParameters([
                        'series' => $item->getSeries(),
                        'type' => $item->getType(),
                    ])
                    ->getQuery()
                    ->getOneOrNullResult(Query::HYDRATE_OBJECT);
                if (null === $runningNoResult) {
                    $key = \array_search($item, $objects, true);
                    $orderedObjects[$key] = $item;
                }
            }
        }

        foreach ($objects as $item) {
            if ($item instanceof TariffRate) {
                $key = \array_search($item, $objects, true);
                $orderedObjects[$key] = $item;
            }
        }

        foreach ($objects as $item) {
            if ($item instanceof CommissionRate) {
                $key = \array_search($item, $objects, true);
                $orderedObjects[$key] = $item;
            }
        }

        foreach ($objects as $item) {
            if ($item instanceof User) {
                $key = \array_search($item, $objects, true);
                $orderedObjects[$key] = $item;
            }
        }

        foreach ($objects as $item) {
            if ($item instanceof CustomerAccount) {
                $key = \array_search($item, $objects, true);
                $orderedObjects[$key] = $item;
            }
        }

        foreach ($objects as $item) {
            if ($item instanceof ApplicationRequest) {
                $key = \array_search($item, $objects, true);
                $orderedObjects[$key] = $item;
            }
        }

        foreach ($objects as $item) {
            if ($item instanceof Lead) {
                $key = \array_search($item, $objects, true);
                $orderedObjects[$key] = $item;
            }
        }

        foreach ($objects as $item) {
            if ($item instanceof Contract) {
                $key = \array_search($item, $objects, true);
                $orderedObjects[$key] = $item;
            }
        }

        return $orderedObjects;
    }
}
