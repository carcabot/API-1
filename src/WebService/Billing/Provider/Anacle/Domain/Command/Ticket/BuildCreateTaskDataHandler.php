<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\Ticket;

use App\Enum\NoteType;
use App\WebService\Billing\Services\DataMapper;

class BuildCreateTaskDataHandler
{
    /**
     * @var DataMapper
     */
    private $dataMapper;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param DataMapper $dataMapper
     */
    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
        $this->timezone = new \DateTimeZone('Asia/Singapore');
    }

    public function handle(BuildCreateTaskData $command): array
    {
        $ticket = $command->getTicket();

        $ticketNumber = $ticket->getTicketNumber();
        $startDate = $ticket->getStartDate();
        $contractNumber = null;
        $plannedCompletionDate = $ticket->getPlannedCompletionDate();
        $followUpRoleDetails = null;
        $message = $ticket->getDescription().' '.$ticket->getCategory()->getName().' '.$ticket->getSubcategory()->getName();

        foreach ($ticket->getNotes() as $ticketNote) {
            if (NoteType::FOLLOW_UP === $ticketNote->getType()->getValue()) {
                $followUpRoleDetails = $ticketNote->getText();
            }
        }

        if (null !== $ticket->getContract()) {
            $contractNumber = $ticket->getContract()->getContractNumber();
        }

        $startDate->setTimezone($this->timezone);
        $startDate = $startDate->format('Ymd');

        $createTaskData = [
            'CRMTaskNumber' => $ticketNumber,
            'FRCContractNumber' => $contractNumber,
            'ReminderDate' => $startDate,
            'DueDate' => $plannedCompletionDate,
            'Message' => $message,
            'FollowUpRole' => $followUpRoleDetails,
        ];

        // start attachments
        $createTaskData['Attachments'] = [];

        $attachments = [];
        foreach ($ticket->getSupplementaryFiles() as $fileAttached) {
            $attachment = $this->dataMapper->mapAttachment($fileAttached);

            if (\count($attachment) > 0) {
                $attachments[] = $attachment;
            }
        }

        if (\count($attachments) > 0) {
            $createTaskData['Attachments'] += $attachments;
        }
        // end attachments

        return $createTaskData;
    }
}
