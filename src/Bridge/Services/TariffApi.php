<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Document\OldUsers;
use App\Document\Tariff;
use App\Entity\BridgeUser;
use App\Entity\DigitalDocument;
use App\Entity\QuantitativeValue;
use App\Entity\SecurityDeposit;
use App\Entity\SecurityDepositList;
use App\Entity\SecurityDepositListItem;
use App\Entity\TariffDailyRate;
use App\Entity\TariffRate;
use App\Entity\TariffRateTerms;
use App\Entity\WebPage;
use App\Enum\ContractType;
use App\Enum\ModuleType;
use App\Enum\TariffRateStatus;
use App\Enum\TariffRateType;
use App\Enum\WebPageStatus;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;

final class TariffApi
{
    /**
     * @var string
     */
    private $bridgeApiUrl;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

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
     * @param string                 $bridgeApiUrl
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(string $bridgeApiUrl, DocumentManager $documentManager, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->client = new GuzzleClient();
        $this->baseUri = HttpUri::createFromString($this->bridgeApiUrl);
        $this->logger = $logger;
    }

    /**
     * Create or update tariff rates into database.
     *
     * @param array $tariffs
     */
    public function updateTariff(array $tariffs)
    {
        $batchSize = 20;

        foreach ($tariffs as $key => $tariff) {
            $existingTariff = $this->entityManager->getRepository(TariffRate::class)->findOneBy([
                'tariffRateNumber' => $tariff->getTariffCode(),
                'isBasedOn' => null,
            ]);

            if (null !== $existingTariff) {
                $tariffRate = $existingTariff;
            } else {
                $tariffRate = new TariffRate();
            }

            if (!empty($tariff->getTariffCode())) {
                $tariffRate->setTariffRateNumber($tariff->getTariffCode());
            }

            if (!empty($tariff->getAvailableFrom())) {
                try {
                    $tariffRate->setStartDate($tariff->getAvailableFrom());
                } catch (\Exception $e) {
                    // do nothing
                }
            }

            if (null !== $tariff->getApplicationForUse() && \count($tariff->getApplicationForUse()) > 0) {
                $tariffRate->clearUsedIn();

                foreach ($tariff->getApplicationForUse() as $applicationForUse) {
                    if (null !== $applicationForUse) {
                        $usedIn = $this->mapUsedIn($applicationForUse);
                        if ('' !== $usedIn) {
                            $tariffRate->addUsedIn($usedIn->getValue());
                        }
                    }
                }
            }

            if (!empty($tariff->getCreatedBy())) {
                $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $tariff->getCreatedBy()]);
                if (null !== $creatorDocument) {
                    $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                    if (null !== $creator) {
                        $tariffRate->setCreator($creator->getUser());
                    }
                }
            }

            if (!empty($tariff->getUpdatedBy())) {
                $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $tariff->getUpdatedBy()]);
                if (null !== $agentDocument) {
                    $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                    if (null !== $agent) {
                        $tariffRate->setAgent($agent->getUser());
                    }
                }
            }

            if (!empty($tariff->getCreatedAt())) {
                $tariffRate->setDateCreated($tariff->getCreatedAt());
            }

            if (!empty($tariff->getUpdatedAt())) {
                $tariffRate->setDateModified($tariff->getUpdatedAt());
            }

            if (null !== $tariff->getContractTypes() && \count($tariff->getContractTypes()) > 0) {
                $tariffRate->clearContractTypes();

                foreach ($tariff->getContractTypes() as $contractType) {
                    $tariffRate->addContractType($this->mapContractType($contractType)->getValue());
                }
            }

            if (!empty($tariff->getTariffDescription()) && null !== $tariff->getTariffDescription()) {
                $tariffRate->setDescription($tariff->getTariffDescription());
            }

            if (!empty($tariff->getChargeDescription()) && null !== $tariff->getChargeDescription()) {
                $tariffRate->setChargeDescription($tariff->getChargeDescription());
            }

            if (null !== $tariff->getTariffCustomized()) {
                $tariffRate->setCustomizable($tariff->getTariffCustomized());
            }

            if (null !== $tariff->getIsDailyRate()) {
                $tariffRate->setIsDailyRate($tariff->getIsDailyRate());
            }

            if (null !== $tariff->getDailyRate() && \count($tariff->getDailyRate()) > 0) {
                foreach ($tariff->getDailRate() as $dailyRateData) {
                    $dailyRate = new TariffDailyRate();

                    if (!empty($dailyRateData['day_of_rate'])) {
                        $dailyRate->setValidFrom($dailyRateData['day_of_rate']);
                    }

                    if (!empty($dailyRateData['rate']) && true === \is_numeric($dailyRateData['rate'])) {
                        if (!empty($dailyRateData['uom'])) {
                            $dailyRate->setRate(new QuantitativeValue((string) $dailyRateData['rate'], null, null, $dailyRateData['uom']));
                        } else {
                            $dailyRate->setRate(new QuantitativeValue((string) $dailyRateData['rate']));
                        }
                    }
                }
            }

            if (null !== $tariff->getTariffInternalOnly()) {
                $tariffRate->setInternalUseOnly($tariff->getTariffInternalOnly());
            }

            if (!empty($tariff->getMinContractTerm()) && true === \is_numeric($tariff->getMinContractTerm())) {
                $tariffRate->setMinContractTerm(new QuantitativeValue((string) $tariff->getMinContractTerm(), null, null, 'MON'));
            }

            if (!empty($tariff->getTariffName())) {
                $tariffRate->setName($tariff->getTariffName());
            }

            if (!empty($tariff->getTariffRemark()) && null !== $tariff->getTariffRemark()) {
                $tariffRate->setRemark($tariff->getTariffRemark());
            }

            if (!empty($tariff->getTariffLimit()) && true === \is_numeric($tariff->getTariffLimit())) {
                $tariffRate->setInventoryLevel(new QuantitativeValue(null, null, (string) $tariff->getTariffLimit()));
            }

            if (null !== $tariff->getToDesign()) {
                $toDesign = $tariff->getToDesign();

                if (null !== $tariffRate->getPage()) {
                    $webPage = $tariffRate->getPage();
                } else {
                    $webPage = new WebPage();
                }

                if (!empty($toDesign->getDetailMessage())) {
                    $webPage->setDescription($toDesign->getDetailMessage());
                }

                if (!empty($toDesign->getImageUrl())) {
                    $appendUri = \sprintf('%s/%s', 'promotion_code', $toDesign->getImageUrl());

                    $modifier = new AppendSegment($appendUri);

                    $imageUri = $modifier->process($this->baseUri);

                    if (null !== $webPage->getImage()) {
                        $webPageImage = $webPage->getImage();
                    } else {
                        $webPageImage = new DigitalDocument();
                    }

                    $webPageImage->setName($toDesign->getImageUrl());

                    $webPageImage->setUrl((string) $imageUri);

                    $webPage->setImage($webPageImage);

                    if (!empty($tariff->getCreatedBy())) {
                        $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $tariff->getCreatedBy()]);
                        if (null !== $creatorDocument) {
                            $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                            if (null !== $creator) {
                                $webPageImage->setCreator($creator->getUser());
                            }
                        }
                    }

                    if (!empty($tariff->getUpdatedBy())) {
                        $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $tariff->getUpdatedBy()]);
                        if (null !== $agentDocument) {
                            $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                            if (null !== $agent) {
                                $webPageImage->setAgent($agent->getUser());
                            }
                        }
                    }

                    if (!empty($tariff->getCreatedAt())) {
                        $webPageImage->setDateCreated($tariff->getCreatedAt());
                    }

                    if (!empty($tariff->getUpdatedAt())) {
                        $webPageImage->setDateModified($tariff->getUpdatedAt());
                    }
                    $this->entityManager->persist($webPageImage);
                }

                if (!empty($toDesign->getSummaryMessage())) {
                    $webPage->setDisambiguatingDescription($toDesign->getSummaryMessage());
                }

                if (!empty($toDesign->getSequenceNumber())) {
                    $webPage->setPosition($toDesign->getSequenceNumber());
                }

                if (!empty($toDesign->getReturnUrl())) {
                    $webPage->setReturnUrl($toDesign->getReturnUrl());
                }

                if (!empty($tariff->getCreatedBy())) {
                    $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $tariff->getCreatedBy()]);
                    if (null !== $creatorDocument) {
                        $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                        if (null !== $creator) {
                            $webPage->setCreator($creator->getUser());
                        }
                    }
                }

                if (!empty($tariff->getUpdatedBy())) {
                    $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $tariff->getUpdatedBy()]);
                    if (null !== $agentDocument) {
                        $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                        if (null !== $agent) {
                            $webPage->setAgent($agent->getUser());
                        }
                    }
                }

                if (!empty($tariff->getCreatedAt())) {
                    $webPage->setDateCreated($tariff->getCreatedAt());
                }

                if (!empty($tariff->getUpdatedAt())) {
                    $webPage->setDateModified($tariff->getUpdatedAt());
                }

                $webPage->setStatus(new WebPageStatus(WebPageStatus::ACTIVE));
                $tariffRate->setPage($webPage);

                $this->entityManager->persist($webPage);
            }

            if (!empty($tariff->getTariffEndDate())) {
                try {
                    $tariffRate->setValidThrough(new \DateTime($tariff->getTariffEndDate()));
                } catch (\Exception $e) {
                    // do nothing
                }
            }

            if (!empty($tariff->getTariffStartDate())) {
                try {
                    $tariffRate->setValidFrom(new \DateTime($tariff->getTariffStartDate()));
                } catch (\Exception $e) {
                    // do nothing
                }
            }

            if (null !== $tariff->getTariffStatus()) {
                $tariffRate->setStatus($this->getStatus($tariff->getTariffStatus()));
            }

            $terms = $tariffRate->getTerms();

            if (null === $terms) {
                $terms = new TariffRateTerms();
                $terms->setTariffRate($tariffRate);
            }

            if (!empty($tariff->getAmiFee())) {
                $terms->setMeterInstallationFee($tariff->getAmiFee());
            }

            if (!empty($tariff->getBundledService())) {
                if (false === \stripos($tariff->getBundledService(), 'NA')) {
                    $terms->setBundledProductsOrServices($tariff->getBundledService());
                }
            }

            if (!empty($tariff->getContractDuration())) {
                $terms->setContractDuration($tariff->getContractDuration());
            }

            if (!empty($tariff->getContractRenewal())) {
                $terms->setContractRenewal($tariff->getContractRenewal());
            }

            if (!empty($tariff->getEarlyTerminationCharge())) {
                $terms->setEarlyTerminationCharges($tariff->getEarlyTerminationCharge());
            }

            if (!empty($tariff->getLatePaymentCharge())) {
                $terms->setLatePaymentCharges($tariff->getLatePaymentCharge());
            }

            if (!empty($tariff->getOneTimeFee())) {
                $terms->setRegistrationFee($tariff->getOneTimeFee());
            }

            if (!empty($tariff->getOtherFee())) {
                $terms->setOtherFeeAndCharges($tariff->getOtherFee());
            }

            if (!empty($tariff->getPricePlanName())) {
                $terms->setPlanName($tariff->getPricePlanName());
            }

            if (!empty($tariff->getPricePlanStandard())) {
                $terms->setStandardPlan($tariff->getPricePlanStandard());
            }

            if (!empty($tariff->getPricePlanType())) {
                $terms->setPlanType($tariff->getPricePlanType());
            }

            if (!empty($tariff->getPricePlanNonStandard())) {
                $terms->setNonStandardPlan($tariff->getPricePlanNonStandard());
            }

            if (!empty($tariff->getRetailerIncentive())) {
                $terms->setIncentives($tariff->getRetailerIncentive());
            }

            if (!empty($tariff->getRetailerName())) {
                $terms->setServiceProvider($tariff->getRetailerName());
            }

            if (!empty($tariff->getRetailerToBill())) {
                $retailerToBill = false;

                if (false !== \stripos($tariff->getRetailerToBill(), 'yes')) {
                    $retailerToBill = true;
                }

                $terms->setBillFromServiceProvider($retailerToBill);
            }

            if (!empty($tariff->getSecurityDeposit())) {
                $terms->setSecurityDeposit($tariff->getSecurityDeposit());
                /*$list = $terms->getSecurityDepositList();

                if (null === $list) {
                    $list = new SecurityDepositList();
                    $terms->setSecurityDepositList($list);
                }

                $listItems = $list->getItemListElement();

                $listItem = \current($listItems);

                if (false === $listItem) {
                    $listItem = new SecurityDepositListItem();
                    $securityDeposit = new SecurityDeposit();
                    $listItem->setItem($securityDeposit);
                    $list->addItemListElement($listItem);

                    $this->entityManager->persist($securityDeposit);
                    $this->entityManager->persist($listItem);
                    $this->entityManager->persist($list);
                } else {
                    $securityDeposit = $listItem->getItem();
                }

                $listItem->setName($tariff->getSecurityDeposit());
                $securityDeposit->setName($tariff->getSecurityDeposit());*/
            }

            if (!empty($tariff->getUsedSmartMeter())) {
                $smartMeterUse = false;

                if (false !== \stripos($tariff->getUsedSmartMeter(), 'yes')) {
                    $smartMeterUse = true;
                }

                $terms->setSmartMeter($smartMeterUse);
            }

            if (null !== $tariff->getVersionDate()) {
                $terms->setVersion($tariff->getVersionDate()->format('Y-m-d'));
            }

            if (!empty($tariff->getCreatedBy())) {
                $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $tariff->getCreatedBy()]);
                if (null !== $creatorDocument) {
                    $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                    if (null !== $creator) {
                        $terms->setCreator($creator->getUser());
                    }
                }
            }

            if (!empty($tariff->getUpdatedBy())) {
                $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $tariff->getUpdatedBy()]);
                if (null !== $agentDocument) {
                    $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                    if (null !== $agent) {
                        $terms->setAgent($agent->getUser());
                    }
                }
            }

            if (!empty($tariff->getCreatedAt())) {
                $terms->setDateCreated($tariff->getCreatedAt());
            }

            if (!empty($tariff->getUpdatedAt())) {
                $terms->setDateModified($tariff->getUpdatedAt());
            }

            $tariffRate->setType(new TariffRateType(TariffRateType::NORMAL));

            $this->entityManager->persist($tariffRate);
            $this->entityManager->flush();

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function getStatus(string $status)
    {
        $statusMap = [
            'ACTIVE' => new TariffRateStatus(TariffRateStatus::ACTIVE),
            'DELETED' => new TariffRateStatus(TariffRateStatus::DELETED),
            'ENDED' => new TariffRateStatus(TariffRateStatus::ENDED),
            'IN_PROGRESS' => new TariffRateStatus(TariffRateStatus::IN_PROGRESS),
            'NEW' => new TariffRateStatus(TariffRateStatus::NEW),
        ];

        return $statusMap[$status];
    }

    private function mapContractType(string $contractType)
    {
        $typesMap = [
            'RESIDENTIAL' => new ContractType(ContractType::RESIDENTIAL),
            'COMMERCIAL' => new ContractType(ContractType::COMMERCIAL),
        ];

        return $typesMap[$contractType];
    }

    private function mapUsedIn(string $applicationForUse)
    {
        if (false !== \stripos($applicationForUse, 'campaigns')) {
            return new ModuleType(ModuleType::CAMPAIGN);
        } elseif (false !== \stripos($applicationForUse, 'homepage')) {
            return new ModuleType(ModuleType::CLIENT_HOMEPAGE);
        } elseif (false !== \stripos($applicationForUse, 'partnership')) {
            return new ModuleType(ModuleType::PARTNERSHIP_PORTAL);
        } elseif (false !== \stripos($applicationForUse, 'quotation')) {
            return new ModuleType(ModuleType::QUOTATION_CONTRACT);
        } elseif (false !== \stripos($applicationForUse, 'campaigns')) {
            return new ModuleType(ModuleType::CAMPAIGN);
        }

        return '';
    }
}
