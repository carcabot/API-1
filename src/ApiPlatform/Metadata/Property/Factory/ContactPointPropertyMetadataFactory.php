<?php

declare(strict_types=1);

namespace App\ApiPlatform\Metadata\Property\Factory;

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use App\Entity\ContactPoint;
use libphonenumber\PhoneNumber;
use Symfony\Component\PropertyInfo\Type;

class ContactPointPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    /**
     * @var PropertyMetadataFactoryInterface
     */
    private $decorated;

    /**
     * @param PropertyMetadataFactoryInterface $decorated
     */
    public function __construct(PropertyMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        if (ContactPoint::class !== $resourceClass) {
            return $propertyMetadata;
        }

        $phoneNumberCollectionType = new Type(
            Type::BUILTIN_TYPE_ARRAY,
            false,
            null,
            true,
            new Type(Type::BUILTIN_TYPE_INT),
            new Type(Type::BUILTIN_TYPE_OBJECT, false, PhoneNumber::class)
        );

        switch ($property) {
            case 'faxNumbers':
                return $propertyMetadata
                    ->withType($phoneNumberCollectionType);

            case 'mobilePhoneNumbers':
                return $propertyMetadata
                    ->withType($phoneNumberCollectionType);

            case 'telephoneNumbers':
                return $propertyMetadata
                    ->withType($phoneNumberCollectionType);

            default:
                return $propertyMetadata;
        }
    }
}
