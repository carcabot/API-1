<?php

declare(strict_types=1);

namespace App\Disque;

use Disque\Connection\Predis;
use Predis\Client as PClient;

class PredisClient extends Predis
{
    /**
     * Build Predis client.
     *
     * @param string $host Host
     * @param int    $port Port
     *
     * @return PClient
     */
    protected function buildClient($host, $port)
    {
        return new PClient(['scheme' => 'tcp'] + \compact('host', 'port'), ['parameters' => ['read_write_timeout' => -1]]);
    }
}
