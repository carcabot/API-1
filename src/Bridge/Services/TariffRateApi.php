<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Entity\AddonService;
use App\Entity\AddonServiceList;
use App\Entity\AddonServiceListItem;
use App\Entity\BridgeUser;
use App\Entity\DigitalDocument;
use App\Entity\QuantitativeValue;
use App\Entity\SecurityDeposit;
use App\Entity\SecurityDepositList;
use App\Entity\SecurityDepositListItem;
use App\Entity\TariffRate;
use App\Entity\TariffRateTerms;
use App\Entity\WebPage;
use App\Enum\ContractType;
use App\Enum\ModuleType;
use App\Enum\TariffRateStatus;
use App\Enum\WebPageStatus;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;

final class TariffRateApi
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
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param string                 $bridgeApiUrl
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(string $bridgeApiUrl, EntityManagerInterface $entityManager)
    {
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->entityManager = $entityManager;
        $this->client = new GuzzleClient();
        $this->baseUri = HttpUri::createFromString($this->bridgeApiUrl);
    }

    /**
     * Gets tariff rates from old version.
     *
     * @param BridgeUser $bridgeUser
     *
     * @return array
     */
    public function getTariffRates(BridgeUser $bridgeUser)
    {
        $tariffRates = [];

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'x-access-token' => $bridgeUser->getAuthToken(),
        ];

        $modifier = new AppendSegment('promotion-code');
        $tariffRateUri = $modifier->process($this->baseUri);

        $tariffRateRequest = new GuzzlePsr7Request('GET', $tariffRateUri, $headers);
        $tariffRateResponse = $this->client->send($tariffRateRequest);
        $tariffRateResult = \json_decode((string) $tariffRateResponse->getBody(), true);

        if (200 === $tariffRateResult['status'] && 1 === $tariffRateResult['flag']) {
            $tariffRates = $tariffRateResult['data'];
        }

        return $tariffRates;
    }

    /**
     * Create or update tariff rates into database.
     *
     * @param array $tariffRates
     */
    public function updateTariffRates(array $tariffRates)
    {
        $tariffRateArray = [];
        $tariffRateData = [];
        $tariffRateNumbers = [];

        foreach ($tariffRates as $tariffRate) {
            $tariffRateData[$tariffRate['promotion_code']] = $tariffRate;
            $tariffRateNumbers[] = $tariffRate['promotion_code'];
        }

        $qb = $this->entityManager->createQueryBuilder();

        $existingTariffRates = $qb->select('tariffRate')
            ->from(TariffRate::class, 'tariffRate')
            ->where($qb->expr()->in('tariffRate.tariffRateNumber', $tariffRateNumbers))
            ->andWhere($qb->expr()->isNull('tariffRate.isBasedOn'))
            ->getQuery()
            ->getResult();

        //remap for fast access
        foreach ($existingTariffRates as $existingTariffRate) {
            $tariffRateArray[$existingTariffRate->getTariffRateNumber()] = $existingTariffRate;
        }

        foreach ($tariffRateNumbers as $tariffRateNumber) {
            if (isset($tariffRateArray[$tariffRateNumber])) {
                $tariffRate = $tariffRateArray[$tariffRateNumber];
            } else {
                $tariffRate = new TariffRate();
                $tariffRate->setTariffRateNumber($tariffRateNumber);
            }

            $newData = $tariffRateData[$tariffRateNumber];

            if (\count($newData['contract_types']) > 0) {
                $tariffRate->clearContractTypes();

                foreach ($newData['contract_types'] as $contractType) {
                    $tariffRate->addContractType($this->mapContractType($contractType)->getValue());
                }
            }

            $tariffRate->setBridgeId($newData['_id']);
            $tariffRate->setName($newData['promotion_name']);
            $tariffRate->setDescription($newData['promotion_desc'] ?? null);
            $tariffRate->setStatus($this->getStatus($newData['promotion_status']));

            if (!empty($newData['promotion_start_date'])) {
                try {
                    $tariffRate->setValidFrom(new \DateTime($newData['promotion_start_date']));
                } catch (\Exception $e) {
                    // do nothing
                }
            }

            if (!empty($newData['promotion_end_date'])) {
                try {
                    $tariffRate->setValidThrough(new \DateTime($newData['promotion_end_date']));
                } catch (\Exception $e) {
                    // do nothing
                }
            }

            if (!empty($newData['available_from'])) {
                try {
                    $tariffRate->setStartDate(new \DateTime($newData['available_from']));
                } catch (\Exception $e) {
                    // do nothing
                }
            }

            if (!empty($newData['promotion_limit']) && true === \is_numeric($newData['promotion_limit'])) {
                $tariffRate->setInventoryLevel(new QuantitativeValue(null, null, (string) $newData['promotion_limit']));
            }

            $tariffRate->setRemark($newData['promotion_remark'] ?? null);
            $tariffRate->setCustomizable($newData['promotion_customized'] ?? null);

            if (!empty($newData['min_contract_term']) && true === \is_numeric($newData['min_contract_term'])) {
                $tariffRate->setMinContractTerm(new QuantitativeValue((string) $newData['min_contract_term'], null, null, 'MON'));
            }

            if (\count($newData['application_for_use']) > 0) {
                $tariffRate->clearUsedIn();

                foreach ($newData['application_for_use'] as $applicationForUse) {
                    if (!empty($applicationForUse)) {
                        $usedIn = $this->mapUsedIn($applicationForUse);
                        if ('' !== $usedIn) {
                            $tariffRate->addUsedIn($usedIn->getValue());
                        }
                    }
                }
            }

            if (isset($newData['to_design'])) {
                $toDesign = $newData['to_design'];

                if (null !== $tariffRate->getPage()) {
                    $webPage = $tariffRate->getPage();
                } else {
                    $webPage = new WebPage();
                }

                if (!empty($toDesign['detail_message'])) {
                    $webPage->setDescription($toDesign['detail_message']);
                }

                if (!empty($toDesign['image_url'])) {
                    $appendUri = \sprintf('%s/%s', 'promotion_code', $toDesign['image_url']);

                    $modifier = new AppendSegment($appendUri);
                    $imageUri = $modifier->process($this->baseUri);

                    if (null !== $webPage->getImage()) {
                        $webPageImage = $webPage->getImage();
                    } else {
                        $webPageImage = new DigitalDocument();
                    }

                    $webPageImage->setName($toDesign['image_url']);
                    $webPageImage->setUrl((string) $imageUri);

                    $webPage->setImage($webPageImage);

                    $this->entityManager->persist($webPageImage);
                }

                if (!empty($toDesign['summary_message'])) {
                    $webPage->setDisambiguatingDescription($toDesign['summary_message']);
                }

                if (!empty($toDesign['sequence_number'])) {
                    $webPage->setPosition($toDesign['sequence_number']);
                }

                if (!empty($toDesign['return_url'])) {
                    $webPage->setReturnUrl($toDesign['return_url']);
                }
                $webPage->setStatus(new WebPageStatus(WebPageStatus::ACTIVE));
                $tariffRate->setPage($webPage);

                $this->entityManager->persist($webPage);
            }

            $terms = $tariffRate->getTerms();

            if (null === $terms) {
                $terms = new TariffRateTerms();
                $terms->setTariffRate($tariffRate);
            }

            if (!empty($newData['version_date'])) {
                $terms->setVersion($newData['version_date']);
            }

            if (!empty($newData['price_plan_standard'])) {
                $terms->setStandardPlan($newData['price_plan_standard']);
            }

            if (!empty($newData['price_plan_name'])) {
                $terms->setPlanName($newData['price_plan_name']);
            }

            if (!empty($newData['price_plan_type'])) {
                $terms->setPlanType($newData['price_plan_type']);
            }

            if (!empty($newData['retailer_name'])) {
                $terms->setServiceProvider($newData['retailer_name']);
            }

            if (!empty($newData['retailer_incentive'])) {
                $terms->setIncentives($newData['retailer_incentive']);
            }

            if (!empty($newData['contract_duration'])) {
                $terms->setContractDuration($newData['contract_duration']);
            }

            if (!empty($newData['contract_renewal'])) {
                $terms->setContractRenewal($newData['contract_renewal']);
            }

            if (!empty($newData['used_smart_meter'])) {
                $smartMeterUse = false;

                if (false !== \stripos($newData['used_smart_meter'], 'yes')) {
                    $smartMeterUse = true;
                }

                $terms->setSmartMeter($smartMeterUse);
            }

            if (!empty($newData['retailer_to_bill'])) {
                $retailerToBill = false;

                if (false !== \stripos($newData['retailer_to_bill'], 'yes')) {
                    $retailerToBill = true;
                }

                $terms->setBillFromServiceProvider($retailerToBill);
            }

            if (!empty($newData['ami_fee'])) {
                $terms->setMeterInstallationFee($newData['ami_fee']);
            }

            if (!empty($newData['price_plan_non_standard'])) {
                $terms->setNonStandardPlan($newData['price_plan_non_standard']);
            }

            if (!empty($newData['early_termination_charge'])) {
                $terms->setEarlyTerminationCharges($newData['early_termination_charge']);
            }

            if (!empty($newData['other_fee'])) {
                $terms->setOtherFeeAndCharges($newData['other_fee']);
            }

            if (!empty($newData['late_payment_charge'])) {
                $terms->setLatePaymentCharges($newData['late_payment_charge']);
            }

            if (!empty($newData['one_time_fee'])) {
                $terms->setRegistrationFee($newData['one_time_fee']);
            }

            if (!empty($newData['security_deposit'])) {
                $list = $terms->getSecurityDepositList();

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

                $listItem->setName($newData['security_deposit']);
                $securityDeposit->setName($newData['security_deposit']);
            }

            if (!empty($newData['bundled_service'])) {
                $terms->setBundledProductsOrServices($newData['bundled_service']);
                /*$list = $terms->getAddonServiceList();

                if (null === $list) {
                    $list = new AddonServiceList();
                    $terms->setAddonServiceList($list);
                }

                $listItems = $list->getItemListElement();

                $listItem = \current($listItems);

                if (false === $listItem) {
                    $listItem = new AddonServiceListItem();
                    $addonService = new AddonService();
                    $listItem->setItem($addonService);
                    $list->addItemListElement($listItem);

                    $this->entityManager->persist($addonService);
                    $this->entityManager->persist($listItem);
                    $this->entityManager->persist($list);
                } else {
                    $addonService = $listItem->getItem();
                }

                $listItem->setName($newData['bundled_service']);
                $addonService->setName($newData['bundled_service']);*/
            }

            $this->entityManager->persist($tariffRate);
            $this->entityManager->flush();
        }
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
