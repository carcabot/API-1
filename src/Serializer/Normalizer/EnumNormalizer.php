<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use MyCLabs\Enum\Enum;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class EnumNormalizer extends AbstractNormalizer
{
    public function __construct()
    {
        parent::__construct(null, null);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Enum;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = $object->getValue();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return \is_subclass_of($type, Enum::class);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $object = new $class($data);

        return $object;
    }
}
