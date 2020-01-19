<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Model\RunningNumberGenerator;

class UpdateSalesRepresentativeAccountNumberHandler
{
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

    public function handle(UpdateSalesRepresentativeAccountNumber $command): void
    {
        $employer = $command->getEmployer();
        $salesRepresentative = $command->getSalesRepresentative();
        $length = 5;

        if (null !== $employer->getAccountNumber()) {
            $nextNumber = $this->runningNumberGenerator->getNextNumber($employer->getAccountNumber(), $employer->getAccountNumber());
            $salesRepresentativeAccountNumber = \sprintf('%s%s', $employer->getAccountNumber(), \str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT));

            $salesRepresentative->setAccountNumber($salesRepresentativeAccountNumber);
        }
    }
}
