<?php

declare(strict_types=1);

namespace App\Domain\Command\PartnerCommissionStatement;

use App\Model\StatementNumberGenerator;

class UpdateStatementNumberHandler
{
    /**
     * @var StatementNumberGenerator
     */
    private $statementNumberGenerator;

    /**
     * @param StatementNumberGenerator $statementNumberGenerator
     */
    public function __construct(StatementNumberGenerator $statementNumberGenerator)
    {
        $this->statementNumberGenerator = $statementNumberGenerator;
    }

    public function handle(UpdateStatementNumber $command): void
    {
        $statement = $command->getPartnerCommissionStatement();
        $statementNumber = $this->statementNumberGenerator->generate('COMST', 'partner_commission_statement', 5);

        $statement->setStatementNumber($statementNumber);
    }
}
