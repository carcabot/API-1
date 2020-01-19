<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CustomerAccount;
use App\Entity\DirectMailCampaignSourceList;
use App\Entity\DirectMailCampaignSourceListItem;
use App\Entity\EmailCampaignSourceList;
use App\Entity\EmailCampaignSourceListItem;
use App\Entity\ImportListingMapping;
use App\Entity\Lead;
use App\Entity\SourceList;
use App\Entity\UnsubscribeListItem;
use App\Enum\CampaignCategory;
use App\Enum\CampaignSourceType;
use App\Enum\ImportListingTargetFields;
use App\Enum\PostalAddressType;
use App\Repository\UnsubscribeListItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Zend\Diactoros\Response\JsonResponse;

class CampaignSourceListController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var UnsubscribeListItem[]
     */
    private $exclusionList;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     */
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        if ($this->entityManager->getRepository(UnsubscribeListItem::class) instanceof UnsubscribeListItemRepository) {
            $this->exclusionList = $this->entityManager->getRepository(UnsubscribeListItem::class)->findAllEmails();
        }
    }

    /**
     * @Route("/count/campaign_source_lists", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function countAction(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);
        $values = [];
        $customerAccounts = [];
        $customerAccountValues = [];
        $leadValues = [];
        $emails = [
            'customerAccounts' => 0,
            'leads' => 0,
        ];
        $leads = [];
        $type = $params['type'] ?? CampaignCategory::EMAIL;

        if (!empty($params['customerAccount'])) {
            $qb = $this->generateQuery($params['customerAccount'], CustomerAccount::class, $type);

            if (null !== $qb) {
                $customerAccounts = $qb->getQuery()->getResult();
            }
        }

        if (!empty($params['lead'])) {
            $qb = $this->generateQuery($params['lead'], Lead::class, $type);

            if (null !== $qb) {
                $leads = $qb->getQuery()->getResult();
            }
        }

        if (!empty($params['custom'])) {
            $firstMapping = $this->entityManager->getRepository(ImportListingMapping::class)->createQueryBuilder('m')
                ->setMaxResults(1)
                ->orderBy('m.id', 'ASC')
                ->getQuery()
                ->getSingleResult();
            if (!empty($params['custom']) && isset($firstMapping)) {
                $matchingIds = $this->fetchMatchingRecords($params['custom'], $firstMapping, $type);

                if ('CUSTOMER' === $firstMapping->getTargetClass()) {
                    $selectQb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customerAccount');
                    $customerAccounts = $selectQb->select('customerAccount')
                        ->where($selectQb->expr()->in('customerAccount.id', ':ids'))
                        ->setParameter('ids', $matchingIds)
                        ->getQuery()
                        ->getResult();
                } elseif ('LEAD' === $firstMapping->getTargetClass()) {
                    $selectQb = $this->entityManager->getRepository(Lead::class)->createQueryBuilder('lead');
                    $leads = $selectQb->select('lead')
                        ->where($selectQb->expr()->in('lead.id', ':ids'))
                        ->setParameter('ids', $matchingIds)
                        ->getQuery()
                        ->getResult();
                }
            }
        }

        $values = $this->removeDuplicates($customerAccounts, $values, $type, CustomerAccount::class);
        $customerAccountValues = \array_values($values);

        $values = $this->removeDuplicates($leads, $values, $type, Lead::class);
        $leadValues = \array_values(\array_diff($values, $customerAccountValues));

        $emails['customerAccounts'] = \count($customerAccountValues);
        $emails['leads'] = \count($leadValues);

        return new JsonResponse($emails, 200);
    }

    /**
     * @Route("/generate/campaign_source_lists/{id}", methods={"PUT"})
     * @ParamConverter("sourceList", options={"mapping"={"id"="id"}})
     *
     * @param ServerRequestInterface $request
     * @param SourceList             $sourceList
     *
     * @return HttpResponseInterface
     */
    public function updateAction(ServerRequestInterface $request, SourceList $sourceList): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);
        $customerAccounts = [];
        $values = [];
        $leads = [];
        $type = $params['type'] ?? CampaignCategory::EMAIL;
        $customerType = null;

        if (\array_key_exists('customerType', $params)) {
            $customerType = $params['customerType'];
        }

        if (!$sourceList instanceof EmailCampaignSourceList && !$sourceList instanceof DirectMailCampaignSourceList) {
            throw new BadRequestHttpException('Currently only supports class EmailCampaignSourceList, DirectMailCampaignSourceList.');
        }

        $serializeOptions = [
            'context' => [
                'email_campaign_source_list_read',
                'direct_mail_campaign_source_list_read',
            ],
        ];

        if (!empty($params['customerAccount'])) {
            $qb = $this->generateQuery($params['customerAccount'], CustomerAccount::class, $type);

            if (null !== $qb) {
                $customerAccounts = $qb->getQuery()->getResult();
            }
        }

        if (!empty($params['lead'])) {
            $qb = $this->generateQuery($params['lead'], Lead::class, $type);

            if (null !== $qb) {
                $leads = $qb->getQuery()->getResult();
            }
        }

        if (!empty($params['custom'])) {
            $firstMapping = $this->entityManager->getRepository(ImportListingMapping::class)->createQueryBuilder('m')
                ->setMaxResults(1)
                ->orderBy('m.id', 'ASC')
                ->getQuery()
                ->getSingleResult();
            if (!empty($params['custom']) && isset($firstMapping)) {
                $matchingIds = $this->fetchMatchingRecords($params['custom'], $firstMapping, $customerType);

                if ('CUSTOMER' === $firstMapping->getTargetClass()) {
                    $selectQb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customerAccount');
                    $customerAccounts = $selectQb->select('customerAccount')
                        ->where($selectQb->expr()->in('customerAccount.id', ':ids'))
                        ->setParameter('ids', $matchingIds)
                        ->getQuery()
                        ->getResult();
                } elseif ('LEAD' === $firstMapping->getTargetClass()) {
                    $selectQb = $this->entityManager->getRepository(Lead::class)->createQueryBuilder('lead');
                    $customerAccounts = $selectQb->select('lead')
                        ->where($selectQb->expr()->in('lead.id', ':ids'))
                        ->setParameter('ids', $matchingIds)
                        ->getQuery()
                        ->getResult();
                }
            }
        }

        if (!empty($params['name'])) {
            $sourceList->setName($params['name']);
        }

        if (!empty($params['description'])) {
            $sourceList->setDescription($params['description']);
        }

        if (!empty($params['vennDiagramFormula'])) {
            $sourceList->setVennDiagramFormula($params['vennDiagramFormula']);
        }

        $sourceList->clearItemListElement();

        // why we do this? so we do not have any duplicates across different base entities
        $values = $this->createSourceListItem($sourceList, $customerAccounts, $values, $type, CustomerAccount::class);

        // $values is from the above to make sure no duplicates
        $this->createSourceListItem($sourceList, $leads, $values, $type, Lead::class);

        $this->entityManager->persist($sourceList);
        $this->entityManager->flush();
        $sourceList = \json_decode($this->serializer->serialize($sourceList, 'jsonld', $serializeOptions));

        return new JsonResponse($sourceList, 200);
    }

    /**
     * @Route("/generate/campaign_source_lists", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function generateAction(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);
        $customerAccounts = [];
        $leads = [];
        $values = [];
        $type = $params['type'] ?? CampaignCategory::EMAIL;
        $customerType = null;

        if (\array_key_exists('customerType', $params)) {
            $customerType = $params['customerType'];
        }

        if (!empty($params['customerAccount'])) {
            $qb = $this->generateQuery($params['customerAccount'], CustomerAccount::class, $type);

            if (null !== $qb) {
                $customerAccounts = $qb->getQuery()->getResult();
            }
        }

        if (!empty($params['lead'])) {
            $qb = $this->generateQuery($params['lead'], Lead::class, $type);

            if (null !== $qb) {
                $leads = $qb->getQuery()->getResult();
            }
        }

        if (!empty($params['custom'])) {
            $firstMapping = $this->entityManager->getRepository(ImportListingMapping::class)->createQueryBuilder('m')
                ->setMaxResults(1)
                ->orderBy('m.id', 'ASC')
                ->getQuery()
                ->getSingleResult();
            if (!empty($params['custom']) && isset($firstMapping)) {
                $matchingIds = $this->fetchMatchingRecords($params['custom'], $firstMapping, $customerType);

                if ('CUSTOMER' === $firstMapping->getTargetClass()) {
                    $selectQb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customerAccount');
                    $customerAccounts = $selectQb->select('customerAccount')
                        ->where($selectQb->expr()->in('customerAccount.id', ':ids'))
                        ->setParameter('ids', $matchingIds)
                        ->getQuery()
                        ->getResult();
                } elseif ('LEAD' === $firstMapping->getTargetClass()) {
                    $selectQb = $this->entityManager->getRepository(Lead::class)->createQueryBuilder('lead');
                    $customerAccounts = $selectQb->select('lead')
                        ->where($selectQb->expr()->in('lead.id', ':ids'))
                        ->setParameter('ids', $matchingIds)
                        ->getQuery()
                        ->getResult();
                }
            }
        }

        $serializeOptions = [
            'context' => [
                'email_campaign_source_list_read',
                'direct_mail_campaign_source_list_read',
            ],
        ];

        if (CampaignCategory::EMAIL === $type) {
            $sourceList = new EmailCampaignSourceList();
        } elseif (CampaignCategory::DIRECT_MAIL === $type) {
            $sourceList = new DirectMailCampaignSourceList();
        } else {
            throw new BadRequestHttpException(\sprintf('The parameter $type must be one of : %s, %s.', CampaignCategory::EMAIL, CampaignCategory::DIRECT_MAIL));
        }

        if (!empty($params['name'])) {
            $sourceList->setName($params['name']);
        }

        if (!empty($params['description'])) {
            $sourceList->setDescription($params['description']);
        }

        if (!empty($params['vennDiagramFormula'])) {
            $sourceList->setVennDiagramFormula($params['vennDiagramFormula']);
        }

        // why we do this? so we do not have any duplicates across different base entities
        $values = $this->createSourceListItem($sourceList, $customerAccounts, $values, $type, CustomerAccount::class);

        // $values is from the above to make sure no duplicates
        $this->createSourceListItem($sourceList, $leads, $values, $type, Lead::class);

        $this->entityManager->persist($sourceList);
        $this->entityManager->flush();
        $sourceList = \json_decode($this->serializer->serialize($sourceList, 'jsonld', $serializeOptions));

        return new JsonResponse($sourceList, 200);
    }

    private function generateQuery(array $data, string $class, string $type)
    {
        $alias = 'a';
        $qb = $this->entityManager->createQueryBuilder()->select($alias)->from($class, $alias);
        $expr = $qb->expr();
        $orX = $expr->orX();

        foreach ($data as $queryParams) {
            $andEqExpr = $expr->andX();
            $andNeqExpr = $expr->andX();

            if (!empty($queryParams['eq'])) {
                foreach ($queryParams['eq'] as $path => $value) {
                    $filterParts = \explode('.', $path);
                    $andEqExpr->add($this->magicPls($qb, $class, [$filterParts[1], $value], $qb));
                }
            }

            if (!empty($queryParams['neq'])) {
                foreach ($queryParams['neq'] as $key => $filters) {
                    $containsImportListing = false;
                    foreach ($filters as $baseClass => $filter) {
                        $containsImportListing = \strpos($baseClass, 'importListing');

                        if (false !== $containsImportListing) {
                            break;
                        }
                    }

                    if (false !== $containsImportListing) {
                        $matchingIds = $this->fetchMatchingRecords([['neq' => [$filters]]], null, $type);
                        if (!empty($matchingIds)) {
                            $matchingIdsParam = \array_map(function ($customer) { return $customer['id']; }, $matchingIds);
                            $andNeqExpr->add($expr->notIn("$alias.id", ':matchingIds'));
                            $qb->setParameter('matchingIds', $matchingIdsParam);
                        }
                    } else {
                        $subQueryAlias = \sprintf('%s_%s', 'sb', \mt_rand());
                        $subQueryQb = $this->entityManager->createQueryBuilder()->select($subQueryAlias)->from($class, $subQueryAlias);
                        foreach ($filters as $path => $value) {
                            $filterParts = \explode('.', $path);
                            $subQueryQb->andWhere($this->magicPls($subQueryQb, $class, [$filterParts[1], $value], $qb));
                        }
                        $andNeqExpr->add($expr->notIn("$alias.id", $subQueryQb->getDQL()));
                    }
                }
            }

            if (!empty($andEqExpr->getParts()) && !empty($andNeqExpr->getParts())) {
                $orX->add($andNeqExpr);
                $orX->add($andEqExpr);
            } elseif (!empty($andEqExpr->getParts())) {
                $orX->add($andEqExpr);
            } elseif (!empty($andNeqExpr->getParts())) {
                $orX->add($andNeqExpr);
            }
        }

        if (!empty($orX->getParts())) {
            $qb->andWhere($orX);
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

            $qb->andWhere($emailExpr);
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

            $qb->andWhere($expr->eq(\sprintf('%s.%s', $addressJoinAlias, 'type'), $expr->literal(PostalAddressType::MAILING_ADDRESS)));
        }

        return $qb;
    }

    // @todo refactor so no magic.
    private function magicPls($qb, $class, $args, $parentQb)
    {
        $alias = 'a';
        $expr = $qb->expr();
        list($path, $value) = $args;
        $conditions = \explode('.', $path);
        $conditionLength = \count($conditions);
        $embedded = [];

        if (Lead::class === $class) {
            $embedded = [
                'averageConsumption',
                'purchaseTimeFrame',
            ];
        }

        if ($conditionLength > 1) {
            $columnString = '';
            $previousAlias = null;

            foreach ($conditions as $idx => $column) {
                // last one so time to finish the query
                if ($idx === ($conditionLength - 1)) {
                    if (null !== $previousAlias) {
                        $columnString = $previousAlias;
                    }

                    if ('[]' === \substr($column, \strlen($column) - 2, 2)) {
                        $column = \substr($column, 0, -2);
                        $valueParam = \sprintf('%s_%s', $columnString, $column.\mt_rand());

                        $parentQb->setParameter($valueParam, \json_encode($value));

                        return $expr->andX($expr->eq(\sprintf(<<<'SQL'
                            jsonb_contains(CAST(%s.%s AS jsonb), :%s)
SQL
                            , $columnString, $column, $valueParam), $expr->literal(true)));
                    }

                    return $expr->eq(\sprintf('%s.%s', $columnString, $column), $expr->literal($value));
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
        }
        $column = $conditions[0];
        if ('[]' === \substr($column, \strlen($column) - 2, 2)) {
            $column = \substr($column, 0, -2);
            $valueParam = \sprintf('%s_%s', $column, \mt_rand());

            $parentQb->setParameter($valueParam, \json_encode($value));

            return $expr->andX($expr->eq(\sprintf(<<<'SQL'
                    jsonb_contains(CAST(%s.%s AS jsonb), :%s)
SQL
                , $alias, $column, $valueParam), $expr->literal(true)));
        }

        return $expr->eq(\sprintf('%s.%s', $alias, $column), $expr->literal($value));
    }

    private function createSourceListItem(SourceList $sourceList, array $entities, array $values, string $type, string $class)
    {
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
                            if (!\in_array($email, $values, true) && !\in_array($email, $this->exclusionList, true)) {
                                $values[] = $email;

                                $listItem = new EmailCampaignSourceListItem();
                                $listItem->setEmailAddress($email);

                                if (CustomerAccount::class === $class) {
                                    $listItem->setSource(new CampaignSourceType(CampaignSourceType::CUSTOMER_ACCOUNT));
                                    $listItem->setCustomer($entity);
                                } elseif (Lead::class === $class) {
                                    $listItem->setSource(new CampaignSourceType(CampaignSourceType::LEAD));
                                    $listItem->setLead($entity);
                                }

                                $listItem->setValue($email);
                                $this->entityManager->persist($listItem);

                                $sourceList->addItemListElement($listItem);
                            }
                        }
                    }
                }
            }
        } elseif (CampaignCategory::DIRECT_MAIL === $type) {
            if (CustomerAccount::class === $class) {
                foreach ($entities as $entity) {
                    foreach ($entity->getAddresses() as $customerAddress) {
                        if (PostalAddressType::MAILING_ADDRESS === $customerAddress->getAddress()->getType()->getValue()) {
                            $fullAddress = $customerAddress->getAddress()->getFullAddress();

                            if (!\in_array($fullAddress, $values, true)) {
                                $values[] = $fullAddress;

                                $listItem = new DirectMailCampaignSourceListItem();

                                $postalAddress = clone $customerAddress->getAddress();
                                $this->entityManager->persist($postalAddress);

                                $listItem->setItem($postalAddress);
                                $listItem->setSource(new CampaignSourceType(CampaignSourceType::CUSTOMER_ACCOUNT));
                                $listItem->setCustomer($entity);
                                $listItem->setValue($fullAddress);
                                $this->entityManager->persist($listItem);

                                $sourceList->addItemListElement($listItem);
                            }
                        }
                    }
                }
            } elseif (Lead::class === $class) {
                foreach ($entities as $entity) {
                    foreach ($entity->getAddresses() as $address) {
                        if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                            $fullAddress = $address->getFullAddress();

                            if (!\in_array($fullAddress, $values, true)) {
                                $values[] = $fullAddress;

                                $listItem = new DirectMailCampaignSourceListItem();

                                $postalAddress = clone $address;
                                $this->entityManager->persist($postalAddress);

                                $listItem->setItem($postalAddress);
                                $listItem->setSource(new CampaignSourceType(CampaignSourceType::LEAD));
                                $listItem->setLead($entity);
                                $listItem->setValue($fullAddress);
                                $this->entityManager->persist($listItem);

                                $sourceList->addItemListElement($listItem);
                            }
                        }
                    }
                }
            }
        }

        return $values;
    }

    private function removeDuplicates(array $entities, array $values, string $type, string $class)
    {
        if (CampaignCategory::EMAIL === $type) {
            foreach ($entities as $entity) {
                if (null !== $entity->getPersonDetails()) {
                    foreach ($entity->getPersonDetails()->getContactPoints() as $contactPoint) {
                        if (\count($contactPoint->getEmails()) > 0) {
                            $email = \array_values(\array_slice($contactPoint->getEmails(), -1))[0];
                            if (empty(\preg_grep("/$email/i", $values)) && empty(\preg_grep("/$email/i", $this->exclusionList))) {
                                $values[] = $email;
                            }
                        }
                    }
                }
            }
        } elseif (CampaignCategory::DIRECT_MAIL === $type) {
            if (CustomerAccount::class === $class) {
                foreach ($entities as $entity) {
                    foreach ($entity->getAddresses() as $customerAddress) {
                        if (PostalAddressType::MAILING_ADDRESS === $customerAddress->getAddress()->getType()->getValue()) {
                            $fullAddress = $customerAddress->getAddress()->getFullAddress();
                            if (empty(\preg_grep("/$fullAddress/i", $values))) {
                                $values[] = $fullAddress;
                            }
                        }
                    }
                }
            } elseif (Lead::class === $class) {
                foreach ($entities as $entity) {
                    foreach ($entity->getAddresses() as $address) {
                        if (PostalAddressType::MAILING_ADDRESS === $address->getType()->getValue()) {
                            $fullAddress = $address->getFullAddress();
                            if (empty(\preg_grep("/$fullAddress/i", $values))) {
                                $values[] = $fullAddress;
                            }
                        }
                    }
                }
            }
        }

        return $values;
    }

    private function fetchMatchingRecords($data, ?ImportListingMapping $relationship, ?string $customerType)
    {
        $result = [];

        if (!isset($relationship)) {
            $relationship = $this->entityManager->getRepository(ImportListingMapping::class)->createQueryBuilder('m')
                ->setMaxResults(1)
                ->orderBy('m.id', 'ASC')
                ->getQuery()
                ->getSingleResult();
        }

        $baseClass = $relationship->getTargetClass();

        $selectQuery = '';
        if (isset($customerType) && 'INDIVIDUAL' === $customerType) {
            if ('CUSTOMER' === $baseClass) {
                $selectQuery = 'SELECT ca.id FROM customer_accounts ca LEFT JOIN people p on ca.person_details_id = p.id ';
            } elseif ('LEAD' === $baseClass) {
                $selectQuery = 'SELECT ca.id FROM leads ca LEFT JOIN people p on ca.person_details_id = p.id ';
            }

            $relationshipColumn = \strtolower(\str_replace(' ', '', $relationship->getSource()));
            $relationshipColumn = \preg_replace('/[^A-Za-z0-9\-]/', '_', $relationshipColumn);
            if (ImportListingTargetFields::IDENTIFICATION === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN people_identifications pi on pi.person_id = p.id ';
                $selectQuery .= 'LEFT JOIN identifications i ON i.id = pi.identification_id ';
                $selectQuery .= "LEFT JOIN import_listing_data il on il.{$relationshipColumn} = i.value ";
            } elseif (ImportListingTargetFields::EMAIL === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN import_listing_data il ON cp.emails @> json_build_array(il.email)::jsonb ';
            } elseif (ImportListingTargetFields::PHONE === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN contact_points_telephone_number_roles t ON t.contact_point_id = cp.id ';
                $selectQuery .= 'LEFT JOIN phone_number_roles nr ON nr.id = t.telephone_number_id ';
                $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = nr.phone_number ";
            } elseif (ImportListingTargetFields::MOBILE === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN contact_points_mobile_phone_number_roles t ON t.contact_point_id = cp.id ';
                $selectQuery .= 'LEFT JOIN phone_number_roles nr ON nr.id = t.mobile_phone_number_id ';
                $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = nr.phone_number ";
            }
        } elseif (isset($customerType) && 'CORPORATE' === $customerType) {
            if ('CUSTOMER' === $baseClass) {
                $selectQuery = 'SELECT ca.id FROM customer_accounts ca LEFT JOIN corporations c ON ca.corporation_details_id = c.id ';
            } elseif ('LEAD' === $baseClass) {
                $selectQuery = 'SELECT ca.id FROM leads ca LEFT JOIN corporations c ON ca.corporation_details_id = c.id ';
            }

            $relationshipColumn = \strtolower(\str_replace(' ', '', $relationship->getSource()));
            $relationshipColumn = \preg_replace('/[^A-Za-z0-9\-]/', '_', $relationshipColumn);
            if (ImportListingTargetFields::IDENTIFICATION === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN corporations_identifications cpi on cpi.corporation_id = c.id ';
                $selectQuery .= 'LEFT JOIN identifications ci ON ci.id = cpi.identification_id ';
                $selectQuery .= "LEFT JOIN import_listing_data il on il.{$relationshipColumn} = ci.value ";
            } elseif (ImportListingTargetFields::EMAIL === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN import_listing_data il ON cpc.emails @> json_build_array(il.email)::jsonb ';
            } elseif (ImportListingTargetFields::PHONE === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN contact_points_telephone_number_roles ct ON ct.contact_point_id = cpc.id ';
                $selectQuery .= 'LEFT JOIN phone_number_roles cnr ON cnr.id = ct.telephone_number_id ';
                $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = cnr.phone_number ";
            } elseif (ImportListingTargetFields::MOBILE === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN contact_points_mobile_phone_number_roles ct ON ct.contact_point_id = cpc.id ';
                $selectQuery .= 'LEFT JOIN phone_number_roles cnr ON cnr.id = ct.mobile_phone_number_id ';
                $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = cnr.phone_number ";
            }
        } else {
            if ('CUSTOMER' === $baseClass) {
                $selectQuery = 'SELECT ca.id FROM customer_accounts ca LEFT JOIN people p on ca.person_details_id = p.id ';
            } elseif ('LEAD' === $baseClass) {
                $selectQuery = 'SELECT ca.id FROM leads ca LEFT JOIN people p on ca.person_details_id = p.id ';
            }

            $selectQuery .= 'LEFT JOIN corporations c ON ca.corporation_details_id = c.id ';

            $relationshipColumn = \strtolower(\str_replace(' ', '', $relationship->getSource()));
            $relationshipColumn = \preg_replace('/[^A-Za-z0-9\-]/', '_', $relationshipColumn);
            if (ImportListingTargetFields::IDENTIFICATION === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN people_identifications pi on pi.person_id = p.id ';
                $selectQuery .= 'LEFT JOIN identifications i ON i.id = pi.identification_id ';
                $selectQuery .= 'LEFT JOIN corporations_identifications cpi on cpi.corporation_id = c.id ';
                $selectQuery .= 'LEFT JOIN identifications ci ON ci.id = cpi.identification_id ';
                $selectQuery .= "LEFT JOIN import_listing_data il on il.{$relationshipColumn} = i.value ";
                $selectQuery .= "LEFT JOIN import_listing_data cil on cil.{$relationshipColumn} = ci.value";
            } elseif (ImportListingTargetFields::EMAIL === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN import_listing_data il ON cp.emails @> json_build_array(il.email)::jsonb ';
                $selectQuery .= 'LEFT JOIN import_listing_data cil ON cpc.emails @> json_build_array(cil.email)::jsonb';
            } elseif (ImportListingTargetFields::PHONE === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN contact_points_telephone_number_roles t ON t.contact_point_id = cp.id ';
                $selectQuery .= 'LEFT JOIN contact_points_telephone_number_roles ct ON ct.contact_point_id = cpc.id ';
                $selectQuery .= 'LEFT JOIN phone_number_roles nr ON nr.id = t.telephone_number_id ';
                $selectQuery .= 'LEFT JOIN phone_number_roles cnr ON cnr.id = ct.telephone_number_id ';
                $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = nr.phone_number ";
                $selectQuery .= "LEFT JOIN import_listing_data cil ON cil.{$relationshipColumn} = cnr.phone_number";
            } elseif (ImportListingTargetFields::MOBILE === $relationship->getTarget()->getValue()) {
                $selectQuery .= 'LEFT JOIN people_contact_points pcp on pcp.person_id = p.id ';
                $selectQuery .= 'LEFT JOIN corporations_contact_points ccp on ccp.corporation_id = c.id ';
                $selectQuery .= 'LEFT JOIN contact_points cp ON cp.id = pcp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN contact_points cpc ON cpc.id = ccp.contact_point_id ';
                $selectQuery .= 'LEFT JOIN contact_points_mobile_phone_number_roles t ON t.contact_point_id = cp.id ';
                $selectQuery .= 'LEFT JOIN contact_points_mobile_phone_number_roles ct ON ct.contact_point_id = cpc.id ';
                $selectQuery .= 'LEFT JOIN phone_number_roles nr ON nr.id = t.mobile_phone_number_id ';
                $selectQuery .= 'LEFT JOIN phone_number_roles cnr ON cnr.id = ct.mobile_phone_number_id ';
                $selectQuery .= "LEFT JOIN import_listing_data il ON il.{$relationshipColumn} = nr.phone_number ";
                $selectQuery .= "LEFT JOIN import_listing_data cil ON cil.{$relationshipColumn} = cnr.phone_number";
            }
        }

        foreach ($data as $key => $conditions) {
            if (!empty($conditions['eq'])) {
                $currentIndex = 0;
                foreach ($conditions['eq'] as $columnDetails => $value) {
                    $targetClass = \explode('.', $columnDetails)[0];
                    $column = \explode('.', $columnDetails)[1];
                    $column = \strtolower(\str_replace(' ', '', $column));
                    $column = \preg_replace('/[^A-Za-z0-9[]\-]/', '_', $column);

                    $isArrayColumn = false;
                    if ('[]' === \substr($column, \strlen($column) - 2, 2)) {
                        $isArrayColumn = true;
                        $column = \substr($column, 0, -2);
                    }

                    if (0 === $currentIndex && 0 === $key) {
                        if ('importListing' !== $targetClass) {
                            $selectQuery .= $isArrayColumn
                                ? " WHERE ca.{$column} @> '[\"{$value}\"]'::jsonb"
                                : " WHERE ca.{$column} = '{$value}'";
                        } else {
                            $selectQuery .= isset($customerType)
                                ? " WHERE il.{$column} = '{$value}'"
                                : " WHERE (il.{$column} = '{$value}' OR cil.{$column} = '{$value}')";
                        }
                    } else {
                        if ('importListing' !== $targetClass) {
                            $selectQuery .= $isArrayColumn
                                ? " AND ca.{$column} @> '[\"{$value}\"]'::jsonb"
                                : " AND ca.{$column} = '{$value}'";
                        } else {
                            isset($customerType)
                                ? $selectQuery .= " AND il.{$column} = '{$value}'"
                                : $selectQuery .= " AND (il.{$column} = '{$value}' OR cil.{$column} = '{$value}')";
                        }
                    }

                    ++$currentIndex;
                }
            }

            if (!empty($conditions['neq'])) {
                $currentIndex = 0;
                foreach ($conditions['neq'] as $neqFilter) {
                    foreach ($neqFilter as $columnDetails => $value) {
                        $targetClass = \explode('.', $columnDetails)[0];
                        $column = \explode('.', $columnDetails)[1];
                        $column = \strtolower(\str_replace(' ', '', $column));
                        $column = \preg_replace('/[^A-Za-z0-9[]\-]/', '_', $column);

                        $isArrayColumn = false;
                        if ('[]' === \substr($column, \strlen($column) - 2, 2)) {
                            $isArrayColumn = true;
                            $column = \substr($column, 0, -2);
                        }

                        if (0 === $currentIndex && 0 === $key) {
                            if (empty($conditions['eq'])) {
                                if ('importListing' !== $targetClass) {
                                    $selectQuery .= $isArrayColumn
                                        ? " WHERE ca.{$column} @> '[\"{$value}\"]'::jsonb"
                                        : " WHERE ca.{$column} = '{$value}'";
                                } else {
                                    $selectQuery .= isset($customerType)
                                        ? " WHERE il.{$column} = '{$value}'"
                                        : " WHERE il.{$column} = '{$value}' OR cil.{$column} = '{$value}'";
                                }
                            }
                        } else {
                            if ('importListing' !== $targetClass) {
                                $selectQuery .= $isArrayColumn
                                    ? " AND (ca.{$column} @> '[\"{$value}\"]'::jsonb)"
                                    : " AND ca.{$column} = '{$value}'";
                            } else {
                                isset($customerType)
                                    ? $selectQuery .= " AND il.{$column} = '{$value}'"
                                    : $selectQuery .= " AND (il.{$column} = '{$value}' OR cil.{$column} <> '{$value}')";
                            }
                        }

                        ++$currentIndex;
                    }
                }
            }

            $query = $this->entityManager->getConnection()->prepare($selectQuery);
            $query->execute();
            $res = $query->fetchAll();
            $result = \array_merge($result, $res);
        }

        return $result;
    }
}
