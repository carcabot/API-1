<?php

declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\EarnContractCreditsAction;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class EarnContractCreditsActionSearchFilter extends SearchFilter
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
     * @param string $resourceClass
     *
     * @return array
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
        $repository = $em->getRepository(EarnContractCreditsAction::class);
        $customerNameTsQuery = $repository->getKeywordTsquery($value, true);

        $tsqueryParam = $queryNameGenerator->generateParameterName('keywordTsquery');
        $creditsActionAlias = $queryNameGenerator->generateJoinAlias('creditsAction');
        $corporationDetailsAlias = $queryNameGenerator->generateJoinAlias('corporationDetails');
        $customerAlias = $queryNameGenerator->generateJoinAlias('customer');
        $contractCustomerAlias = $queryNameGenerator->generateJoinAlias('contractCustomerAlias');
        $personDetailsAlias = $queryNameGenerator->generateJoinAlias('personDetails');
        $customerDetailsAlias = $queryNameGenerator->generateJoinAlias('customerDetails');
        $corporationAlias = $queryNameGenerator->generateJoinAlias('corporationAlias');
        $contractAlias = $queryNameGenerator->generateJoinAlias('contractAlias');
        $actionContractAlias = $queryNameGenerator->generateJoinAlias('actionContractAlias');

        $tsvectorSubquery = $em->createQueryBuilder()
            ->select(\sprintf(
                'tsvector_concat(coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'), coalesce(%s.keywords, \'\'))',
                $customerAlias,
                $corporationDetailsAlias,
                $personDetailsAlias
            ))
            ->from(EarnContractCreditsAction::class, $creditsActionAlias)
            ->leftJoin(\sprintf('%s.object', $rootAlias), $actionContractAlias)
            ->leftJoin(\sprintf('%s.customer', $actionContractAlias), $customerAlias)
            ->leftJoin(\sprintf('%s.corporationDetails', $customerAlias), $corporationDetailsAlias)
            ->leftJoin(\sprintf('%s.personDetails', $customerAlias), $personDetailsAlias)
            ->andWhere($expr->andX(
                $expr->eq($creditsActionAlias, $rootAlias)
            ))
            ->getDQL();

        $queryBuilder->leftJoin(\sprintf('%s.object', $rootAlias), $contractAlias)
            ->leftJoin(\sprintf('%s.customer', $contractAlias), $contractCustomerAlias)
            ->leftJoin(\sprintf('%s.corporationDetails', $contractCustomerAlias), $corporationAlias)
            ->leftJoin(\sprintf('%s.personDetails', $contractCustomerAlias), $customerDetailsAlias)
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
