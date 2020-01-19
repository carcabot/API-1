<?php

declare(strict_types=1);

namespace App\WebService\SMS;

interface ClientInterface
{
    public function send(string $recipient, string $message, ?string $sender = null);
}
