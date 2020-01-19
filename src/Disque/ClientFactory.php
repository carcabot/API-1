<?php

declare(strict_types=1);

namespace App\Disque;

use Disque\Connection\Credentials;
use Doctrine\ORM\EntityManagerInterface;

class ClientFactory
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
     * Creates a client for the specified host (or host:port).
     *
     * @param string $host
     * @param string $port
     *
     * @return Client
     */
    public function create(string $host, string $port = '7711'): Client
    {
        $port = (int) $port;

        $client = new Client([
            new Credentials($host, $port),
        ]);

        // use predis
        $client->getConnectionManager()->setConnectionFactory(new ConnectionFactory());
        $client->setEntityManager($this->entityManager);

        return $client;
    }
}
