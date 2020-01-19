<?php

declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DiscriminatorColumnSearchFilter extends SearchFilter
{
    const TYPE_PROPERTY_NAME = 'type';

    /**
     * @param ManagerRegistry                 $managerRegistry
     * @param RequestStack                    $requestStack
     * @param IriConverterInterface           $iriConverter
     * @param PropertyAccessorInterface|null  $propertyAccessor
     * @param LoggerInterface|null            $logger
     * @param array<string, string|null>|null $properties
     */
    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor = null, LoggerInterface $logger = null, array $properties = null)
    {
        parent::__construct($managerRegistry, $requestStack, $iriConverter, $propertyAccessor, $logger, $properties);
    }

    /**
     * @param string $resourceClass
     *
     * @return array
     */
    public function getDescription(string $resourceClass): array
    {
        $description = parent::getDescription($resourceClass);

        $description[self::TYPE_PROPERTY_NAME] = [
            'property' => self::TYPE_PROPERTY_NAME,
            'type' => 'string',
            'required' => false,
            'strategy' => self::STRATEGY_EXACT,
            'swagger' => ['description' => 'The Class Type E.g ApplicationRequest'],
        ];

        $description[self::TYPE_PROPERTY_NAME.'[]'] = [
            'property' => self::TYPE_PROPERTY_NAME,
            'type' => 'array',
            'required' => false,
            'strategy' => self::STRATEGY_EXACT,
            'swagger' => ['description' => 'The Class Type E.g ApplicationRequest'],
        ];

        return $description;
    }

    /**
     * Passes a property through the filter.
     *
     * @param string                      $property
     * @param mixed                       $value
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (!\in_array($property, [
            self::TYPE_PROPERTY_NAME,
        ], true)) {
            parent::filterProperty($property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName);

            return;
        }

        if (null === $value) {
            return;
        }

        $expr = $queryBuilder->expr();
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $conditions = [];
        $orExpressions = $expr->orX();

        if (\is_array($value)) {
            foreach ($value as $instance) {
                $conditions[] = $expr->isInstanceOf($rootAlias, \sprintf('App\Entity\%s', $instance));
            }
        } else {
            $conditions[] = $expr->isInstanceOf($rootAlias, \sprintf('App\Entity\%s', $value));
        }

        foreach ($conditions as $condition) {
            $orExpressions->add($condition);
        }

        $queryBuilder->andWhere($orExpressions);
    }
}
