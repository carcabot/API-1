<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CustomerAccount;
use App\Entity\ImportListingMapping;
use App\Entity\Lead;
use App\Enum\CampaignCategory;
use App\Enum\ImportListingTargetFields;
use App\Enum\PostalAddressType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class CampaignFilterController
{
    const CUSTOMER_ACCOUNT = 'customerAccount';
    const IMPORT_LISTING = 'importListing';
    const LEADS = 'lead';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     */
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/generate/import_listing", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function saveListing(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);
        $dbConn = $this->entityManager->getConnection();

        $importedData = $params['importedData'];
        $relationships = $params['relationships'];
        $targetClass = $params['targetClass'];

        $dbConn->executeUpdate($dbConn->getDatabasePlatform()->getTruncateTableSQL('import_listing_mappings'));

        $primaryKeysQuery = 'PRIMARY KEY (';
        $dropSqlQuery = 'DROP TABLE IF EXISTS import_listing_data';
        $createSqlQuery = 'CREATE TABLE import_listing_data (';
        $insertQuery = 'INSERT INTO import_listing_data VALUES ';

        if (\count($relationships) > 1) {
            for ($idx = 0; $idx < \count($relationships) - 1; ++$idx) {
                $mapping = new ImportListingMapping();
                $mapping->setSource($relationships[$idx]['source']);
                $mapping->setTarget(new ImportListingTargetFields($relationships[$idx]['target']));
                $mapping->setTargetClass($targetClass);

                $this->entityManager->persist($mapping);

                $passedString = \strtolower(\str_replace(' ', '', $relationships[$idx]['source']));
                $cleanedString = \preg_replace('/[^A-Za-z0-9\-]/', '_', $passedString);

                $primaryKeysQuery .= $cleanedString.', ';
            }
            $mapping = new ImportListingMapping();
            $mapping->setSource($relationships[\count($relationships) - 1]['source']);
            $mapping->setTarget(new ImportListingTargetFields($relationships[\count($relationships) - 1]['target']));
            $mapping->setTargetClass($targetClass);

            $this->entityManager->persist($mapping);

            $passedString = \strtolower(\str_replace(' ', '', $relationships[\count($relationships) - 1]['source']));
            $cleanedString = \preg_replace('/[^A-Za-z0-9\-]/', '_', $passedString);

            $primaryKeysQuery .= $cleanedString;
        } else {
            $mapping = new ImportListingMapping();
            $mapping->setSource($relationships[0]['source']);
            $mapping->setTarget(new ImportListingTargetFields($relationships[0]['target']));
            $mapping->setTargetClass($targetClass);

            $this->entityManager->persist($mapping);

            $passedString = \strtolower(\str_replace(' ', '', $relationships[\count($relationships) - 1]['source']));
            $cleanedString = \preg_replace('/[^A-Za-z0-9\-]/', '_', $passedString);

            $primaryKeysQuery .= $cleanedString;
        }

        $this->entityManager->flush();

        $primaryKeysQuery .= ')';

        $dbConn->query($dropSqlQuery);

        $columns = \array_keys($importedData[0]);

        for ($idx = 0; $idx < \count($columns); ++$idx) {
            $passedString = \strtolower(\str_replace(' ', '', $columns[$idx]));
            $cleanedString = \preg_replace('/[^A-Za-z0-9\-]/', '_', $passedString);
            $createSqlQuery .= $cleanedString.' VARCHAR(100),';
        }

        $createSqlQuery .= $primaryKeysQuery.')';

        // $createSqlQuery .= \strtolower(\str_replace(' ', '_', $columns[\count($columns) - 1])).' VARCHAR(100))';

        $dbConn->query($createSqlQuery);

        for ($idx = 0; $idx < \count($importedData) - 1; ++$idx) {
            $values = \array_map(function ($value) use ($dbConn) { return $dbConn->quote($value); }, \array_values($importedData[$idx]));

            $result = \implode(', ', $values);
            $insertQuery .= " ({$result}),";
        }
        $lastQuery = \implode(', ', \array_map(function ($value) use ($dbConn) { return $dbConn->quote($value); }, \array_values($importedData[\count($importedData) - 1])));
        $insertQuery .= " ({$lastQuery})";

        $dbConn->query($insertQuery);

        return new EmptyResponse();
    }

    /**
     * @Route("/generate/subsets", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function generateAction(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);
        $filters = $params['sets'];
        $type = $params['type'];
        $mappings = $params['relationships'] ?? null;
        $targetTable = $params['targetTable'] ?? null;

        $combinations = $this->getAllCombinations($filters);

        $subsets = [];
        foreach ($combinations as $combination) {
            $count = $this->getCombinationCount($combination, $type, $mappings, $targetTable);

            if ($count > 0) {
                $subsetFilter = $combination[0];
                unset($combination[0]);

                foreach ($combination as $field) {
                    $subsetFilter .= '__'.$field;
                }

                $subsets[$subsetFilter] = $count;
            }
        }

        return new JsonResponse($subsets, 200, ['Cache-Control' => 'public, s-maxage=1, must-revalidate']);
    }

    private function getAllCombinations(array $params)
    {
        // initialize by adding the empty set
        $results = [[]];

        foreach ($params as $element) {
            foreach ($results as $combination) {
                \array_push($results, \array_merge([$element], $combination));
            }
        }
        // remove empty set at the beginning
        unset($results[0]);
        \array_values($results);

        return $results;
    }

    // customerAccounts.status.active;

    private function getCombinationCount(array $filterParams, string $type, $mappings = null, $targetTable = null)
    {
        $classes = [];
        $conditions = [];
        $embedded = [];
        $values = [];
        $count = 0;
        $customerType = null;

        foreach ($filterParams as $key => $filterParam) {
            $fields = \explode('.', $filterParam);
            if ('type' === $fields[1] && !isset($customerType)) {
                $customerType = $fields[2];
            }
            $values[$key] = \array_pop($fields);
            $classes[] = $fields[0];
            unset($fields[0]);
            $conditions[$key] = \array_values($fields);
        }
        $classes = \array_flip($classes);
        $classes = \array_flip($classes);
        $classes = \array_values($classes);

        if (\count($classes) > 1 && !\in_array(self::IMPORT_LISTING, $classes, true)) {
            throw new \Exception('Only one base class is supported');
        }

        if (!\in_array(self::IMPORT_LISTING, $classes, true)) {
            switch ($classes[0]) {
                case 'lead':
                    $class = Lead::class;
                    $embedded = [
                        'averageConsumption',
                        'purchaseTimeFrame',
                    ];
                    break;
                case 'customerAccount':
                    $class = CustomerAccount::class;
                    break;
                default:
                    throw new \Exception('Unsupported base class: '.$classes[0]);
            }

            $alias = $classes[0];
            $qb = $this->entityManager->createQueryBuilder()->select($alias)->from($class, $alias);
            $expr = $qb->expr();
            $andEqExpr = $expr->andX();
            $paramCounter = 1;

            foreach ($conditions as $key => $condition) {
                $conditionLength = \count($condition);

                if ($conditionLength > 1) {
                    $columnString = '';
                    $previousAlias = null;

                    foreach ($condition as $idx => $column) {
                        // last one so time to finish the query
                        if ($idx === ($conditionLength - 1)) {
                            if (null !== $previousAlias) {
                                $columnString = $previousAlias;
                            }

                            if ('[]' === \substr($column, \strlen($column) - 2, 2)) {
                                $column = \substr($column, 0, -2);
                                $valueParam = \sprintf('%s_%s', $columnString, $column);
                                ++$paramCounter;

                                $andEqExpr->add($expr->andX($expr->eq(\sprintf(<<<'SQL'
                                jsonb_contains(CAST(%s.%s AS jsonb), :%s)
SQL
                                    , $columnString, $column, $valueParam), $expr->literal(true))));

                                $qb->setParameter($valueParam, \json_encode($values[$key]));
                            } else {
                                $andEqExpr->add($expr->eq(\sprintf('%s.%s', $columnString, $column), $expr->literal($values[$key])));
                            }
                            break;
                        }
                        // joins or embedded stuff
                        if (\in_array($column, $embedded, true)) {
                            if (null !== $previousAlias) {
                                $columnString = \sprintf('%s.%s', $columnString, $previousAlias);
                            } else {
                                $columnString = \sprintf('%s.%s', $columnString, $column);
                            }
                            $previousAlias = null;
                        } else {
                            if (null !== $previousAlias) {
                                $joinAlias = \sprintf('%s_%s', $previousAlias, $column);
                                $joinCondition = \sprintf('%s.%s', $previousAlias, $column);
                            } else {
                                $joinAlias = \sprintf('%s_%s', $alias, $column);
                                $joinCondition = \sprintf('%s.%s', $alias, $column);
                            }

                            if (!\in_array($joinAlias, $qb->getAllAliases(), true)) {
                                $qb->leftJoin($joinCondition, $joinAlias);
                            }
                            $previousAlias = $joinAlias;
                        }
                    }
                } else {
                    $column = $condition[0];
                    if ('[]' === \substr($column, \strlen($column) - 2, 2)) {
                        $column = \substr($column, 0, -2);
                        $valueParam = \sprintf('%s_%s', $column, $paramCounter);
                        ++$paramCounter;

                        $andEqExpr->add($expr->andX($expr->eq(\sprintf(<<<'SQL'
                        jsonb_contains(CAST(%s.%s AS jsonb), :%s)
SQL
                            , $alias, $column, $valueParam), $expr->literal(true))));

                        $qb->setParameter($valueParam, \json_encode($values[$key]));
                    } else {
                        $andEqExpr->add($expr->eq(\sprintf('%s.%s', $alias, $column), $expr->literal($values[$key])));
                    }
                }
            }

            if (CampaignCategory::EMAIL === $type) {
                $emailExpr = $expr->orX();

                $personJoinCondition = \sprintf('%s.%s', $alias, 'personDetails');
                $personJoinAlias = \sprintf('%s_%s', $alias, 'personDetails');
                if (!\in_array($personJoinAlias, $qb->getAllAliases(), true)) {
                    $qb->leftJoin($personJoinCondition, $personJoinAlias);
                }

                $personContactPointJoinCondition = \sprintf('%s.%s', $personJoinAlias, 'contactPoints');
                $personContactPointJoinAlias = \sprintf('%s_%s', $personJoinAlias, 'contactPoints');
                if (!\in_array($personContactPointJoinAlias, $qb->getAllAliases(), true)) {
                    $qb->leftJoin($personContactPointJoinCondition, $personContactPointJoinAlias);
                }

                $emailExpr->add($expr->andX($expr->gt(\sprintf(<<<'SQL'
                jsonb_array_length(CAST(%s.%s AS jsonb))
SQL
                    , $personContactPointJoinAlias, 'emails'), $expr->literal(0))));

                $corporationJoinCondition = \sprintf('%s.%s', $alias, 'corporationDetails');
                $corporationJoinAlias = \sprintf('%s_%s', $alias, 'corporationDetails');
                if (!\in_array($corporationJoinAlias, $qb->getAllAliases(), true)) {
                    $qb->leftJoin($corporationJoinCondition, $corporationJoinAlias);
                }

                $corporationContactPointJoinCondition = \sprintf('%s.%s', $corporationJoinAlias, 'contactPoints');
                $corporationContactPointJoinAlias = \sprintf('%s_%s', $corporationJoinAlias, 'contactPoints');
                if (!\in_array($corporationContactPointJoinAlias, $qb->getAllAliases(), true)) {
                    $qb->leftJoin($corporationContactPointJoinCondition, $corporationContactPointJoinAlias);
                }

                $emailExpr->add($expr->andX($expr->gt(\sprintf(<<<'SQL'
                jsonb_array_length(CAST(%s.%s AS jsonb))
SQL
                    , $corporationContactPointJoinAlias, 'emails'), $expr->literal(0))));

                $andEqExpr->add($emailExpr);
            } elseif (CampaignCategory::DIRECT_MAIL === $type) {
                $addressJoinCondition = \sprintf('%s.%s', $alias, 'addresses');
                $addressJoinAlias = \sprintf('%s_%s', $alias, 'addresses');
                if (!\in_array($addressJoinAlias, $qb->getAllAliases(), true)) {
                    $qb->join($addressJoinCondition, $addressJoinAlias);
                }

                if (CustomerAccount::class === $class) {
                    $addressJoinCondition = \sprintf('%s.%s', $addressJoinAlias, 'address');
                    $addressJoinAlias = \sprintf('%s_%s', $addressJoinAlias, 'address');
                    if (!\in_array($addressJoinAlias, $qb->getAllAliases(), true)) {
                        $qb->join($addressJoinCondition, $addressJoinAlias);
                    }
                }

                $andEqExpr->add($expr->eq(\sprintf('%s.%s', $addressJoinAlias, 'type'), $expr->literal(PostalAddressType::MAILING_ADDRESS)));
            }

            if (!empty($andEqExpr->getParts())) {
                $entities = $qb->andWhere($andEqExpr)->getQuery()->getResult();
                $uniques = [];

                if (CampaignCategory::EMAIL === $type) {
                    foreach ($entities as $entity) {
                        $contactPointsBase = null;
                        if (null !== $entity->getPersonDetails()) {
                            $contactPointsBase = $entity->getPersonDetails();
                        } elseif (null !== $entity->getCorporationDetails()) {
                            $contactPointsBase = $entity->getCorporationDetails();
                        }

                        if (null !== $contactPointsBase) {
                            foreach ($contactPointsBase->getContactPoints() as $contactPoint) {
                                if (\count($contactPoint->getEmails()) > 0) {
                                    $email = \array_values(\array_slice($contactPoint->getEmails(), -1))[0];
                                    // really strict check
                                    if (empty(\preg_grep("/$email/i", $uniques))) {
                                        $uniques[] = $email;
                                    }
                                }
                            }
                        }
                    }

                    $count = \count($uniques);
                } elseif (CampaignCategory::DIRECT_MAIL === $type) {
                    if (CustomerAccount::class === $class) {
                        foreach ($entities as $entity) {
                            foreach ($entity->getAddresses() as $customerAddress) {
                                if (PostalAddressType::MAILING_ADDRESS === $customerAddress->getAddress()->getType()->getValue()) {
                                    $fullAddress = $customerAddress->getAddress()->getFullAddress();

                                    if (!\in_array($fullAddress, $uniques, true)) {
                                        $uniques[] = $fullAddress;
                                    }
                                }
                            }
                        }

                        $count = \count($uniques);
                    } elseif (Lead::class === $class) {
                        foreach ($entities as $entity) {
                            foreach ($entity->getAddresses() as $address) {
                                if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                                    $fullAddress = $address->getFullAddress();

                                    if (!\in_array($fullAddress, $uniques, true)) {
                                        $uniques[] = $fullAddress;
                                    }
                                }
                            }
                        }

                        $count = \count($uniques);
                    }
                }
            }
        } else {
            $selectQuery = '';

            if (isset($customerType) && 'INDIVIDUAL' === $customerType) {
                if ('CUSTOMER' === $targetTable) {
                    $selectQuery = 'SELECT ca.id FROM customer_accounts ca LEFT JOIN people p on ca.person_details_id = p.id ';
                } elseif ('LEAD' === $targetTable) {
                    $selectQuery = 'SELECT ca.id FROM leads ca LEFT JOIN people p on ca.person_details_id = p.id ';
                }

                if (null !== $mappings && \count($mappings) > 0) {
                    $relationshipColumn = \strtolower(\str_replace(' ', '', $mappings[0]['source']));
                    $relationshipColumn = \preg_replace('/[^A-Za-z0-9\-]/', '_', $relationshipColumn);
                    if (ImportListingTargetFields::IDENTIFICATION === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN people_identifications pi on pi.person_id = p.id ';
                        $selectQuery .= 'LEFT JOIN identifications i ON i.id = pi.identification_id ';
                        $selectQuery .= "LEFT JOIN import_listing_data il on il.{$relationshipColumn} = i.value ";
                    } elseif (ImportListingTargetFields::EMAIL === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                        $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN import_listing_data il ON cp.emails @> json_build_array(il.email)::jsonb ';
                    } elseif (ImportListingTargetFields::PHONE === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                        $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN contact_points_telephone_number_roles t ON t.contact_point_id = cp.id ';
                        $selectQuery .= 'LEFT JOIN phone_number_roles nr ON nr.id = t.telephone_number_id ';
                        $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = nr.phone_number ";
                    } elseif (ImportListingTargetFields::MOBILE === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                        $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN contact_points_mobile_phone_number_roles t ON t.contact_point_id = cp.id ';
                        $selectQuery .= 'LEFT JOIN phone_number_roles nr ON nr.id = t.mobile_phone_number_id ';
                        $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = nr.phone_number ";
                    }
                }
            } elseif (isset($customerType) && 'CORPORATE' === $customerType) {
                if ('CUSTOMER' === $targetTable) {
                    $selectQuery = 'SELECT ca.id FROM customer_accounts ca LEFT JOIN corporations c ON ca.corporation_details_id = c.id ';
                } elseif ('LEAD' === $targetTable) {
                    $selectQuery = 'SELECT ca.id FROM leads ca LEFT JOIN corporations c ON ca.corporation_details_id = c.id ';
                }

                if (null !== $mappings && \count($mappings) > 0) {
                    $relationshipColumn = \strtolower(\str_replace(' ', '', $mappings[0]['source']));
                    $relationshipColumn = \preg_replace('/[^A-Za-z0-9\-]/', '_', $relationshipColumn);
                    if (ImportListingTargetFields::IDENTIFICATION === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN corporations_identifications cpi on cpi.corporation_id = c.id ';
                        $selectQuery .= 'LEFT JOIN identifications ci ON ci.id = cpi.identification_id ';
                        $selectQuery .= "LEFT JOIN import_listing_data il on il.{$relationshipColumn} = ci.value ";
                    } elseif (ImportListingTargetFields::EMAIL === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                        $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN import_listing_data il ON cpc.emails @> json_build_array(il.email)::jsonb ';
                    } elseif (ImportListingTargetFields::PHONE === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                        $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN contact_points_telephone_number_roles ct ON ct.contact_point_id = cpc.id ';
                        $selectQuery .= 'LEFT JOIN phone_number_roles cnr ON cnr.id = ct.telephone_number_id ';
                        $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = cnr.phone_number ";
                    } elseif (ImportListingTargetFields::MOBILE === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                        $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN contact_points_mobile_phone_number_roles ct ON ct.contact_point_id = cpc.id ';
                        $selectQuery .= 'LEFT JOIN phone_number_roles cnr ON cnr.id = ct.mobile_phone_number_id ';
                        $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = cnr.phone_number ";
                    }
                }
            } else {
                if ('CUSTOMER' === $targetTable) {
                    $selectQuery = 'SELECT ca.id FROM customer_accounts ca LEFT JOIN people p on ca.person_details_id = p.id ';
                } elseif ('LEAD' === $targetTable) {
                    $selectQuery = 'SELECT ca.id FROM leads ca LEFT JOIN people p on ca.person_details_id = p.id ';
                }

                $selectQuery .= 'LEFT JOIN corporations c ON ca.corporation_details_id = c.id ';

                if (null !== $mappings && \count($mappings) > 0) {
                    $relationshipColumn = \strtolower(\str_replace(' ', '', $mappings[0]['source']));
                    $relationshipColumn = \preg_replace('/[^A-Za-z0-9\-]/', '_', $relationshipColumn);
                    if (ImportListingTargetFields::IDENTIFICATION === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN people_identifications pi on pi.person_id = p.id ';
                        $selectQuery .= 'LEFT JOIN identifications i ON i.id = pi.identification_id ';
                        $selectQuery .= 'LEFT JOIN corporations_identifications cpi on cpi.corporation_id = c.id ';
                        $selectQuery .= 'LEFT JOIN identifications ci ON ci.id = cpi.identification_id ';
                        $selectQuery .= "LEFT JOIN import_listing_data il on il.{$relationshipColumn} = i.value ";
                        $selectQuery .= "LEFT JOIN import_listing_data cil on cil.{$relationshipColumn} = ci.value ";
                    } elseif (ImportListingTargetFields::EMAIL === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                        $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                        $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN import_listing_data il ON cp.emails @> json_build_array(il.email)::jsonb ';
                        $selectQuery .= 'LEFT JOIN import_listing_data cil ON cpc.emails @> json_build_array(cil.email)::jsonb ';
                    } elseif (ImportListingTargetFields::PHONE === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                        $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                        $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN contact_points_telephone_number_roles t ON t.contact_point_id = cp.id ';
                        $selectQuery .= 'LEFT JOIN contact_points_telephone_number_roles ct ON ct.contact_point_id = cpc.id ';
                        $selectQuery .= 'LEFT JOIN phone_number_roles nr ON nr.id = t.telephone_number_id ';
                        $selectQuery .= 'LEFT JOIN phone_number_roles cnr ON cnr.id = ct.telephone_number_id ';
                        $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = nr.phone_number ";
                        $selectQuery .= "LEFT JOIN import_listing_data cil ON cil.{$relationshipColumn} = cnr.phone_number ";
                    } elseif (ImportListingTargetFields::MOBILE === $mappings[0]['target']) {
                        $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                        $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                        $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                        $selectQuery .= 'LEFT JOIN contact_points_mobile_phone_number_roles t ON t.contact_point_id = cp.id ';
                        $selectQuery .= 'LEFT JOIN contact_points_mobile_phone_number_roles ct ON ct.contact_point_id = cpc.id ';
                        $selectQuery .= 'LEFT JOIN phone_number_roles nr ON nr.id = t.mobile_phone_number_id ';
                        $selectQuery .= 'LEFT JOIN phone_number_roles cnr ON cnr.id = ct.mobile_phone_number_id ';
                        $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = nr.phone_number ";
                        $selectQuery .= "LEFT JOIN import_listing_data cil ON cil.{$relationshipColumn} = cnr.phone_number ";
                    }
                }
            }

            foreach ($conditions as $key => $condition) {
                $column = \strtolower(\str_replace(' ', '', $condition[0]));
                $column = \preg_replace('/[^A-Za-z0-9[]\-]/', '_', $column);

                $targetClass = $fields = \explode('.', $filterParams[$key])[0];

                $isArrayColumn = false;
                if ('[]' === \substr($column, \strlen($column) - 2, 2)) {
                    $isArrayColumn = true;
                    $column = \substr($column, 0, -2);
                }

                if (0 === $key) {
                    if (self::IMPORT_LISTING !== $targetClass) {
                        $selectQuery .= $isArrayColumn
                            ? "WHERE ca.{$column} @> '[\"{$values[$key]}\"]'::jsonb"
                            : "WHERE ca.{$column} = '{$values[$key]}'";
                    } else {
                        $selectQuery .= isset($customerType)
                            ? "WHERE il.{$column} = '{$values[$key]}'"
                            : "WHERE (il.{$column} = '{$values[$key]}' OR cil.{$column} = '{$values[$key]}')";
                    }
                } else {
                    if (self::IMPORT_LISTING !== $targetClass) {
                        $selectQuery .= $isArrayColumn
                            ? " AND ca.{$column} @> '[\"{$values[$key]}\"]'::jsonb"
                            : " AND ca.{$column} = '{$values[$key]}'";
                    } else {
                        isset($customerType)
                        ? $selectQuery .= " AND il.{$column} = '{$values[$key]}'"
                        : $selectQuery .= " AND (il.{$column} = '{$values[$key]}' OR cil.{$column} = '{$values[$key]}')";
                    }
                }
            }

            $query = $this->entityManager->getConnection()->prepare($selectQuery);
            $query->execute();
            $result = $query->fetchAll();

            $count = \count($result);
        }

        return $count;
    }
}
