<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Document\OldUsers;
use App\Document\ProductCatalog;
use App\Entity\BridgeUser;
use App\Entity\OfferCatalog;
use App\Enum\CatalogStatus;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OfferCatalogApi
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(DocumentManager $documentManager, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function createOfferCatalogs(array $offerCatalogs)
    {
        foreach ($offerCatalogs as $offerCatalogData) {
            $this->createOfferCatalog($offerCatalogData);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function createOfferCatalog(ProductCatalog $offerCatalogData): OfferCatalog
    {
        $existingOfferCatalog = $this->entityManager->getRepository(OfferCatalog::class)->findOneBy(['name' => $offerCatalogData->getName()]);

        $offerCatalog = new OfferCatalog();

        if (null !== $existingOfferCatalog) {
            $offerCatalog = $existingOfferCatalog;
        }

        if (null !== $offerCatalogData->getCreatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $offerCatalogData->getCreatedAt()]);

            if (null !== $oldUser) {
                $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $creator) {
                    $offerCatalog->setCreator($creator->getUser());
                }
            }
        }

        if (null !== $offerCatalogData->getUpdatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $offerCatalogData->getUpdatedBy()]);

            if (null !== $oldUser) {
                $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $agent) {
                    $offerCatalog->setAgent($agent->getUser());
                }
            }
        }

        if (null !== $offerCatalogData->getCreatedAt()) {
            $offerCatalog->setDateCreated($offerCatalogData->getCreatedAt());
        }

        if (null !== $offerCatalogData->getUpdatedAt()) {
            $offerCatalog->setDateModified($offerCatalogData->getUpdatedAt());
        }

        if (null !== $offerCatalogData->getName()) {
            $offerCatalog->setName($offerCatalogData->getName());
        }

        if (null !== $offerCatalogData->getStatus()) {
            $catalogStatus = new CatalogStatus(CatalogStatus::ACTIVE);

            if ('New' === $offerCatalogData->getStatus()) {
                $catalogStatus = new CatalogStatus(CatalogStatus::DRAFT);
            }

            $offerCatalog->setStatus($catalogStatus);
        }

        if (null !== $offerCatalogData->getValidFrom()) {
            $offerCatalog->setValidFrom($offerCatalogData->getValidFrom());
        }

        if (null !== $offerCatalogData->getValidThrough()) {
            $offerCatalog->setValidThrough($offerCatalogData->getValidThrough());
        }

        $this->entityManager->persist($offerCatalog);

        return $offerCatalog;
    }
}
