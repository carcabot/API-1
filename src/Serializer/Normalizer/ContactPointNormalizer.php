<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\Entity\ContactPoint;
use App\Entity\PhoneNumberRole;
use App\PropertyAccess\ClosurePropertyAccessor;
use Doctrine\Common\Persistence\Proxy as DoctrineProxy;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

class ContactPointNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait {
        setSerializer as baseSetSerializer;
    }

    /**
     * @var PhoneNumberUtil
     */
    private $phoneNumberUtil;

    /**
     * @var ClosurePropertyAccessor
     */
    private $closurePropertyAccessor;

    /**
     * @var NormalizerInterface
     */
    private $decorated;

    /**
     * @param PhoneNumberUtil         $phoneNumberUtil
     * @param ClosurePropertyAccessor $closurePropertyAccessor
     * @param NormalizerInterface     $decorated
     */
    public function __construct(PhoneNumberUtil $phoneNumberUtil, ClosurePropertyAccessor $closurePropertyAccessor, NormalizerInterface $decorated)
    {
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->closurePropertyAccessor = $closurePropertyAccessor;
        $this->decorated = $decorated;
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
        if (ContactPoint::class !== $class) {
            return $this->decorated->denormalize($data, $class, $format, $context);
        }

        $faxNumbers = null;
        $mobilePhoneNumbers = null;
        $telephoneNumbers = null;

        if (isset($data['faxNumbers'])) {
            $faxNumbers = $data['faxNumbers'];
            unset($data['faxNumbers']);
        }

        if (isset($data['mobilePhoneNumbers'])) {
            $mobilePhoneNumbers = $data['mobilePhoneNumbers'];
            unset($data['mobilePhoneNumbers']);
        }

        if (isset($data['telephoneNumbers'])) {
            $telephoneNumbers = $data['telephoneNumbers'];
            unset($data['telephoneNumbers']);
        }

        $object = $this->decorated->denormalize($data, $class, $format, $context);

        if (\is_array($faxNumbers)) {
            $faxNumberObjects = \array_map(function ($value) use ($format, $context) {
                return $this->serializer->denormalize($value, PhoneNumber::class, $format, $context);
            }, $faxNumbers);

            $this->updatePhoneNumbers($object, 'faxNumbers', $faxNumberObjects);
        }

        if (\is_array($mobilePhoneNumbers)) {
            $mobilePhoneNumberObjects = \array_map(function ($value) use ($format, $context) {
                return $this->serializer->denormalize($value, PhoneNumber::class, $format, $context);
            }, $mobilePhoneNumbers);

            $this->updatePhoneNumbers($object, 'mobilePhoneNumbers', $mobilePhoneNumberObjects);
        }

        if (\is_array($telephoneNumbers)) {
            $telephoneNumberObjects = \array_map(function ($value) use ($format, $context) {
                return $this->serializer->denormalize($value, PhoneNumber::class, $format, $context);
            }, $telephoneNumbers);

            $this->updatePhoneNumbers($object, 'telephoneNumbers', $telephoneNumberObjects);
        }

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

    /**
     * Updates phone numbers for a contact point.
     *
     * @param ContactPoint  $contactPoint
     * @param string        $property
     * @param PhoneNumber[] $values
     */
    private function updatePhoneNumbers(ContactPoint $contactPoint, $property, $values)
    {
        if (
            $contactPoint instanceof DoctrineProxy &&
            !$contactPoint->__isInitialized()
        ) {
            $contactPoint->__load();
        }

        $phoneNumberRoleCollection = $this->closurePropertyAccessor->getValue($contactPoint, $property);

        $existingMap = [];

        foreach ($phoneNumberRoleCollection as $phoneNumberRole) {
            $phoneNumberProto = $phoneNumberRole->getPhoneNumber();

            $formattedNumber = $this->phoneNumberUtil->format($phoneNumberProto, PhoneNumberFormat::E164);

            $existingMap[$formattedNumber] = $phoneNumberRole;
        }

        // set by index, reusing any existing values
        foreach ($values as $i => $phoneNumberProto) {
            $formattedNumber = $this->phoneNumberUtil->format($phoneNumberProto, PhoneNumberFormat::E164);

            $phoneNumberRole = $existingMap[$formattedNumber] ?? (new PhoneNumberRole())->setPhoneNumber($phoneNumberProto);

            $phoneNumberRoleCollection[$i] = $phoneNumberRole;
        }

        // unset any higher indexes if any
        $newLength = \count($values);
        foreach ($phoneNumberRoleCollection as $i => $value) {
            if ($i >= $newLength) {
                unset($phoneNumberRoleCollection[$i]);
            }
        }
    }
}
