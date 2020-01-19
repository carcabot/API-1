<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Entity\TariffRate;
use App\Enum\TariffRateStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TariffRateUpdater
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     * @param SerializerInterface    $serializer
     */
    public function __construct(EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->serializer = $serializer;
    }

    public function processArrayData(array $data)
    {
        foreach ($data as $datum) {
            $this->update($datum);
        }
        $this->entityManager->flush();
    }

    protected function update(array $tariffRateData)
    {
        $existingTariffRate = null;

        if (isset($tariffRateData['tariffRateNumber'])) {
            $existingTariffRate = $this->entityManager->getRepository(TariffRate::class)->findOneBy(['tariffRateNumber' => $tariffRateData['tariffRateNumber']]);

            if (null !== $existingTariffRate) {
                $tariffRateData['@id'] = $this->iriConverter->getIriFromItem($existingTariffRate);
            }
        }

        if (isset($tariffRateData['customizable'])) {
            if ('true' === $tariffRateData['customizable']) {
                $tariffRateData['customizable'] = true;
            } else {
                $tariffRateData['customizable'] = false;
            }
        }

        if (isset($tariffRateData['internalUseOnly'])) {
            if ('true' === $tariffRateData['internalUseOnly']) {
                $tariffRateData['internalUseOnly'] = true;
            } else {
                $tariffRateData['internalUseOnly'] = false;
            }
        }

        if (empty($tariffRateData['startDate'])) {
            $tariffRateData['startDate'] = null;
        }

        if (empty($tariffRateData['validFrom'])) {
            $tariffRateData['validFrom'] = null;
        }

        if (empty($tariffRateData['validThrough'])) {
            $tariffRateData['validThrough'] = null;
        }

        if (!isset($tariffRateData['@id'])) {
            $tariffRateData['status'] = TariffRateStatus::NEW;
        }

        $tariffRate = $this->serializer->deserialize(\json_encode($tariffRateData), TariffRate::class, 'jsonld', ['tariff_rate_write']);

        $this->entityManager->persist($tariffRate);
    }
}
