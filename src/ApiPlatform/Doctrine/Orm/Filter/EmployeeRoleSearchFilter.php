<?php

declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\EmployeeRole;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class EmployeeRoleSearchFilter extends SearchFilter
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

            $employeeRoleRepository = $em->getRepository(EmployeeRole::class);

            $keywordTsquery = $employeeRoleRepository->getKeywordTsquery($keywordValues, true);

            $employeeRoleAlias = $queryNameGenerator->generateJoinAlias('employee');
            $customerAccountAlias = $queryNameGenerator->generateJoinAlias('customerAccount');
            $personDetailsAlias = $queryNameGenerator->generateJoinAlias('personDetails');

            $aggregateKeywordsTsvectorSubquery = $em->createQueryBuilder()
                ->select(\sprintf(
                    'tsvector_concat(coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'))',
                     $customerAccountAlias,
                     $personDetailsAlias
                ))
                ->from(EmployeeRole::class, $employeeRoleAlias)
                ->leftJoin('o.employee', $customerAccountAlias)
                ->leftJoin(\sprintf('%s.personDetails', $customerAccountAlias), $personDetailsAlias)
                ->andWhere($expr->andX(
                    $expr->eq($employeeRoleAlias, 'o')
                ))
                ->getDQL();

            $keywordTsqueryParam = $queryNameGenerator->generateParameterName('keywordTsquery');

            $employeeAccountAlias = $queryNameGenerator->generateJoinAlias('employeeAccount');
            $employeeUserAlias = $queryNameGenerator->generateJoinAlias('employeeUser');
            $personDetailsAlias = $queryNameGenerator->generateJoinAlias('persDetails');
            $personContactPointAlias = $queryNameGenerator->generateJoinAlias('personDetailsId');

            $queryBuilder
                ->leftJoin('o.employee', $employeeAccountAlias)
                ->leftJoin(\sprintf('%s.user', $employeeAccountAlias), $employeeUserAlias)
                ->leftJoin(\sprintf('%s.personDetails', $employeeAccountAlias), $personDetailsAlias)
                ->leftJoin(\sprintf('%s.contactPoints', $personDetailsAlias), $personContactPointAlias)
                ->andWhere($expr->orX(
                    $expr->andX(
                        $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                        , $aggregateKeywordsTsvectorSubquery, $keywordTsqueryParam), $expr->literal(true))
                    ),
                    $expr->eq(\sprintf(<<<'SQL'
                        jsonb_contains(CAST(lower(CAST(%s.%s AS text)) AS jsonb), :%s)
SQL
                        , $personContactPointAlias, 'emails', 'personContactPointEmail'),
                        $expr->literal(true)),
                    $expr->eq(\sprintf('%s.accountNumber', $employeeAccountAlias), $expr->literal($keywordValues[0])),
                    $expr->eq(\sprintf('%s.email', $employeeUserAlias), $expr->literal($keywordValues[0])),
                    $expr->eq(\sprintf('%s.username', $employeeUserAlias), $expr->literal($keywordValues[0]))
                ))
                ->setParameter($keywordTsqueryParam, $keywordTsquery)
                ->setParameter('personContactPointEmail', \json_encode(\strtolower($keywordValues[0])));
        }
    }
}
