<?php

declare(strict_types=1);

namespace App\Domain\Command\CreditsTransaction;

use App\Model\CreditsTransactionNumberGenerator;

class UpdateCreditsTransactionNumberHandler
{
    /**
     * @var CreditsTransactionNumberGenerator
     */
    private $creditsTransactionNumberGenerator;

    /**
     * @param CreditsTransactionNumberGenerator $creditsTransactionNumberGenerator
     */
    public function __construct(CreditsTransactionNumberGenerator $creditsTransactionNumberGenerator)
    {
        $this->creditsTransactionNumberGenerator = $creditsTransactionNumberGenerator;
    }

    public function handle(UpdateCreditsTransactionNumber $command): void
    {
        $creditsTransaction = $command->getCreditsTransaction();
        $creditsTransactionNumber = $this->creditsTransactionNumberGenerator->generate($creditsTransaction);

        $creditsTransaction->setCreditsTransactionNumber($creditsTransactionNumber);
    }
}
