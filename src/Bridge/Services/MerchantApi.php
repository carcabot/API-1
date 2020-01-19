<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Document\OldUsers;
use App\Document\ProductPartner;
use App\Entity\BridgeUser;
use App\Entity\Merchant;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MerchantApi
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

    public function createMerchants(array $merchants)
    {
        foreach ($merchants as $merchantData) {
            $this->createMerchant($merchantData);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function createMerchant(ProductPartner $merchantData): Merchant
    {
        $existingMerchant = $this->entityManager->getRepository(Merchant::class)->findOneBy(['merchantNumber' => $merchantData->getCode()]);

        $merchant = new Merchant();

        if (null !== $existingMerchant) {
            $merchant = $existingMerchant;
        }

        if (null !== $merchantData->getName()) {
            $merchant->setName($merchantData->getName());
        }

        if (null !== $merchantData->getCode()) {
            $merchant->setMerchantNumber($merchantData->getCode());
        }

        if (null !== $merchantData->getCreatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $merchantData->getCreatedAt()]);

            if (null !== $oldUser) {
                $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $creator) {
                    $merchant->setCreator($creator->getUser());
                }
            }
        }

        if (null !== $merchantData->getUpdatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $merchantData->getUpdatedBy()]);

            if (null !== $oldUser) {
                $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $agent) {
                    $merchant->setAgent($agent->getUser());
                }
            }
        }

        if (null !== $merchantData->getCreatedAt()) {
            $merchant->setDateCreated($merchantData->getCreatedAt());
        }

        if (null !== $merchantData->getUpdatedAt()) {
            $merchant->setDateModified($merchantData->getUpdatedAt());
        }

        $this->entityManager->persist($merchant);

        return $merchant;
    }
}
