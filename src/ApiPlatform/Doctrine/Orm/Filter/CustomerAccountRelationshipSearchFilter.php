<?php

declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\CustomerAccountRelationship;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class CustomerAccountRelationshipSearchFilter extends SearchFilter
{
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

        $description['customerName'] = [
            'property' => 'customerName',
            'type' => 'string',
            'required' => false,
            'swagger' => ['description' => 'Name of the Customer Example: Duba'],
        ];
        $description['customerId'] = [
            'property' => 'customerId',
            'type' => 'string',
            'required' => false,
            'swagger' => ['description' => 'Customer Account Number Example: x-xxxxxxxxx'],
        ];
        $description['type'] = [
            'property' => 'type',
            'type' => 'string',
            'required' => false,
            'swagger' => ['description' => 'type of relationship Example: IS_CONTACT_PERSON'],
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
            'customerName',
            'customerId',
        ], true)) {
            parent::filterProperty($property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName);

            return;
        }

        if (null === $value) {
            return;
        }

        $transactionType = $this->requestStack->getCurrentRequest()->query->get('type');
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $em = $this->managerRegistry->getManager();
        $expr = $queryBuilder->expr();
        $repository = $em->getRepository(CustomerAccountRelationship::class);
        $normalizedValues = $this->normalizeValues((array) $value);

        if ('customerName' === $property) {
            $customerNameTsQuery = $repository->getKeywordTsquery($normalizedValues, true);

            $tsqueryParam = $queryNameGenerator->generateParameterName('keywordTsquery');
            $customerAccountRelationshipAlias = $queryNameGenerator->generateJoinAlias('customerAccountRelationship');
            $customerCorporationAlias = $queryNameGenerator->generateJoinAlias('customerCorporation');
            $contactPersonCorporationAlias = $queryNameGenerator->generateJoinAlias('contactPersonCorporation');
            $customerDetailsAlias = $queryNameGenerator->generateJoinAlias('customerCorporation');
            $contactPersonDetailsAlias = $queryNameGenerator->generateJoinAlias('contactPersonCorporation');
            $customerAlias = $queryNameGenerator->generateJoinAlias('customer');
            $customerAccAlias = $queryNameGenerator->generateJoinAlias('customerAcc');
            $contactPersonAlias = $queryNameGenerator->generateJoinAlias('contactPerson');
            $contactPersonAccAlias = $queryNameGenerator->generateJoinAlias('contactPersonAcc');
            $personDetailsAlias = $queryNameGenerator->generateJoinAlias('personDetails');
            $corporationDetailsAlias = $queryNameGenerator->generateJoinAlias('corporationDetails');
            $contactPersoAccnDetailsAlias = $queryNameGenerator->generateJoinAlias('contactPersonAccDetails');
            $contactPersonCorporationDetailsAlias = $queryNameGenerator->generateJoinAlias('contactPersonCorporation');

            if ('IS_CONTACT_PERSON' === $transactionType) {
                $tsvectorSubquery = $em->createQueryBuilder()
                    ->select(\sprintf(
                        'tsvector_concat(coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'))',
                        $contactPersonAccAlias,
                        $contactPersonCorporationDetailsAlias,
                        $contactPersonDetailsAlias
                    ))
                    ->from(CustomerAccountRelationship::class, $customerAccountRelationshipAlias)
                    ->leftJoin(\sprintf('%s.from', $rootAlias), $contactPersonAccAlias)
                    ->leftJoin(\sprintf('%s.corporationDetails', $contactPersonAccAlias), $contactPersonCorporationDetailsAlias)
                    ->leftJoin(\sprintf('%s.personDetails', $contactPersonAccAlias), $contactPersonDetailsAlias)
                    ->andWhere($expr->andX(
                        $expr->eq($customerAccountRelationshipAlias, $rootAlias)
                    ))
                    ->getDQL();

                $queryBuilder
                    ->leftJoin(\sprintf('%s.from', $rootAlias), $contactPersonAlias)
                    ->leftJoin(\sprintf('%s.personDetails', $contactPersonAlias), $contactPersoAccnDetailsAlias)
                    ->leftJoin(\sprintf('%s.corporationDetails', $contactPersonAlias), $contactPersonCorporationAlias)
                    ->andWhere($expr->orX(
                        $expr->andX(
                            $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                                , $tsvectorSubquery, $tsqueryParam), $expr->literal(true))
                        )
                    ))
                    ->setParameter($tsqueryParam, $customerNameTsQuery);
            } elseif ('HAS_CONTACT_PERSON' === $transactionType) {
                $tsvectorSubquery = $em->createQueryBuilder()
                    ->select(\sprintf(
                        'tsvector_concat(coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'))',
                        $customerAccAlias,
                        $corporationDetailsAlias,
                        $personDetailsAlias
                    ))
                    ->from(CustomerAccountRelationship::class, $customerAccountRelationshipAlias)
                    ->leftJoin(\sprintf('%s.to', $rootAlias), $customerAccAlias)
                    ->leftJoin(\sprintf('%s.corporationDetails', $customerAccAlias), $corporationDetailsAlias)
                    ->leftJoin(\sprintf('%s.personDetails', $customerAccAlias), $personDetailsAlias)
                    ->andWhere($expr->andX(
                        $expr->eq($customerAccountRelationshipAlias, $rootAlias)
                    ))
                    ->getDQL();

                $queryBuilder
                    ->leftJoin(\sprintf('%s.to', $rootAlias), $customerAlias)
                    ->leftJoin(\sprintf('%s.personDetails', $customerAlias), $customerDetailsAlias)
                    ->leftJoin(\sprintf('%s.corporationDetails', $customerAlias), $customerCorporationAlias)
                    ->andWhere($expr->orX(
                        $expr->andX(
                            $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                                , $tsvectorSubquery, $tsqueryParam), $expr->literal(true))
                        )
                    ))
                    ->setParameter($tsqueryParam, $customerNameTsQuery);
            } else {
                $tsvectorSubquery = $em->createQueryBuilder()
                    ->select(\sprintf(
                        'tsvector_concat(coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'))',
                        $contactPersonAccAlias,
                        $contactPersonCorporationDetailsAlias,
                        $contactPersonDetailsAlias,
                        $customerAccAlias,
                        $corporationDetailsAlias,
                        $personDetailsAlias
                    ))
                    ->from(CustomerAccountRelationship::class, $customerAccountRelationshipAlias)
                    ->leftJoin(\sprintf('%s.from', $rootAlias), $contactPersonAccAlias)
                    ->leftJoin(\sprintf('%s.corporationDetails', $contactPersonAccAlias), $contactPersonCorporationDetailsAlias)
                    ->leftJoin(\sprintf('%s.personDetails', $contactPersonAccAlias), $contactPersonDetailsAlias)
                    ->leftJoin(\sprintf('%s.to', $rootAlias), $customerAccAlias)
                    ->leftJoin(\sprintf('%s.corporationDetails', $customerAccAlias), $corporationDetailsAlias)
                    ->leftJoin(\sprintf('%s.personDetails', $customerAccAlias), $personDetailsAlias)
                    ->andWhere($expr->andX(
                        $expr->eq($customerAccountRelationshipAlias, $rootAlias)
                    ))
                    ->getDQL();

                $queryBuilder
                    ->leftJoin(\sprintf('%s.to', $rootAlias), $customerAlias)
                    ->leftJoin(\sprintf('%s.personDetails', $customerAlias), $customerDetailsAlias)
                    ->leftJoin(\sprintf('%s.corporationDetails', $customerAlias), $customerCorporationAlias)
                    ->leftJoin(\sprintf('%s.from', $rootAlias), $contactPersonAlias)
                    ->leftJoin(\sprintf('%s.personDetails', $contactPersonAlias), $contactPersoAccnDetailsAlias)
                    ->leftJoin(\sprintf('%s.corporationDetails', $contactPersonAlias), $contactPersonCorporationAlias)
                    ->andWhere($expr->orX(
                        $expr->andX(
                            $expr->eq(\sprintf(<<<'SQL'
                            ts_match((%s), :%s)
SQL
                                , $tsvectorSubquery, $tsqueryParam), $expr->literal(true))
                        )
                    ))
                    ->setParameter($tsqueryParam, $customerNameTsQuery);
            }
        }

        if ('customerId' === $property) {
            if ('IS_CONTACT_PERSON' === $transactionType || null === $transactionType) {
                $queryBuilder->andWhere($queryBuilder->expr()->isNotNull(\sprintf('%s.from', $rootAlias)))
                    ->leftJoin(\sprintf('%s.from', $rootAlias), 'contactPerson')
                    ->andWhere($queryBuilder->expr()->eq('contactPerson.accountNumber', ':customerId'))
                    ->setParameter('customerId', $value);
            } elseif ('HAS_CONTACT_PERSON' === $transactionType || null === $transactionType) {
                $queryBuilder->andWhere($queryBuilder->expr()->isNotNull(\sprintf('%s.to', $rootAlias)))
                    ->leftJoin(\sprintf('%s.to', $rootAlias), 'customer')
                    ->andWhere($queryBuilder->expr()->eq('customer.accountNumber', ':customerId'))
                    ->setParameter('customerId', $value);
            } else {
                $queryBuilder->leftJoin(\sprintf('%s.from', $rootAlias), 'contactPerson')
                    ->leftJoin(\sprintf('%s.to', $rootAlias), 'customer')
                    ->andWhere($queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('customer.accountNumber', ':customerId'),
                    $queryBuilder->expr()->eq('contactPerson.accountNumber', ':customerId')
                ))
                    ->setParameter('customerId', $value);
            }
        }
    }
}
