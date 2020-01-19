<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Entity\Note;
use App\Enum\NoteType;

class UpdateBlacklistNotesHandler
{
    public function handle(UpdateBlacklistNotes $command): void
    {
        $blacklist = $command->getBlacklist();
        $customer = $command->getCustomer();

        if (null !== $blacklist->getReason()) {
            $blacklistReason = new Note();
            $blacklistReason->setType(new NoteType(NoteType::BLACKLIST_REASON));
            $blacklistReason->setText($blacklist->getReason());

            $customer->addNote($blacklistReason);
        }

        $blacklistRemark = new Note();
        $blacklistRemark->setType(new NoteType(NoteType::BLACKLIST_REMARK));
        $blacklistRemark->setText($blacklist->getRemarks());

        $customer->addNote($blacklistRemark);
    }
}
