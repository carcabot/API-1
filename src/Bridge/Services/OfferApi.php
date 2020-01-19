<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Document\OldUsers;
use App\Document\Product;
use App\Document\ProductCatalog;
use App\Document\ProductCategory;
use App\Document\ProductPartner;
use App\Document\ProductType;
use App\Entity\BridgeUser;
use App\Entity\Merchant;
use App\Entity\MonetaryAmount;
use App\Entity\Offer;
use App\Entity\OfferCatalog;
use App\Entity\OfferCategory;
use App\Entity\OfferListItem;
use App\Entity\OfferSerialNumber;
use App\Entity\PriceSpecification;
use App\Entity\QuantitativeValue;
use App\Enum\OfferType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OfferApi
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
     * @var OfferCatalogApi
     */
    private $offerCatalogApi;

    /**
     * @var OfferCategoryApi
     */
    private $offerCategoryApi;

    /**
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param OfferCatalogApi        $offerCatalogApi
     * @param OfferCategoryApi       $offerCategoryApi
     */
    public function __construct(DocumentManager $documentManager, EntityManagerInterface $entityManager, LoggerInterface $logger, OfferCatalogApi $offerCatalogApi, OfferCategoryApi $offerCategoryApi)
    {
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->offerCatalogApi = $offerCatalogApi;
        $this->offerCategoryApi = $offerCategoryApi;
    }

    public function createOffers(array $offers)
    {
        foreach ($offers as $offerData) {
            $this->createOffer($offerData);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function createOffer(Product $offerData): Offer
    {
        $existingOffer = $this->entityManager->getRepository(Offer::class)->findOneBy(['offerNumber' => $offerData->getProductNumber()]);
        $offer = new Offer();

        if (null !== $existingOffer) {
            $offer = $existingOffer;
        }

        if (null !== $offerData->getProductNumber()) {
            if ('12345' === $offerData->getProductNumber()) {
                return $offer;
            }
            $offer->setOfferNumber($offerData->getProductNumber());
            $offer->setSku($offerData->getProductNumber());
        }

        if (null !== $offerData->getProductCategory()) {
            $productCategory = $this->documentManager->getRepository(ProductCategory::class)->find($offerData->getProductCategory());

            if (null !== $productCategory) {
                $offerCategory = $this->entityManager->getRepository(OfferCategory::class)->findOneBy(['categoryNumber' => $productCategory->getCode()]);

                if (null !== $offerCategory) {
                    $offer->setCategory($offerCategory);
                }
            } else {
                if (null !== $offerData->getProductNumber()) {
                    $newOfferCategory = $this->offerCategoryApi->createOfferCategoryByNameOnly($offerData->getProductNumber());
                    $offer->setCategory($newOfferCategory);
                }
            }
        }

        $offerType = null;
        if (null !== $offerData->getProductType()) {
            $productType = $this->documentManager->getRepository(ProductType::class)->find($offerData->getProductType());

            if (null !== $productType && null !== $productType->getProductType()) {
                if ('BR' === $productType->getProductType()) {
                    $offerType = new OfferType(OfferType::BILL_REBATE);
                }
            }
        }

        if (null === $offerType) {
            $offerType = new OfferType(OfferType::VOUCHER);
        }
        $offer->setType($offerType);

        if (null !== $offerData->getProductPartner()) {
            $productPartner = $this->documentManager->getRepository(ProductPartner::class)->find($offerData->getProductPartner());

            if (null !== $productPartner) {
                $merchant = $this->entityManager->getRepository(Merchant::class)->findOneBy(['merchantNumber' => $productPartner->getCode()]);

                if (null !== $merchant) {
                    $offer->setSeller($merchant);
                }
            }
        }

        if (null !== $offerData->getDescription()) {
            $offer->setDescription($offerData->getDescription());
            $offer->setName($offerData->getDescription());
        }

        if (null !== $offerData->getValidFrom()) {
            $offer->setValidFrom($offerData->getValidFrom());
        }

        if (null !== $offerData->getValidThrough()) {
            $offer->setValidThrough($offerData->getValidThrough());
        }

        if (null !== $offerData->getCreatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $offerData->getCreatedAt()]);

            if (null !== $oldUser) {
                $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $creator) {
                    $offer->setCreator($creator->getUser());
                }
            }
        }

        if (null !== $offerData->getUpdatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $offerData->getUpdatedBy()]);

            if (null !== $oldUser) {
                $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $agent) {
                    $offer->setAgent($agent->getUser());
                }
            }
        }

        if (null !== $offerData->getCreatedAt()) {
            $offer->setDateCreated($offerData->getCreatedAt());
        }

        if (null !== $offerData->getUpdatedAt()) {
            $offer->setDateModified($offerData->getUpdatedAt());
        }

        $this->entityManager->persist($offer);

        $this->createOfferListItem($offerData, $offer);

        return $offer;
    }

    public function createOfferListItem(Product $offerData, Offer $offer): OfferListItem
    {
        $offerListItem = new OfferListItem();
        $offerListItem->setItem($offer);

        if (null !== $offerData->getPoint()) {
            $offerListItem->setPriceSpecification(new PriceSpecification(null, null, (string) $offerData->getPoint(), null));
        }

        if (null !== $offerData->getAmount()) {
            $offerListItem->setMonetaryExchangeValue(new MonetaryAmount((string) $offerData->getAmount(), 'SGD'));
        }

        if (OfferType::VOUCHER === $offer->getType()->getValue()) {
            $this->entityManager->persist($offerListItem);
            $serialNumbers = $offerData->getVouchers();

            if (null !== $serialNumbers && \count($serialNumbers) > 0) {
                $this->createSerialNumbers($serialNumbers, $offerListItem);
            }
        }

        $this->entityManager->persist($offerListItem);

        if (null !== $offerData->getProductCatalog()) {
            $productCatalog = $this->documentManager->getRepository(ProductCatalog::class)->find($offerData->getProductCatalog());
            $offerCatalog = null;

            if (null !== $productCatalog) {
                $existingOfferCatalog = $this->entityManager->getRepository(OfferCatalog::class)->findOneBy(['name' => $productCatalog->getName()]);

                if (null === $existingOfferCatalog) {
                    $offerCatalog = $this->offerCatalogApi->createOfferCatalog($productCatalog);
                } else {
                    $offerCatalog = $existingOfferCatalog;
                }
            }

            if (null !== $offerCatalog) {
                $offerCatalog->addItemListElement($offerListItem);
                $this->entityManager->persist($offerCatalog);
            }
        }

        return $offerListItem;
    }

    public function createSerialNumbers(array $serials, OfferListItem $offerListItem)
    {
        $inventoryLevel = 0;

        foreach ($serials as $serial) {
            if (isset($serial['voucher_number'])) {
                ++$inventoryLevel;

                $offerSerialNumber = new OfferSerialNumber();
                $offerSerialNumber->setSerialNumber($serial['voucher_number']);
                $offerSerialNumber->setOfferListItem($offerListItem);

                $this->entityManager->persist($offerSerialNumber);
            }
        }

        $offerListItem->setInventoryLevel(new QuantitativeValue((string) $inventoryLevel, null, null, null));
    }
}
