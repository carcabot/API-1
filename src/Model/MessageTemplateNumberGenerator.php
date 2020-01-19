<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\MessageTemplate;

class MessageTemplateNumberGenerator
{
    const LENGTH = 9;
    const PREFIX = 'M';
    const TYPE = 'message_template';

    /**
     * @var RunningNumberGenerator
     */
    private $runningNumberGenerator;

    /**
     * @param RunningNumberGenerator $runningNumberGenerator
     */
    public function __construct(RunningNumberGenerator $runningNumberGenerator)
    {
        $this->runningNumberGenerator = $runningNumberGenerator;
    }

    /**
     * Generates campaign number.
     *
     * @param MessageTemplate $message
     *
     * @return string
     */
    public function generate(MessageTemplate $message)
    {
        $nextNumber = $this->runningNumberGenerator->getNextNumber(self::TYPE, (string) self::LENGTH);
        $messageNumber = \sprintf('%s%s', self::PREFIX, \str_pad((string) $nextNumber, self::LENGTH, '0', STR_PAD_LEFT));

        return $messageNumber;
    }
}
