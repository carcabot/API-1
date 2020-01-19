<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use App\Entity\Contract;
use App\Entity\ContractWelcomePackage;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Util\MimeType as MimeTypeUtil;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ContractWelcomePackageAttachmentDataProvider implements SubresourceDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * ContractWelcomePackageCollectionDataProvider constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param NormalizerInterface    $normalizer
     * @param WebServiceClient       $webServiceClient
     */
    public function __construct(EntityManagerInterface $entityManager, NormalizerInterface $normalizer, WebServiceClient $webServiceClient)
    {
        $this->entityManager = $entityManager;
        $this->normalizer = $normalizer;
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
        $result = null;

        if ($contract instanceof Contract && !empty($identifiers['fileKey'])) {
            $contractFormProfiles = [
                'Enworkz',
            ];

            if (\in_array($this->webServiceClient->getProviderName(), $contractFormProfiles, true)) {
                foreach ($contract->getFiles() as $file) {
                    if ((int) $identifiers['fileKey'] === $file->getId() && null !== $file->getContentPath()) {
                        $fileNormalized = $this->normalizer->normalize($file, 'jsonld');
                        $result = new ContractWelcomePackage($file->getId(), $file->getName(), MimeTypeUtil::detectByFilename($file->getContentPath()), null, $file->getDateCreated(), $fileNormalized['url']);
                    }
                }
            } else {
                $result = $this->webServiceClient->getContractWelcomePackageAttachment($contract, $identifiers['fileKey']);
            }
        }

        return $result;
    }
}
