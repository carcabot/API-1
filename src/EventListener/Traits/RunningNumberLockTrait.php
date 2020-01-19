<?php

declare(strict_types=1);

namespace App\EventListener\Traits;

use Doctrine\ORM\EntityManagerInterface;

trait RunningNumberLockTrait
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var bool
     */
    protected $locked;

    /**
     * Sets entityManager.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Gets entityManager.
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Sets locked.
     *
     * @param bool $locked
     */
    public function setLocked(bool $locked)
    {
        $this->locked = $locked;
    }

    /**
     * Gets locked.
     *
     * @return bool
     */
    public function getLocked(): bool
    {
        return $this->locked;
    }

    public function startLockTransaction()
    {
        $this->locked = true;
        $this->entityManager->getConnection()->beginTransaction();
        $this->entityManager->getConnection()->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;');
    }

    public function endLockTransaction()
    {
        if (true === $this->locked) {
            $this->entityManager->getConnection()->commit();
            $this->locked = false;
        }
    }
}
