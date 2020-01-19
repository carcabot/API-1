<?php

declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class JsonSearchFilter extends AbstractFilter
{
    /**
     * @param ManagerRegistry          $managerRegistry
     * @param RequestStack|null        $requestStack
     * @param LoggerInterface|null     $logger
     * @param array<string, null>|null $properties
     */
    public function __construct(ManagerRegistry $managerRegistry, $requestStack = null, LoggerInterface $logger = null, array $properties = null)
    {
        parent::__construct($managerRegistry, $requestStack, $logger, $properties);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->properties;
        if (null === $properties) {
            $properties = \array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $unused) {
            if (!$this->isPropertyMapped($property, $resourceClass) || !$this->isJsonField($property, $resourceClass)) {
                continue;
            }

            $description[$property] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
            ];

            $description[$property.'[]'] = [
                'property' => $property.'[]',
                'type' => 'string',
                'required' => false,
            ];
        }

        return $description;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (
            !(\is_string($value) ||
            \is_array($value)) ||
            !$this->isPropertyEnabled($property) ||
            !$this->isPropertyMapped($property, $resourceClass) ||
            !$this->isJsonField($property, $resourceClass)
        ) {
            return;
        }

        $alias = 'o';
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            list($alias, $field) = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator);
        }

        $valueParameter = $queryNameGenerator->generateParameterName($field);

        $expr = $queryBuilder->expr();

        if (\is_array($value)) {
            $orX = $expr->orX();

            foreach ($value as $key => $param) {
                $valueParam = \sprintf('%s_%s', $valueParameter, $key);
                $orX->add($expr->andX($expr->eq(\sprintf(<<<'SQL'
                    jsonb_contains(CAST(%s.%s AS jsonb), :%s)
SQL
                , $alias, $field, $valueParam), $expr->literal(true))));

                $queryBuilder->setParameter($valueParam, \json_encode($param));
            }

            $queryBuilder->andWhere($orX);
        } else {
            $queryBuilder
                ->andWhere($expr->andX(
                    $expr->eq(\sprintf(<<<'SQL'
                        jsonb_contains(CAST(%s.%s AS jsonb), :%s)
SQL
                    , $alias, $field, $valueParameter),
                    $expr->literal(true))
                ))
                ->setParameter($valueParameter, \json_encode($value));
        }
    }

    /**
     * Determines whether the given property refers to a JSON field.
     *
     * @param string $property
     * @param string $resourceClass
     *
     * @return bool
     */
    protected function isJsonField(string $property, string $resourceClass): bool
    {
        $propertyParts = $this->splitPropertyParts($property);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        return DBALType::JSON === $metadata->getTypeOfField($propertyParts['field']);
    }
}
