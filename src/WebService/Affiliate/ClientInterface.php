<?php

declare(strict_types=1);

namespace App\WebService\Affiliate;

interface ClientInterface
{
    public function getConversionDataByDate(\DateTime $startDate, \DateTime $endDate);

    public function generateTrackingUrl(string $baseUrl, array $params);

    public function getProviderName();

    public function normalizeConversionData(array $data);
}
