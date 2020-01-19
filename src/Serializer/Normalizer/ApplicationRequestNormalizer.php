<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Entity\ApplicationRequest;
use App\Entity\Promotion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

class ApplicationRequestNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
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
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @param NormalizerInterface    $decorated
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     */
    public function __construct(NormalizerInterface $decorated, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter)
    {
        $this->decorated = $decorated;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
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
        return $this->decorated->normalize($object, $format, $context);
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
        if (ApplicationRequest::class !== $class) {
            return $this->decorated->denormalize($data, $class, $format, $context);
        }

        $promotion = null;
        if (!empty($data['promotion']['promotionNumber']) && null !== $data['promotion']['promotionNumber']) {
            $existingPromotion = $this->entityManager->getRepository(Promotion::class)->findOneBy(['promotionNumber' => $data['promotion']['promotionNumber'], 'isBasedOn' => null]);

            if (null !== $existingPromotion) {
                $promotion = $existingPromotion;
            }

            if (null !== $promotion) {
                unset($data['promotion']['promotionNumber']);
                $data['promotion'] = $this->iriConverter->getIriFromItem($promotion);
            }
        }

        $object = $this->decorated->denormalize($data, $class, $format, $context);

        return $object;
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
