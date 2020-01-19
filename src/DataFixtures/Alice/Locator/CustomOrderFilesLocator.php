<?php

declare(strict_types=1);

namespace App\DataFixtures\Alice\Locator;

use Hautelook\AliceBundle\FixtureLocatorInterface;
use Nelmio\Alice\IsAServiceTrait;

class CustomOrderFilesLocator implements FixtureLocatorInterface
{
    use IsAServiceTrait;

    private $decoratedFixtureLocator;

    /**
     * @param FixtureLocatorInterface $decoratedFixtureLocator
     */
    public function __construct(FixtureLocatorInterface $decoratedFixtureLocator)
    {
        $this->decoratedFixtureLocator = $decoratedFixtureLocator;
    }

    public function locateFiles(array $bundles, string $environment): array
    {
        $files = [];

        if ('group1' === $environment) {
            $files = [
                '/srv/api/fixtures/RunningNumber.yaml',
                '/srv/api/fixtures/TariffRate.yaml',
                '/srv/api/fixtures/User.yaml',
                '/srv/api/fixtures/CustomerAccount.yaml',
                '/srv/api/fixtures/MonetaryAmount.yaml',
                '/srv/api/fixtures/QuantitativeValue.yaml',
                '/srv/api/fixtures/Lead.yaml',
            ];
        }
        if ('group2' === $environment) {
            $files = [
                '/srv/api/fixtures/MonetaryAmount.yaml',
                '/srv/api/fixtures/QuantitativeValue.yaml',
                '/srv/api/fixtures/CommissionRate.yaml',
                '/srv/api/fixtures/ApplicationRequest.yaml',
                '/srv/api/fixtures/Contract.yaml',
            ];
        }

        if ('module' === $environment) {
            $files = [
                '/srv/api/fixtures/Module.yaml',
            ];
        }

        return $files;
    }
}
