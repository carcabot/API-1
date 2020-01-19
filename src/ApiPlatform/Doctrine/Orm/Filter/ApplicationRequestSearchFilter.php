<?php

declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\ApplicationRequest;
use App\Enum\Source;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ApplicationRequestSearchFilter extends SearchFilter
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
            'sourceChannel',
            'salesRep',
            'agency',
        ], true)) {
            parent::filterProperty($property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName);

            return;
        }

        if (null === $value) {
            return;
        }

        $em = $this->managerRegistry->getManager();
        $expr = $queryBuilder->expr();

        $applicationRequestRepository = $em->getRepository(ApplicationRequest::class);

        $tsqueryParam = $queryNameGenerator->generateParameterName('keywordTsquery');
        $applicationRequestAlias = $queryNameGenerator->generateJoinAlias('order');
        $corporationDetailsAlias = $queryNameGenerator->generateJoinAlias('corporationDetails');
        $customerAlias = $queryNameGenerator->generateJoinAlias('customer');
        $personDetailsAlias = $queryNameGenerator->generateJoinAlias('personDetails');

        if ('sourceChannel' === $property) {
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $values = $this->normalizeValues((array) $value);
            $expr = $queryBuilder->expr();

            if (\in_array(Source::SELF_SERVICE_PORTAL, $values, true)) {
                $queryBuilder->andWhere($expr->notLike(\sprintf('%s.sourceUrl', $rootAlias), $expr->literal('%channel=%')));
            }

            if (\in_array(Source::TELEPHONE, $values, true)) {
                $queryBuilder->andWhere($expr->like(\sprintf('%s.sourceUrl', $rootAlias), $expr->literal('%channel=sms%')));
            }

            if (\in_array(Source::EMAIL, $values, true)) {
                $queryBuilder->andWhere($expr->like(\sprintf('%s.sourceUrl', $rootAlias), $expr->literal('%channel=email%')));
            }
        } elseif ('salesRep' === $property) {
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $value = $this->normalizeValues((array) $value);
            $expr = $queryBuilder->expr();
            $salesRepTsQuery = $applicationRequestRepository->getKeywordTsquery($value, true);

            $salesRepAlias = $queryNameGenerator->generateJoinAlias('salesRep');
            $salesRepIdAlias = $queryNameGenerator->generateJoinAlias('salesRepId');
            $salesRepCustomerAlias = $queryNameGenerator->generateJoinAlias('salesRepAccount');
            $salesRepDetailsAlias = $queryNameGenerator->generateJoinAlias('salesRepDetails');

            $tsvectorSubquery = $em->createQueryBuilder()
                ->select(\sprintf(
                    'tsvector_concat(o.keywords, coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'))',
                    $customerAlias,
                    $personDetailsAlias
                ))
                ->from(ApplicationRequest::class, $applicationRequestAlias)
                ->leftJoin('o.creator', $salesRepAlias)
                ->leftJoin(\sprintf('%s.customerAccount', $salesRepAlias), $customerAlias)
                ->leftJoin(\sprintf('%s.personDetails', $customerAlias), $personDetailsAlias)
                ->andWhere($expr->andX(
                    $expr->eq($applicationRequestAlias, 'o')
                ))
                ->getDQL();

            $queryBuilder->leftJoin(\sprintf('%s.creator', $rootAlias), $salesRepIdAlias)
                ->leftJoin(\sprintf('%s.customerAccount', $salesRepIdAlias), $salesRepCustomerAlias)
                ->leftJoin(\sprintf('%s.personDetails', $salesRepCustomerAlias), $salesRepDetailsAlias)
                ->andWhere($expr->orX($expr->andX(
                    $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                        , $tsvectorSubquery, $tsqueryParam), $expr->literal(true))
                ),
                    $expr->andX(
                        $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                            , 'o.salesRepName', $tsqueryParam), $expr->literal(true))
                    )))
                ->setParameter($tsqueryParam, $salesRepTsQuery);
        } elseif ('agency' === $property) {
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $value = $this->normalizeValues((array) $value);
            $expr = $queryBuilder->expr();
            $agencyTsQuery = $applicationRequestRepository->getKeywordTsquery($value, true);

            $agencyIdAlias = $queryNameGenerator->generateJoinAlias('agencyId');
            $agencyDetailsAlias = $queryNameGenerator->generateJoinAlias('agencyDetails');
            $agencyCorporationAlias = $queryNameGenerator->generateJoinAlias('agencyCorporation');

            $tsvectorSubquery = $em->createQueryBuilder()
                ->select(\sprintf(
                    'tsvector_concat(o.keywords, coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'))',
                    $customerAlias,
                    $corporationDetailsAlias,
                    $personDetailsAlias
                ))
                ->from(ApplicationRequest::class, $applicationRequestAlias)
                ->leftJoin('o.acquiredFrom', $customerAlias)
                ->leftJoin(\sprintf('%s.corporationDetails', $customerAlias), $corporationDetailsAlias)
                ->leftJoin(\sprintf('%s.personDetails', $customerAlias), $personDetailsAlias)
                ->andWhere($expr->andX(
                    $expr->eq($applicationRequestAlias, 'o')
                ))
                ->getDQL();

            $queryBuilder->leftJoin(\sprintf('%s.acquiredFrom', $rootAlias), $agencyIdAlias)
                ->leftJoin(\sprintf('%s.personDetails', $agencyIdAlias), $agencyDetailsAlias)
                ->leftJoin(\sprintf('%s.corporationDetails', $agencyIdAlias), $agencyCorporationAlias)
                ->andWhere($expr->orX(
                    $expr->andX(
                        $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                            , $tsvectorSubquery, $tsqueryParam), $expr->literal(true))
                    )
                ))
                ->setParameter($tsqueryParam, $agencyTsQuery);
        } else {
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

                $keywordTsquery = $applicationRequestRepository->getKeywordTsquery($keywordValues, true);

                $contactPersonAlias = $queryNameGenerator->generateJoinAlias('contactPerson');
                $contactPersonDetailsAlias = $queryNameGenerator->generateJoinAlias('personDetails');

                $aggregateKeywordsTsvectorSubquery = $em->createQueryBuilder()
                    ->select(\sprintf(
                        'tsvector_concat(o.keywords, coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'))',
                        $contactPersonAlias,
                        $contactPersonDetailsAlias,
                        $corporationDetailsAlias,
                        $customerAlias,
                        $personDetailsAlias
                    ))
                    ->from(ApplicationRequest::class, $applicationRequestAlias)
                    ->leftJoin(\sprintf('%s.contactPerson', $applicationRequestAlias), $contactPersonAlias)
                    ->leftJoin(\sprintf('%s.personDetails', $contactPersonAlias), $contactPersonDetailsAlias)
                    ->leftJoin(\sprintf('%s.corporationDetails', $applicationRequestAlias), $corporationDetailsAlias)
                    ->leftJoin(\sprintf('%s.customer', $applicationRequestAlias), $customerAlias)
                    ->leftJoin(\sprintf('%s.personDetails', $applicationRequestAlias), $personDetailsAlias)
                    ->andWhere($expr->andX(
                        $expr->eq($applicationRequestAlias, 'o')
                    ))
                    ->getDQL();

                $keywordTsqueryParam = $queryNameGenerator->generateParameterName('keywordTsquery');

                $corpDetailsAlias = $queryNameGenerator->generateJoinAlias('corpDetails');
                $corporationIdAlias = $queryNameGenerator->generateJoinAlias('corporationDetailsId');
                $persDetailsAlias = $queryNameGenerator->generateJoinAlias('persDetails');
                $personIdAlias = $queryNameGenerator->generateJoinAlias('personDetailsId');

                $queryBuilder
                    ->leftJoin('o.corporationDetails', $corpDetailsAlias)
                    ->leftJoin('o.personDetails', $persDetailsAlias)
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
                        $expr->eq(\sprintf('%s.value', $corporationIdAlias), $expr->literal($keywordValues[0]))
                    ))
                    ->setParameter($keywordTsqueryParam, $keywordTsquery);
            }
        }
    }
}
