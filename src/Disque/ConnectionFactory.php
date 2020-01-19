<?php

declare(strict_types=1);

namespace App\Disque;

use Disque\Connection\ConnectionInterface;
use Disque\Connection\Factory\ConnectionFactoryInterface;

/**
 * Create the default Disque connection.
 */
class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * Create a new Connection object.
     *
     * @param string $host
     * @param int    $port
     *
     * @return ConnectionInterface
     */
    public function create($host, $port)
    {
        return new PredisClient($host, $port);
    }
}
