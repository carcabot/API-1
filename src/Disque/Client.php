<?php

declare(strict_types=1);

namespace App\Disque;

use Disque\Client as DisqueClient;
use Doctrine\ORM\EntityManagerInterface;

class Client extends DisqueClient
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var array
     */
    private $queues;

    /**
     * Get a queue.
     *
     * @param string $name Queue name
     *
     * @return Queue Queue
     */
    public function queue($name)
    {
        if (!isset($this->queues[$name])) {
            $this->queues[$name] = new Queue($this, $name, $this->entityManager);
        }

        return $this->queues[$name];
    }

    /**
     * Set entityManager.
     *
     * @param EntityManagerInterface $entityManager`
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
}
