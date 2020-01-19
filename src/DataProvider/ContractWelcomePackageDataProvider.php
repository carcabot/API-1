<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use App\Entity\Contract;
use App\Entity\ContractWelcomePackage;
use App\Enum\DocumentType;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Util\MimeType as MimeTypeUtil;

class ContractWelcomePackageDataProvider implements SubresourceDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * ContractWelcomePackageCollectionDataProvider constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param WebServiceClient       $webServiceClient
     */
    public function __construct(EntityManagerInterface $entityManager, WebServiceClient $webServiceClient)
    {
        $this->entityManager = $entityManager;
        $this->webServiceClient = $webServiceClient;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Contract::class === $resourceClass;
    }

    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        $repository = $this->entityManager->getRepository($resourceClass);

        $contract = $repository->find($identifiers['id']);
        $result = [];

        if ($contract instanceof Contract && \in_array('contract_welcome_package_read', $context, true)) {
            $contractFormProfiles = [
                'Enworkz',
            ];

            if (\in_array($this->webServiceClient->getProviderName(), $contractFormProfiles, true)) {
                foreach ($contract->getFiles() as $file) {
                    if (DocumentType::CUSTOMER_CONTRACT_FORM === $file->getType()->getValue() && null !== $file->getContentPath()) {
                        $result[] = new ContractWelcomePackage($file->getId() ?? 0, $file->getName(), MimeTypeUtil::detectByFilename($file->getContentPath()), null, $file->getDateCreated());
                    }
                }
            } else {
                $result = $this->webServiceClient->getContractWelcomePackage($contract);
            }
        }

        return $result;
    }
}
