<?php

declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\CustomerAccount;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class CustomerAccountSearchFilter extends SearchFilter
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

            $applicationRequestRepository = $em->getRepository(CustomerAccount::class);

            $keywordTsquery = $applicationRequestRepository->getKeywordTsquery($keywordValues, true);

            $customerAccountAlias = $queryNameGenerator->generateJoinAlias('customerAccount');
            $corporationDetailsAlias = $queryNameGenerator->generateJoinAlias('corporationDetails');
            $personDetailsAlias = $queryNameGenerator->generateJoinAlias('personDetails');

            $aggregateKeywordsTsvectorSubquery = $em->createQueryBuilder()
                ->select(\sprintf(
                    'tsvector_concat(o.keywords, coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'))',
                     $corporationDetailsAlias,
                     $personDetailsAlias
                ))
                ->from(CustomerAccount::class, $customerAccountAlias)
                ->leftJoin(\sprintf('%s.corporationDetails', $customerAccountAlias), $corporationDetailsAlias)
                ->leftJoin(\sprintf('%s.personDetails', $customerAccountAlias), $personDetailsAlias)
                ->andWhere($expr->andX(
                    $expr->eq($customerAccountAlias, 'o')
                ))
                ->getDQL();

            $keywordTsqueryParam = $queryNameGenerator->generateParameterName('keywordTsquery');

            $corpDetailsAlias = $queryNameGenerator->generateJoinAlias('corpDetails');
            $corporationIdAlias = $queryNameGenerator->generateJoinAlias('corporationDetailsId');
            $persDetailsAlias = $queryNameGenerator->generateJoinAlias('persDetails');
            $personIdAlias = $queryNameGenerator->generateJoinAlias('personDetailsId');
            $contractsAlias = $queryNameGenerator->generateJoinAlias('contracts');

            $queryBuilder
                ->leftJoin('o.corporationDetails', $corpDetailsAlias)
                ->leftJoin('o.personDetails', $persDetailsAlias)
                ->leftJoin('o.contracts', $contractsAlias)
                ->leftJoin(\sprintf('%s.identifiers', $persDetailsAlias), $personIdAlias)
                ->leftJoin(\sprintf('%s.identifiers', $corpDetailsAlias), $corporationIdAlias)
                ->andWhere($expr->orX(
                    $expr->andX(
                        $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                        , $aggregateKeywordsTsvectorSubquery, $keywordTsqueryParam), $expr->literal(true))
                    ),
                    $expr->eq(\sprintf('%s.value', $personIdAlias), $expr->literal($keywordValues[0])),
                    $expr->eq(\sprintf('%s.value', $corporationIdAlias), $expr->literal($keywordValues[0])),
                    $expr->eq(\sprintf('%s.contractNumber', $contractsAlias), $expr->literal(\strtoupper($keywordValues[0])))
                ))
                ->setParameter($keywordTsqueryParam, $keywordTsquery);
        }
    }
}
