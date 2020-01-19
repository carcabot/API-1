<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\Entity\Contract;
use App\WebService\Billing\Controller\ContractBillingSummaryController;
use App\WebService\Billing\Controller\ContractWelcomePackageController;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory as UploaderMappingFactory;
use Vich\UploaderBundle\Storage\StorageInterface as UploaderStorageInterface;

class ContractNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait {
        setSerializer as baseSetSerializer;
    }

    /**
     * @var UploaderStorageInterface
     */
    private $uploaderStorage;

    /**
     * @var UploaderMappingFactory
     */
    private $uploaderMappingFactory;

    /**
     * @var NormalizerInterface
     */
    private $decorated;

    /**
     * @var ContractBillingSummaryController
     */
    private $contractBillingSummaryController;

    /**
     * @var ContractWelcomePackageController
     */
    private $contractWelcomePackageController;

    public function __construct(UploaderStorageInterface $uploaderStorage, UploaderMappingFactory $uploaderMappingFactory, NormalizerInterface $decorated, ContractBillingSummaryController $contractBillingSummaryController, ContractWelcomePackageController $contractWelcomePackageController)
    {
        $this->uploaderStorage = $uploaderStorage;
        $this->uploaderMappingFactory = $uploaderMappingFactory;
        $this->decorated = $decorated;
        $this->contractBillingSummaryController = $contractBillingSummaryController;
        $this->contractWelcomePackageController = $contractWelcomePackageController;
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
        if (!$object instanceof Contract) {
            return $this->decorated->normalize($object, $format, $context);
        }

        /** @var Contract $contract */
        $contract = $object;

        $data = $this->decorated->normalize($object, $format, $context);

        if (!\is_array($data)) {
            return $data;
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
