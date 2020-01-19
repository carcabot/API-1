<?php

declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\User;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class UserSearchFilter extends SearchFilter
{
    const CUSTOMER_NAME_PROPERTY_NAME = 'customerName';
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

        $description[self::CUSTOMER_NAME_PROPERTY_NAME] = [
            'property' => self::CUSTOMER_NAME_PROPERTY_NAME,
            'type' => 'string',
            'required' => false,
            'swagger' => ['description' => 'Name of the Customer Example: Duba'],
        ];

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
            self::CUSTOMER_NAME_PROPERTY_NAME,
            self::KEYWORDS_PROPERTY_NAME,
        ], true)) {
            parent::filterProperty($property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName);

            return;
        }

        if (null === $value) {
            return;
        }

        $em = $this->managerRegistry->getManager();
        $expr = $queryBuilder->expr();
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $value = $this->normalizeValues((array) $value);

        $userRepository = $em->getRepository(User::class);
        $customerNameTsQuery = $userRepository->getKeywordTsquery($value, true);

        $tsqueryParam = $queryNameGenerator->generateParameterName('keywordTsquery');
        $userAlias = $queryNameGenerator->generateJoinAlias('user');
        $corporationDetailsAlias = $queryNameGenerator->generateJoinAlias('corporationDetails');
        $customerAlias = $queryNameGenerator->generateJoinAlias('customer');
        $userCustomerAlias = $queryNameGenerator->generateJoinAlias('userCustomer');
        $personDetailsAlias = $queryNameGenerator->generateJoinAlias('personDetails');
        $customerDetailsAlias = $queryNameGenerator->generateJoinAlias('customerDetails');
        $corporationAlias = $queryNameGenerator->generateJoinAlias('corporationAlias');

        $tsvectorSubquery = $em->createQueryBuilder()
            ->select(\sprintf(
                'tsvector_concat(coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'))',
                $customerAlias,
                $corporationDetailsAlias,
                $personDetailsAlias
            ))
            ->from(User::class, $userAlias)
            ->leftJoin(\sprintf('%s.customerAccount', $rootAlias), $customerAlias)
            ->leftJoin(\sprintf('%s.corporationDetails', $customerAlias), $corporationDetailsAlias)
            ->leftJoin(\sprintf('%s.personDetails', $customerAlias), $personDetailsAlias)
            ->andWhere($expr->andX(
                $expr->eq($userAlias, $rootAlias)
            ))
            ->getDQL();

        $filterOrExprs = $expr->orX();

        $filterOrExprs->add($expr->andX(
            $expr->eq(\sprintf(<<<'SQL'
                    ts_match((%s), :%s)
SQL
                , $tsvectorSubquery, $tsqueryParam), $expr->literal(true))
        ));

        if (self::KEYWORDS_PROPERTY_NAME === $property) {
            $filterOrExprs->add($expr->eq(\sprintf('%s.email', $rootAlias), $expr->literal($value[0])));
            $filterOrExprs->add($expr->eq(\sprintf('%s.username', $rootAlias), $expr->literal($value[0])));
        }

        $queryBuilder
            ->leftJoin(\sprintf('%s.customerAccount', $rootAlias), $userCustomerAlias)
            ->leftJoin(\sprintf('%s.personDetails', $userCustomerAlias), $customerDetailsAlias)
            ->leftJoin(\sprintf('%s.corporationDetails', $userCustomerAlias), $corporationAlias)
            ->andWhere($filterOrExprs)
            ->setParameter($tsqueryParam, $customerNameTsQuery);
    }
}
