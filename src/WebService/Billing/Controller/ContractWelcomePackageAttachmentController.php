<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractWelcomePackageAttachmentDataProvider;
use App\Entity\Contract;
use App\Entity\ContractWelcomePackage;

class ContractWelcomePackageAttachmentController
{
    private $contractWelcomePackageAttachmentDataProvider;

    /**
     * @param ContractWelcomePackageAttachmentDataProvider $contractWelcomePackageAttachmentDataProvider
     */
    public function __construct(ContractWelcomePackageAttachmentDataProvider $contractWelcomePackageAttachmentDataProvider)
    {
        $this->contractWelcomePackageAttachmentDataProvider = $contractWelcomePackageAttachmentDataProvider;
    }

    public function __invoke(string $id, int $fileKey): ?ContractWelcomePackage
    {
        $data = $this->contractWelcomePackageAttachmentDataProvider->getSubresource(Contract::class, ['id' => $id, 'fileKey' => $fileKey], ['contract_welcome_package_attachment_read'], 'GET');

        return $data;
    }
}
