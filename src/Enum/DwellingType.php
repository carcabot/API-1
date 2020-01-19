<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of residential dwelling types.
 *
 * @ref https://www.singstat.gov.sg/docs/default-source/default-document-library/methodologies_and_standards/standards_and_classifications/sctd.pdf
 */
class DwellingType extends Enum
{
    /**
     * @var string Indicates dwelling type of a 1 room flat.
     */
    const ONE_ROOM_FLAT_HDB = '1_ROOM_FLAT_HDB';

    /**
     * @var string Indicates dwelling type of a 2 room flat.
     */
    const TWO_ROOM_FLAT_HDB = '2_ROOM_FLAT_HDB';

    /**
     * @var string Indicates dwelling type of a 3 room flat.
     */
    const THREE_ROOM_FLAT_HDB = '3_ROOM_FLAT_HDB';

    /**
     * @var string Indicates dwelling type of a 4 room flat.
     */
    const FOUR_ROOM_FLAT_HDB = '4_ROOM_FLAT_HDB';

    /**
     * @var string Indicates dwelling type of a 5 room flat.
     */
    const FIVE_ROOM_FLAT_HDB = '5_ROOM_FLAT_HDB';

    /**
     * @var string Indicates dwelling type of a condominium property.
     */
    const CONDOMINIUM = 'CONDOMINIUM';

    /**
     * @var string Indicates dwelling type of an executive flat.
     */
    const EXECUTIVE_FLAT_HDB = 'EXECUTIVE_FLAT_HDB';

    /**
     * @var string Indicates dwelling type of a landed property.
     */
    const LANDED = 'LANDED';
}
