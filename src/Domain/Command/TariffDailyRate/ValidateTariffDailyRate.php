<?php

declare(strict_types=1);

namespace App\Domain\Command\TariffDailyRate;

use App\Entity\TariffDailyRate;

class ValidateTariffDailyRate
{
    /**
     * @var TariffDailyRate
     */
    private $tariffDailyRate;

    /**
     * @param TariffDailyRate $tariffDailyRate
     */
    public function __construct(TariffDailyRate $tariffDailyRate)
    {
        $this->tariffDailyRate = $tariffDailyRate;
    }

    /**
     * @return TariffDailyRate
     */
    public function getTariffDailyRate(): TariffDailyRate
    {
        return $this->tariffDailyRate;
    }
}
