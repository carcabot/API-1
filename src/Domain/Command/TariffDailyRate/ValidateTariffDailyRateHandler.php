<?php

declare(strict_types=1);

namespace App\Domain\Command\TariffDailyRate;

use App\Model\TariffDailyRateValidator;

class ValidateTariffDailyRateHandler
{
    /**
     * @var TariffDailyRateValidator
     */
    private $tariffDailyRateValidator;

    /**
     * @param TariffDailyRateValidator $tariffDailyRateValidator
     */
    public function __construct(TariffDailyRateValidator $tariffDailyRateValidator)
    {
        $this->tariffDailyRateValidator = $tariffDailyRateValidator;
    }

    public function handle(ValidateTariffDailyRate $command): void
    {
        $tariffDailyRate = $command->getTariffDailyRate();

        $this->tariffDailyRateValidator->validate($tariffDailyRate);
    }
}
