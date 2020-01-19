<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use App\Entity\ThirdPartyCharge;
use App\Entity\ThirdPartyChargeConfiguration;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ThirdPartyChargeConfigurationDataProvider implements SubresourceDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param EntityManagerInterface $entityManager
     * @param WebServiceClient       $webServiceClient
     * @param LoggerInterface        $logger
     * @param string                 $timezone
     */
    public function __construct(EntityManagerInterface $entityManager, WebServiceClient $webServiceClient, LoggerInterface $logger, string $timezone)
    {
        $this->entityManager = $entityManager;
        $this->webServiceClient = $webServiceClient;
        $this->logger = $logger;
        $this->timezone = new \DateTimeZone($timezone);
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return ThirdPartyChargeConfiguration::class === $resourceClass;
    }

    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        $repository = $this->entityManager->getRepository($resourceClass);

        if (!\in_array('third_party_charge_config_read', $context, true)) {
            return [];
        }

        $data = $this->webServiceClient->getThirdPartyChargeConfiguration();
        try {
            foreach ($data->ThirdParyChargesTemplates->ThirdParyChargesTemplate as $template) {
                if (true === isset($template->TemplateCode) && null !== $template->TemplateCode) {
                    $thirdPartyChargeConfiguration = $repository->findOneBy(['configurationNumber' => $template->TemplateCode]);

                    if (null === $thirdPartyChargeConfiguration) {
                        $thirdPartyChargeConfiguration = new ThirdPartyChargeConfiguration();
                    }

                    $effectiveEndDate = null;
                    if ($thirdPartyChargeConfiguration instanceof ThirdPartyChargeConfiguration) {
                        if (true === isset($template->EffectiveEndDate) && null !== $template->EffectiveEndDate) {
                            $effectiveEndDate = \DateTime::createFromFormat('Ymd', $template->EffectiveEndDate, $this->timezone);
                            $thirdPartyChargeConfiguration->setValidThrough($effectiveEndDate);
                        }

                        if (null !== $effectiveEndDate && $effectiveEndDate->getTimestamp() < (new \DateTime())->getTimestamp()) {
                            $thirdPartyChargeConfiguration->setEnabled(false);
                        } else {
                            $thirdPartyChargeConfiguration->setConfigurationNumber($template->TemplateCode);
                            $thirdPartyChargeConfiguration->setName($template->TemplateName);
                            $effectiveStartDate = \DateTime::createFromFormat('Ymd', $template->EffectiveStartDate, $this->timezone);
                            $thirdPartyChargeConfiguration->setValidFrom($effectiveStartDate);
                            $thirdPartyChargeConfiguration->setEnabled(true);
                            $thirdPartyChargeConfiguration->clearCharges();

                            $thirdPartyChargeItems = $template->ThirdPartyChargeItems->ThirdPartyChargeItem;

                            if (!\is_array($thirdPartyChargeItems)) {
                                $thirdPartyChargeItems = [$thirdPartyChargeItems];
                            }

                            foreach ($thirdPartyChargeItems as $thirdPartyChargeItem) {
                                if (true === isset($thirdPartyChargeItem->ItemNumber) && null !== $thirdPartyChargeItem->ItemNumber) {
                                    $thirdPartyCharge = $this->entityManager->getRepository(ThirdPartyCharge::class)->findOneBy(['thirdPartyChargeNumber' => $thirdPartyChargeItem->ItemNumber]);

                                    if (null === $thirdPartyCharge) {
                                        $thirdPartyCharge = new ThirdPartyCharge();
                                    }
                                    $thirdPartyCharge->setThirdPartyChargeNumber($thirdPartyChargeItem->ItemNumber);
                                    $thirdPartyCharge->setDescription($thirdPartyChargeItem->ChargeDescription);
                                    $startDate = \DateTime::createFromFormat('Ymd', $thirdPartyChargeItem->StartDate, $this->timezone);
                                    $thirdPartyCharge->setValidFrom($startDate);

                                    if (true === isset($thirdPartyChargeItem->ItemChargeType) && 'Included' === $thirdPartyChargeItem->ItemChargeType) {
                                        $thirdPartyCharge->setEnabled(true);
                                    } else {
                                        $thirdPartyCharge->setEnabled(false);
                                    }

                                    //@todo need a shorter checking for undefined field
                                    if (true === isset($thirdPartyChargeItem->EndDate) && null !== $thirdPartyChargeItem->EndDate) {
                                        $endDate = \DateTime::createFromFormat('Ymd', $thirdPartyChargeItem->EndDate, $this->timezone);
                                        $thirdPartyCharge->setValidThrough($endDate);
                                    }
                                    $thirdPartyChargeConfiguration->addCharge($thirdPartyCharge);
                                    $this->entityManager->persist($thirdPartyCharge);
                                }
                            }
                        }

                        $this->entityManager->persist($thirdPartyChargeConfiguration);
                        $this->entityManager->flush();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $thirdPartyChargeConfigurations = $repository->findAll();

            return $thirdPartyChargeConfigurations;
        }

        $thirdPartyChargeConfigurations = $repository->findAll();

        return $thirdPartyChargeConfigurations;
    }
}
