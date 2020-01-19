<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractWelcomePackageDataProvider;
use App\Entity\Contract;

class ContractWelcomePackageController
{
    private $contractWelcomePackageDataProvider;

    /**
     * @param ContractWelcomePackageDataProvider $contractWelcomePackageDataProvider
     */
    public function __construct(ContractWelcomePackageDataProvider $contractWelcomePackageDataProvider)
    {
        $this->contractWelcomePackageDataProvider = $contractWelcomePackageDataProvider;
    }

    public function __invoke(int $id): array
    {
        $data = $this->contractWelcomePackageDataProvider->getSubresource(Contract::class, ['id' => $id], ['contract_welcome_package_read'], 'GET');

        return $data;
    }
}
