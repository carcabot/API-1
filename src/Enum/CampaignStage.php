<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of campaign stages.
 */
class CampaignStage extends Enum
{
    /**
     * @var string Represent that a campaign is in create source stage.
     */
    const CREATE_SOURCE = 'CREATE_SOURCE';

    /**
     * @var string Represent that a campaign is in create template stage.
     */
    const CREATE_TEMPLATE = 'CREATE_TEMPLATE';

    /**
     * @var string Represent that a campaign is in report summary stage.
     */
    const REPORT_SUMMARY = 'REPORT_SUMMARY';

    /**
     * @var string Represent that a campaign is in schedule stage.
     */
    const SCHEDULE = 'SCHEDULE';
}
