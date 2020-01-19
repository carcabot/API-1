<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Document\OldUsers;
use App\Document\PointCategory;
use App\Document\PointType;
use App\Entity\BridgeUser;
use App\Entity\CreditsScheme;
use App\Entity\MonetaryAmount;
use App\Entity\QuantitativeValue;
use App\Entity\ReferralCreditsScheme;
use App\Enum\CreditsType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CreditsSchemeApi
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

    public function createCreditsSchemes(array $creditsSchemes)
    {
        foreach ($creditsSchemes as $creditsSchemeData) {
            $this->createCreditsScheme($creditsSchemeData);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function createCreditsScheme(PointType $creditsSchemeData): CreditsScheme
    {
        $existingCreditsScheme = $this->entityManager->getRepository(CreditsScheme::class)->findOneBy(['schemeId' => $creditsSchemeData->getId(), 'isBasedOn' => null]);

        $creditsScheme = new CreditsScheme();

        if (null !== $existingCreditsScheme) {
            $creditsScheme = $existingCreditsScheme;
        }

        $pointCategory = $this->documentManager->getRepository(PointCategory::class)->find($creditsSchemeData->getPointCategory());

        if (null !== $creditsSchemeData->getPointId() && 'RF' !== $creditsSchemeData->getPointId()) {
            $creditsScheme->setSchemeId($creditsSchemeData->getPointId());
        } else {
            return $this->createReferralCreditsScheme($creditsSchemeData);
        }

        if (null !== $pointCategory) {
            if ('RP' === $pointCategory->getPointCategory()) {
                $creditsScheme->setType(new CreditsType(CreditsType::RP));
            } else {
                $creditsScheme->setType(new CreditsType(CreditsType::OP));
            }
        }

        if (null !== $creditsSchemeData->getValidFrom()) {
            $creditsScheme->setValidFrom($creditsSchemeData->getValidFrom());
        }

        if (null !== $creditsSchemeData->getValidThrough()) {
            $creditsScheme->setValidThrough($creditsSchemeData->getValidThrough());
        }

        if (null !== $creditsSchemeData->getCreatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $creditsSchemeData->getCreatedAt()]);

            if (null !== $oldUser) {
                $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $creator) {
                    $creditsScheme->setCreator($creator->getUser());
                }
            }
        }

        if (null !== $creditsSchemeData->getUpdatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $creditsSchemeData->getUpdatedBy()]);

            if (null !== $oldUser) {
                $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $agent) {
                    $creditsScheme->setAgent($agent->getUser());
                }
            }
        }

        if (null !== $creditsSchemeData->getCreatedAt()) {
            $creditsScheme->setDateCreated($creditsSchemeData->getCreatedAt());
        }

        if (null !== $creditsSchemeData->getUpdatedAt()) {
            $creditsScheme->setDateModified($creditsSchemeData->getUpdatedAt());
        }

        $creditsScheme->setAmount(new QuantitativeValue((string) $creditsSchemeData->getPoint(), null, null, null));

        if (0 !== $creditsSchemeData->getAmount()) {
            $creditsScheme->setMonetaryExchangeValue(new MonetaryAmount((string) $creditsSchemeData->getAmount(), 'SGD'));
        }

        $this->entityManager->persist($creditsScheme);

        return $creditsScheme;
    }

    public function createReferralCreditsScheme(PointType $referralCreditsSchemeData): ReferralCreditsScheme
    {
        $existingReferralCreditsScheme = $this->entityManager->getRepository(ReferralCreditsScheme::class)->findOneBy(['schemeId' => $referralCreditsSchemeData->getId(), 'isBasedOn' => null]);

        $referralCreditsScheme = new ReferralCreditsScheme();

        if (null !== $existingReferralCreditsScheme) {
            $referralCreditsScheme = $existingReferralCreditsScheme;
        }

        $referralCreditsScheme->setSchemeId($referralCreditsSchemeData->getPointId());

        $pointCategory = $this->documentManager->getRepository(PointCategory::class)->find($referralCreditsSchemeData->getPointCategory());
        if (null !== $pointCategory) {
            if ('RP' === $pointCategory->getPointCategory()) {
                $referralCreditsScheme->setType(new CreditsType(CreditsType::RP));
            } else {
                $referralCreditsScheme->setType(new CreditsType(CreditsType::OP));
            }
        }

        if (null !== $referralCreditsSchemeData->getValidFrom()) {
            $referralCreditsScheme->setValidFrom($referralCreditsSchemeData->getValidFrom());
        }

        if (null !== $referralCreditsSchemeData->getValidThrough()) {
            $referralCreditsScheme->setValidThrough($referralCreditsSchemeData->getValidThrough());
        }

        if (null !== $referralCreditsSchemeData->getCreatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $referralCreditsSchemeData->getCreatedAt()]);

            if (null !== $oldUser) {
                $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $creator) {
                    $referralCreditsScheme->setCreator($creator->getUser());
                }
            }
        }

        if (null !== $referralCreditsSchemeData->getUpdatedBy()) {
            $oldUser = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $referralCreditsSchemeData->getUpdatedBy()]);

            if (null !== $oldUser) {
                $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $oldUser->getId()]);
                if (null !== $agent) {
                    $referralCreditsScheme->setAgent($agent->getUser());
                }
            }
        }

        $referralCreditsScheme->setReferralAmount(new QuantitativeValue((string) $referralCreditsSchemeData->getPoint(), null, null, null));

        $this->entityManager->persist($referralCreditsScheme);

        return $referralCreditsScheme;
    }
}
