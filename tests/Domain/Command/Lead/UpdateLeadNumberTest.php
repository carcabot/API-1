<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\Lead;

use App\Domain\Command\Lead\UpdateLeadNumber;
use App\Domain\Command\Lead\UpdateLeadNumberHandler;
use App\Entity\Lead;
use App\Model\LeadNumberGenerator;
use PHPUnit\Framework\TestCase;

class UpdateLeadNumberTest extends TestCase
{
    public function testUpdateLeadNumber()
    {
        $length = 8;
        $prefix = 'L-';
        $type = 'lead';
        $number = 1;

        $leadProphecy = $this->prophesize(Lead::class);
        $leadProphecy->setLeadNumber('L-00000001')->shouldBeCalled();
        $lead = $leadProphecy->reveal();

        $leadNumberGeneratorProphecy = $this->prophesize(LeadNumberGenerator::class);
        $leadNumberGeneratorProphecy->generate($lead)->willReturn(\sprintf('%s%s', $prefix, \str_pad((string) $number, $length, '0', STR_PAD_LEFT)));
        $leadNumberGenerator = $leadNumberGeneratorProphecy->reveal();

        $updateLeadNumberHandler = new UpdateLeadNumberHandler($leadNumberGenerator);
        $updateLeadNumberHandler->handle(new UpdateLeadNumber($lead));
    }
}
