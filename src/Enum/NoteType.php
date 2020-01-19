<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of note types.
 */
class NoteType extends Enum
{
    /**
     * @var string Indicates the alert note type.
     */
    const ALERT = 'ALERT';

    /**
     * @var string Indicates the blacklist reason note type.
     */
    const BLACKLIST_REASON = 'BLACKLIST_REASON';

    /**
     * @var string Indicates the blacklist remark note type.
     */
    const BLACKLIST_REMARK = 'BLACKLIST_REMARK';

    /**
     * @var string Indicates the description note type.
     */
    const DESCRIPTION_NOTE = 'DESCRIPTION_NOTE';

    /**
     * @var string Indicates the follow-up note type.
     */
    const FOLLOW_UP = 'FOLLOW_UP';

    /**
     * @var string Indicates the general note type.
     */
    const GENERAL = 'GENERAL';

    /**
     * @var string Indicates the internal note type.
     */
    const INTERNAL_NOTE = 'INTERNAL_NOTE';

    /**
     * @var string Indicates the lost reason note type.
     */
    const LOST_REASON = 'LOST_REASON';

    /**
     * @var string Indicates the note type is not in this list.
     */
    const OTHERS = 'OTHERS';

    /**
     * @var string Indicates the reject reason note type.
     */
    const REJECT_REASON = 'REJECT_REASON';

    /**
     * @var string Indicates the resolution note type.
     */
    const RESOLUTION_NOTE = 'RESOLUTION_NOTE';

    /**
     * @var string Indicates the task note type.
     */
    const TASK = 'TASK';
}
