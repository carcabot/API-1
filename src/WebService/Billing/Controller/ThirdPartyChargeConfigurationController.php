<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ThirdPartyChargeConfigurationDataProvider;
use App\Entity\ThirdPartyChargeConfiguration;

class ThirdPartyChargeConfigurationController
{
    private $thirdPartyChargeConfigurationDataProvider;

    /**
     * @param ThirdPartyChargeConfigurationDataProvider $thirdPartyChargeConfigurationDataProvider
     */
    public function __construct(ThirdPartyChargeConfigurationDataProvider $thirdPartyChargeConfigurationDataProvider)
    {
        $this->thirdPartyChargeConfigurationDataProvider = $thirdPartyChargeConfigurationDataProvider;
    }

    public function __invoke(): array
    {
        $data = $this->thirdPartyChargeConfigurationDataProvider->getSubresource(ThirdPartyChargeConfiguration::class, [], ['third_party_charge_config_read'], 'get');

        return $data;
    }
}
