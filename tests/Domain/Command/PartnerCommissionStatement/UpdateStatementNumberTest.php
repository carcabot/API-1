<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\PartnerCommissionStatement;

use App\Domain\Command\PartnerCommissionStatement\UpdateStatementNumber;
use App\Domain\Command\PartnerCommissionStatement\UpdateStatementNumberHandler;
use App\Entity\PartnerCommissionStatement;
use App\Model\StatementNumberGenerator;
use PHPUnit\Framework\TestCase;

class UpdateStatementNumberTest extends TestCase
{
    public function testUpdateApplicationRequestNumber()
    {
        $statementProphecy = $this->prophesize(PartnerCommissionStatement::class);
        $statementProphecy->setStatementNumber('COMST00001')->shouldBeCalled();
        $statement = $statementProphecy->reveal();

        $statementNumberGeneratorProphecy = $this->prophesize(StatementNumberGenerator::class);
        $statementNumberGeneratorProphecy->generate('COMST', 'partner_commission_statement', 5)->willReturn('COMST00001');
        $statementNumberGenerator = $statementNumberGeneratorProphecy->reveal();

        $updateStatementNumberHandler = new UpdateStatementNumberHandler($statementNumberGenerator);
        $updateStatementNumberHandler->handle(new UpdateStatementNumber($statement));
    }
}
