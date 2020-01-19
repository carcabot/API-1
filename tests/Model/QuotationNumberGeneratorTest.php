<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\Quotation;
use App\Model\QuotationNumberGenerator;
use App\Model\RunningNumberGenerator;
use PHPUnit\Framework\TestCase;

class QuotationNumberGeneratorTest extends TestCase
{
    public function testGenerateQuotationNumber()
    {
        $length = 9;
        $prefix = 'Q';
        $type = 'quotation';
        $number = 1;

        $quotationProphecy = $this->prophesize(Quotation::class);
        $quotation = $quotationProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $quotationNumberGenerator = new QuotationNumberGenerator($runningNumberGenerator);
        $quotationNumber = $quotationNumberGenerator->generate($quotation);

        $this->assertEquals($quotationNumber, 'Q000000001');
    }
}
