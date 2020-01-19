<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\Lead;
use App\Model\LeadNumberGenerator;
use App\Model\RunningNumberGenerator;
use PHPUnit\Framework\TestCase;

class LeadNumberGeneratorTest extends TestCase
{
    public function testGenerateDefaultLeadNumber()
    {
        $length = 8;
        $prefix = 'L-';
        $type = 'lead';
        $number = 1;

        $leadProphecy = $this->prophesize(Lead::class);
        $lead = $leadProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $leadNumberGenerator = new LeadNumberGenerator($runningNumberGenerator);
        $leadNumber = $leadNumberGenerator->generate($lead);

        $this->assertEquals($leadNumber, 'L-00000001');
    }

    public function testGenerateLeadNumber()
    {
        $length = 8;
        $number = 1;
        $prefix = 'L-';
        $parameters = [
            'lead_number_length' => '6',
            'lead_number_series' => 'ym',
            'lead_number_prefix' => 'SWLD',
        ];
        $series = $parameters['lead_number_series'];
        $timezone = 'Asia/Singapore';
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($timezone));
        $type = 'lead';

        $leadProphecy = $this->prophesize(Lead::class);
        $lead = $leadProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, $series)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $leadNumberGenerator = new LeadNumberGenerator($runningNumberGenerator, $parameters, $timezone);
        $leadNumber = $leadNumberGenerator->generate($lead);

        $prefixDateSuffix = $now->format($parameters['lead_number_series']);
        $this->assertEquals($leadNumber, 'SWLD'.$prefixDateSuffix.'000001');
    }
}
