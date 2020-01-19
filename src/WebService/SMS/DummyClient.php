<?php

declare(strict_types=1);

namespace App\WebService\SMS;

use App\Enum\SMSDirection;
use App\Enum\SMSWebServicePartner;

class DummyClient implements ClientInterface
{
    public function send(string $recipient, string $message, ?string $sender = null)
    {
        if (empty($sender)) {
            $sender = '+6598765432';
        }

        if (0 !== \strpos('+', $recipient)) {
            $recipient = '+'.$recipient;
        }

        return [
            'dateSent' => (new \DateTime())->format('c'),
            'direction' => SMSDirection::OUTBOUND,
            'message' => $message,
            'provider' => SMSWebServicePartner::DUMMY,
            'rawMessage' => $message,
            'recipientMobileNumber' => $recipient,
            'sender' => $sender,
            'senderMobileNumber' => $sender,
        ];
    }
}
