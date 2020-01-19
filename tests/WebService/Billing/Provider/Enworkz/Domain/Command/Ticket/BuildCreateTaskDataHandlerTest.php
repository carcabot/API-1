<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 17/4/19
 * Time: 10:31 AM.
 */

namespace App\Tests\WebService\Billing\Provider\Enworkz\Domain\Command\Ticket;

use App\Entity\Contract;
use App\Entity\DigitalDocument;
use App\Entity\Note;
use App\Entity\Ticket;
use App\Entity\TicketCategory;
use App\Enum\NoteType;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\Ticket\BuildCreateTaskData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\Ticket\BuildCreateTaskDataHandler;
use App\WebService\Billing\Services\DataMapper;
use PHPUnit\Framework\TestCase;

class BuildCreateTaskDataHandlerTest extends TestCase
{
    public function testCreateTaskDataWithNoteTypeAsFollowUp()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SWCC123456');
        $contractProphecy = $contractProphecy->reveal();

        $noteProphecy = $this->prophesize(Note::class);
        $noteProphecy->getType()->willReturn(new NoteType(NoteType::FOLLOW_UP));
        $noteProphecy->getText()->willReturn('testNoteText');
        $noteProphecy = $noteProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $ticketCategoryProphecy = $this->prophesize(TicketCategory::class);
        $ticketCategoryProphecy->getName()->willReturn('testCategoryName');
        $ticketCategoryProphecy = $ticketCategoryProphecy->reveal();

        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->getCategory()->willReturn($ticketCategoryProphecy);
        $ticketProphecy->getContract()->willReturn($contractProphecy);
        $ticketProphecy->getPlannedCompletionDate()->willReturn($now);
        $ticketProphecy->getNotes()->willReturn([$noteProphecy]);
        $ticketProphecy->getStartDate()->willReturn($now);
        $ticketProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $ticketProphecy->getSubcategory()->willReturn($ticketCategoryProphecy);
        $ticketProphecy->getTicketNumber()->willReturn('SWCS123456');
        $ticketProphecy->getDescription()->willReturn('Test description');

        $ticket = $ticketProphecy->reveal();

        $expectedCreateTaskData = [
            'CRMTaskNumber' => 'SWCS123456',
            'FRCContractNumber' => 'SWCC123456',
            'ReminderDate' => $now->format('Ymd'),
            'DueDate' => $now,
            'Message' => 'Test description testCategoryName testCategoryName',
            'FollowUpRole' => 'testNoteText',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildCreateTaskData = new BuildCreateTaskData($ticket);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildCreateTaskDataHandler = new BuildCreateTaskDataHandler($dataMapperProphecy);
        $actualCreateTaskData = $buildCreateTaskDataHandler->handle($buildCreateTaskData);

        $this->assertEquals($expectedCreateTaskData, $actualCreateTaskData);
    }

    public function testCreateTaskDataWithNoteTypeAsNotFollowup()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SWCC123456');
        $contractProphecy = $contractProphecy->reveal();

        $noteProphecy = $this->prophesize(Note::class);
        $noteProphecy->getType()->willReturn(new NoteType(NoteType::OTHERS));
        $noteProphecy->getText()->willReturn('testNoteText');
        $noteProphecy = $noteProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $ticketCategoryProphecy = $this->prophesize(TicketCategory::class);
        $ticketCategoryProphecy->getName()->willReturn('testCategoryName');
        $ticketCategoryProphecy = $ticketCategoryProphecy->reveal();

        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->getCategory()->willReturn($ticketCategoryProphecy);
        $ticketProphecy->getContract()->willReturn($contractProphecy);
        $ticketProphecy->getPlannedCompletionDate()->willReturn($now);
        $ticketProphecy->getNotes()->willReturn([$noteProphecy]);
        $ticketProphecy->getStartDate()->willReturn($now);
        $ticketProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $ticketProphecy->getSubcategory()->willReturn($ticketCategoryProphecy);
        $ticketProphecy->getTicketNumber()->willReturn('SWCS123456');
        $ticketProphecy->getDescription()->willReturn('Test description');

        $ticket = $ticketProphecy->reveal();

        $expectedCreateTaskData = [
            'CRMTaskNumber' => 'SWCS123456',
            'FRCContractNumber' => 'SWCC123456',
            'ReminderDate' => $now->format('Ymd'),
            'DueDate' => $now,
            'Message' => 'Test description testCategoryName testCategoryName',
            'FollowUpRole' => null,
            'Attachments' => [
                [
                    'Attachment' => [
                        'FileName' => 'testAttachmentName',
                        'ContentType' => '',
                        'FileBytes' => '',
                    ],
                ],
            ],
        ];

        $buildCreateTaskData = new BuildCreateTaskData($ticket);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildCreateTaskDataHandler = new BuildCreateTaskDataHandler($dataMapperProphecy);
        $actualCreateTaskData = $buildCreateTaskDataHandler->handle($buildCreateTaskData);

        $this->assertEquals($expectedCreateTaskData, $actualCreateTaskData);
    }
}
