<?php

declare(strict_types=1);

namespace App\WebService\Affiliate;

class DummyClient implements ClientInterface
{
    public function getConversionDataByDate(\DateTime $startDate, \DateTime $endDate)
    {
        return [];
    }

    public function generateTrackingUrl(string $baseUrl, array $params)
    {
        return '';
    }

    public function getProviderName()
    {
        return 'dummy';
    }

    public function normalizeConversionData(array $data)
    {
        return [];
    }
}
