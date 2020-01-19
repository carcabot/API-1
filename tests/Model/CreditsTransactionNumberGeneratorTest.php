<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\CreditsTransaction;
use App\Model\CreditsTransactionNumberGenerator;
use App\Model\RunningNumberGenerator;
use PHPUnit\Framework\TestCase;

class CreditsTransactionNumberGeneratorTest extends TestCase
{
    public function testGenerateCreditsTransactionNumber()
    {
        $length = 8;
        $prefix = 'TX';
        $number = 1;
        $type = 'credits_transaction';

        $creditsTransactionProphecy = $this->prophesize(CreditsTransaction::class);
        $creditsTransaction = $creditsTransactionProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $creditsTransactionNumberGenerator = new CreditsTransactionNumberGenerator($runningNumberGenerator);
        $creditsTransactionNumber = $creditsTransactionNumberGenerator->generate($creditsTransaction);

        $this->assertEquals($creditsTransactionNumber, 'TX00000001');
    }
}
