<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\RunningNumberGenerator;
use App\Model\StatementNumberGenerator;
use PHPUnit\Framework\TestCase;

class StatementNumberGeneratorTest extends TestCase
{
    public function testGenerateStatementNumber()
    {
        $length = 5;
        $prefix = 'COMST';
        $type = 'partner_commission_statement';

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $statementNumberGenerator = new StatementNumberGenerator($runningNumberGenerator);
        $statementNumber = $statementNumberGenerator->generate($prefix, $type, $length);

        $this->assertEquals($statementNumber, 'COMST00001');
    }
}
