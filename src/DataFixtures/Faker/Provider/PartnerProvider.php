<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use App\Entity\QuantitativeValue;
use Faker\Provider\Base as BaseProvider;

class PartnerProvider extends BaseProvider
{
    public function generateAnnouncementAudience()
    {
        $audience = self::randomElement(['CreativeWork ', 'Event', 'LodgingBusiness', 'PlayAction', 'Product', 'Service']);

        return $audience;
    }

    public function generatePayoutCycle()
    {
        $payout = new QuantitativeValue();

        return $payout;
    }
}
