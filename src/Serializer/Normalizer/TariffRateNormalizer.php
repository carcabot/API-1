<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\Entity\ApplicationRequest;
use App\Entity\TariffRate;
use App\Enum\ApplicationRequestStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

class TariffRateNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait {
        setSerializer as baseSetSerializer;
    }

    /**
     * @var NormalizerInterface
     */
    private $decorated;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param NormalizerInterface    $decorated
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(NormalizerInterface $decorated, EntityManagerInterface $entityManager)
    {
        $this->decorated = $decorated;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof TariffRate) {
            return $this->decorated->normalize($object, $format, $context);
        }

        /** @var TariffRate $tariffRate */
        $tariffRate = $object;

        $data = $this->decorated->normalize($object, $format, $context);

        if (!\is_array($data)) {
            return $data;
        }

        if (!empty($data['inventoryLevel']) && null !== $data['inventoryLevel']['maxValue']) {
            $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('applicationRequest');

            $applications = $qb->select($qb->expr()->count('applicationRequest'))
                ->leftJoin('applicationRequest.tariffRate', 'tariffRate')
                ->where($qb->expr()->orX(
                    $qb->expr()->eq('tariffRate.id', ':tariffRate'),
                    $qb->expr()->eq('tariffRate.isBasedOn', ':tariffRate')
                ))
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->eq('applicationRequest.status', ':statusCompleted'),
                    $qb->expr()->eq('applicationRequest.status', ':statusInProgress')
                ))
                ->setParameter('tariffRate', $tariffRate->getId())
                ->setParameter('statusInProgress', new ApplicationRequestStatus(ApplicationRequestStatus::IN_PROGRESS))
                ->setParameter('statusCompleted', new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED))
                ->getQuery()
                ->getSingleScalarResult();

            $currentInventoryLevel = $data['inventoryLevel']['maxValue'] - $applications;

            if ($currentInventoryLevel < 0) {
                $currentInventoryLevel = 0;
            }

            $data['inventoryLevel']['value'] = (string) \number_format($currentInventoryLevel, 4, '.', '');
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $this->decorated->supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return $this->decorated->denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->baseSetSerializer($serializer);

        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }
}
