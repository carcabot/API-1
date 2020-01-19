<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\Ticket;
use App\Model\RunningNumberGenerator;
use App\Model\TicketNumberGenerator;
use PHPUnit\Framework\TestCase;

class TicketNumberGeneratorTest extends TestCase
{
    public function testGenerateDefaultTicketNumber()
    {
        $length = 9;
        $prefix = 'T';
        $type = 'ticket';
        $number = 1;

        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticket = $ticketProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $ticketNumberGenerator = new TicketNumberGenerator($runningNumberGenerator);
        $ticketNumber = $ticketNumberGenerator->generate($ticket);

        $this->assertEquals($ticketNumber, 'T000000001');
    }

    public function testGenerateTicketNumber()
    {
        $length = 8;
        $number = 1;
        $prefix = 'T-';
        $parameters = [
            'ticket_length' => '6',
            'ticket_series' => 'ym',
            'ticket_prefix' => 'SWCS',
            ];
        $series = $parameters['ticket_series'];
        $timezone = 'Asia/Singapore';
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($timezone));
        $type = 'ticket';

        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticket = $ticketProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, $series)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $ticketNumberGenerator = new TicketNumberGenerator($runningNumberGenerator, $parameters, $timezone);
        $ticketNumber = $ticketNumberGenerator->generate($ticket);

        $prefixDateSuffix = $now->format($parameters['ticket_series']);
        $this->assertEquals($ticketNumber, 'SWCS'.$prefixDateSuffix.'000001');
    }
}
