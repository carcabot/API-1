<?php

declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Lead;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class LeadSearchFilter extends SearchFilter
{
    const KEYWORDS_PROPERTY_NAME = 'keywords';

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
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = parent::getDescription($resourceClass);

        $description[self::KEYWORDS_PROPERTY_NAME] = [
            'property' => self::KEYWORDS_PROPERTY_NAME,
            'type' => 'string',
            'required' => false,
        ];

        return $description;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if (!\in_array($property, [
            self::KEYWORDS_PROPERTY_NAME,
        ], true)) {
            parent::filterProperty($property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName);

            return;
        }

        if (null === $value) {
            return;
        }

        if (self::KEYWORDS_PROPERTY_NAME === $property) {
            $keywordValues = $this->normalizeValues((array) $value);

            if (empty($keywordValues)) {
                return;
            }

            $em = $this->managerRegistry->getManager();
            $expr = $queryBuilder->expr();

            $applicationRequestRepository = $em->getRepository(Lead::class);

            $keywordTsquery = $applicationRequestRepository->getKeywordTsquery($keywordValues, true);

            $leadAlias = $queryNameGenerator->generateJoinAlias('lead');
            $corporationDetailsAlias = $queryNameGenerator->generateJoinAlias('corporationDetails');
            $personDetailsAlias = $queryNameGenerator->generateJoinAlias('personDetails');

            $aggregateKeywordsTsvectorSubquery = $em->createQueryBuilder()
                ->select(\sprintf(
                    'tsvector_concat(o.keywords, coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'))',
                     $corporationDetailsAlias,
                     $personDetailsAlias
                ))
                ->from(Lead::class, $leadAlias)
                ->leftJoin(\sprintf('%s.corporationDetails', $leadAlias), $corporationDetailsAlias)
                ->leftJoin(\sprintf('%s.personDetails', $leadAlias), $personDetailsAlias)
                ->andWhere($expr->andX(
                    $expr->eq($leadAlias, 'o')
                ))
                ->getDQL();

            $keywordTsqueryParam = $queryNameGenerator->generateParameterName('keywordTsquery');

            $queryBuilder
                ->andWhere($expr->andX(
                    $expr->eq(\sprintf(<<<'SQL'
                        ts_match((%s), :%s)
SQL
                    , $aggregateKeywordsTsvectorSubquery, $keywordTsqueryParam), $expr->literal(true))
                ))
                ->setParameter($keywordTsqueryParam, $keywordTsquery);
        }
    }
}
