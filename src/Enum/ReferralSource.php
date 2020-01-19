<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of referral sources.
 */
class ReferralSource extends Enum
{
    /**
     * @var string Indicates that the referral source is from another website.
     */
    const ANOTHER_WEBSITE = 'ANOTHER_WEBSITE';

    /**
     * @var string Indicates that the referral source is from email newsletter.
     */
    const EMAIL_NEWSLETTER = 'EMAIL_NEWSLETTER';

    /**
     * @var string Indicates that the referral source is from event roadshow.
     */
    const EVENT_ROADSHOW = 'EVENT_ROADSHOW';

    /**
     * @var string Indicates that the referral source is from MRT or bus.
     */
    const MRT_BUS = 'MRT_BUS';

    /**
     * @var string Indicates that the referral source is from newspaper or magazine.
     */
    const NEWSPAPER_MAGAZINE = 'NEWSPAPER_MAGAZINE';

    /**
     * @var string Indicates that the referral source is from online advertisement.
     */
    const ONLINE_ADVERTISEMENT = 'ONLINE_ADVERTISEMENT';

    /**
     * @var string Indicates that the referral source is not included in the list.
     */
    const OTHERS = 'OTHERS';

    /**
     * @var string Indicates that the referral source is from postcard or letter.
     */
    const POSTCARD_LETTER = 'POSTCARD_LETTER';

    /**
     * @var string Indicates that the referral source is from radio or television.
     */
    const RADIO_TV = 'RADIO_TV';

    /**
     * @var string Indicates that the referral source is from a referrer.
     */
    const REFERRAL = 'REFERRAL';

    /**
     * @var string Indicates that the referral source is from social media.
     */
    const SOCIAL_MEDIA = 'SOCIAL_MEDIA';
}
