<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Document\OldUsers;
use App\Document\ProductCategory;
use App\Entity\BridgeUser;
use App\Entity\OfferCategory;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OfferCategoryApi
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

    public function createOfferCategories(array $offerCategories)
    {
        foreach ($offerCategories as $offerCategoryData) {
            $this->createOfferCategory($offerCategoryData);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function createOfferCategory(ProductCategory $offerCategoryData): OfferCategory
    {
        $existingOfferCategory = $this->entityManager->getRepository(OfferCategory::class)->findOneBy(['categoryNumber' => $offerCategoryData->getCode()]);

        $offerCategory = new OfferCategory();

        if (null !== $existingOfferCategory) {
            $offerCategory = $existingOfferCategory;
        }

        if (null !== $offerCategoryData->getName()) {
            $offerCategory->setName($offerCategoryData->getName());
        }

        if (null !== $offerCategoryData->getCode()) {
            $offerCategory->setCategoryNumber($offerCategoryData->getCode());
        }

        if (null !== $offerCategoryData->getCreatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $offerCategoryData->getCreatedAt()]);

            if (null !== $oldUser) {
                $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $creator) {
                    $offerCategory->setCreator($creator->getUser());
                }
            }
        }

        if (null !== $offerCategoryData->getUpdatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $offerCategoryData->getUpdatedBy()]);

            if (null !== $oldUser) {
                $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $agent) {
                    $offerCategory->setAgent($agent->getUser());
                }
            }
        }

        if (null !== $offerCategoryData->getCreatedAt()) {
            $offerCategory->setDateCreated($offerCategoryData->getCreatedAt());
        }

        if (null !== $offerCategoryData->getUpdatedAt()) {
            $offerCategory->setDateModified($offerCategoryData->getUpdatedAt());
        }

        $this->entityManager->persist($offerCategory);

        return $offerCategory;
    }

    public function createOfferCategoryByNameOnly(string $name): OfferCategory
    {
        $offerCategory = new OfferCategory();

        $offerCategory->setName($name);
        $offerCategory->setDescription($name);
        $offerCategory->setCategoryNumber($name);

        $this->entityManager->persist($offerCategory);

        return $offerCategory;
    }
}
