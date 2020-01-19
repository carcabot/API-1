<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Order;
use App\Enum\OrderStatus;

class OrderNumberGenerator
{
    const LENGTH = 9;
    const PREFIX = 'O';
    const DRAFT_PREFIX = 'ODFT';
    const DRAFT_LENGTH = 5;
    const TYPE = 'order';

    /**
     * @var RunningNumberGenerator
     */
    private $runningNumberGenerator;

    /**
     * @var array
     */
    private $runningNumberParameters;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param RunningNumberGenerator $runningNumberGenerator
     * @param array                  $runningNumberParameters
     * @param string                 $timezone
     */
    public function __construct(RunningNumberGenerator $runningNumberGenerator, array $runningNumberParameters, string $timezone)
    {
        $this->runningNumberGenerator = $runningNumberGenerator;
        $this->runningNumberParameters = $runningNumberParameters;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * Generates an order number.
     *
     * @param Order $order
     *
     * @return string
     */
    public function generate(Order $order)
    {
        $length = self::LENGTH;
        $prefix = self::PREFIX;
        $prefixDateSuffix = '';
        $series = $length;
        $type = self::TYPE;

        if (OrderStatus::DRAFT === $order->getOrderStatus()->getValue()) {
            $length = self::DRAFT_LENGTH;
            $series = $length;
            $prefix = self::DRAFT_PREFIX;
        } else {
            if (!empty($this->runningNumberParameters['order_prefix'])) {
                $prefix = $this->runningNumberParameters['order_prefix'];
            }

            if (!empty($this->runningNumberParameters['order_length'])) {
                $length = (int) $this->runningNumberParameters['order_length'];
            }

            if (!empty($this->runningNumberParameters['order_series'])) {
                $series = $this->runningNumberParameters['order_series'];
                $now = new \DateTime();
                $now->setTimezone($this->timezone);
                $prefixDateSuffix = $now->format($series);
            }
        }

        if ($series === $length) {
            $numberPrefix = $prefix;
        } else {
            $numberPrefix = $prefix.$prefixDateSuffix;
        }

        $nextNumber = $this->runningNumberGenerator->getNextNumber($type, (string) $series);
        $orderNumber = \sprintf('%s%s', $numberPrefix, \str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT));

        return $orderNumber;
    }
}
