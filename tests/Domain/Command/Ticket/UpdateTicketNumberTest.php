<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\Ticket;

use App\Domain\Command\Ticket\UpdateTicketNumber;
use App\Domain\Command\Ticket\UpdateTicketNumberHandler;
use App\Entity\Ticket;
use App\Model\TicketNumberGenerator;
use PHPUnit\Framework\TestCase;

class UpdateTicketNumberTest extends TestCase
{
    public function testUpdateTicketNumber()
    {
        $length = 8;
        $prefix = 'T-';
        $type = 'ticket';
        $number = 1;

        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->setTicketNumber('T-00000001')->shouldBeCalled();
        $ticket = $ticketProphecy->reveal();

        $ticketNumberGeneratorProphecy = $this->prophesize(TicketNumberGenerator::class);
        $ticketNumberGeneratorProphecy->generate($ticket)->willReturn(\sprintf('%s%s', $prefix, \str_pad((string) $number, $length, '0', STR_PAD_LEFT)));
        $ticketNumberGenerator = $ticketNumberGeneratorProphecy->reveal();

        $updateTicketNumberHandler = new UpdateTicketNumberHandler($ticketNumberGenerator);
        $updateTicketNumberHandler->handle(new UpdateTicketNumber($ticket));
    }
}
