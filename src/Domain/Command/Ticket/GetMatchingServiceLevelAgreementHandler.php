<?php

declare(strict_types=1);

namespace App\Domain\Command\Ticket;

use App\Entity\ServiceLevelAgreement;
use Doctrine\ORM\EntityManagerInterface;
use iter;

class GetMatchingServiceLevelAgreementHandler
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handle(GetMatchingServiceLevelAgreement $command)
    {
        $ticketCategory = $command->getCategory();
        $ticketPriority = $command->getPriority();
        $ticketSubcategory = $command->getSubcategory();
        $ticketType = $command->getType();

        $qb = $this->entityManager->getRepository(ServiceLevelAgreement::class)->createQueryBuilder('sla');
        $expr = $qb->expr();
        $sla = null;

        // with or without matching priority and at least one matching category (catch-all for the loop to select the SLA below)
        $validSLAs = $qb->select('sla')
            ->leftJoin('sla.ticketCategories', 'ticketCategory')
            ->leftJoin('sla.ticketTypes', 'ticketType')
            ->where(
                    $expr->orX(
                        $expr->eq('sla.priority', ':priority'),
                        $expr->eq('ticketCategory.id', ':category'),
                        $expr->eq('ticketCategory.id', ':subcategory'),
                        $expr->eq('ticketType.id', ':type')
                    )
            )
            ->setParameters([
                'priority' => $ticketPriority,
                'category' => $ticketCategory->getId(),
                'subcategory' => $ticketSubcategory->getId(),
                'type' => $ticketType,
            ])
            ->getQuery()
            ->getResult();

        // @todo need some genius to redo this

        // We filter by hierarchy of specificity in the following order: (1 being the highest priority)
        // 1.  Matching type, subcategory, category, priority
        // 2.  Matching type, subcategory, priority
        // 3.  Matching type, subcategory, category
        // 4.  Matching type, subcategory
        // 5.  Matching subcategory, category, priority
        // 6.  Matching subcategory, priority
        // 7.  Matching type, priority
        // 8.  Matching subcategory, category
        // 9.  Matching subcategory
        // 10. Matching type
        // 11. Matching priority
        $slaHierarchy = [];
        $possibleHierarchyLevels = [];
        $total = 11;
        // init the array
        for ($hierarchy = 1; $hierarchy <= $total; ++$hierarchy) {
            $slaHierarchy[$hierarchy] = [];
            $possibleHierarchyLevels[] = $hierarchy;
        }

        $typeLevels = [1, 2, 3, 4, 7, 10];
        $priorityLevels = [1, 2, 5, 6, 7, 11];

        // here we rearrange the catch-all to fit the hierarchy
        foreach ($validSLAs as $validSLA) {
            $hierarchyLevels = $possibleHierarchyLevels;
            $hierarchyLevel = 0;
            $slaMainCategory = null;
            $slaSubcategory = null;

            $slaTicketType = iter\search(function ($slaTicketType) use ($ticketType) {
                return $slaTicketType === $ticketType;
            }, $validSLA->getTicketTypes());

            // first we divide the categories
            foreach ($validSLA->getTicketCategories() as $slaTicketCategory) {
                if ($slaTicketCategory->getId() === $ticketSubcategory->getId()) {
                    $slaSubcategory = $slaTicketCategory;
                } elseif ($slaTicketCategory->getId() === $ticketCategory->getId()) {
                    $slaMainCategory = $slaTicketCategory;
                }
            }

            // matching type
            if (null !== $slaTicketType) {
                $hierarchyLevels = \array_intersect($hierarchyLevels, $typeLevels);
            } elseif (0 === \count($validSLA->getTicketTypes())) {
                $hierarchyLevels = \array_diff($hierarchyLevels, $typeLevels);
            } else {
                // types exists but does not match, filter out.
                continue;
            }

            if (null !== $validSLA->getPriority() && $validSLA->getPriority()->getValue() === $ticketPriority->getValue()) {
                $hierarchyLevels = \array_intersect($hierarchyLevels, $priorityLevels);
            } elseif (null === $validSLA->getPriority()) {
                $hierarchyLevels = \array_diff($hierarchyLevels, $priorityLevels);
            } else {
                // priority exists, but does not match, filter out.
                continue;
            }

            if (null === $slaSubcategory && null === $slaMainCategory && 0 === \count($validSLA->getTicketCategories())) {
                // this means no category matched, only type and/or priority
                // possible levels are 7, 10, 11
                $possibleLevels = [7, 10, 11];
                $hierarchyLevels = \array_intersect($hierarchyLevels, $possibleLevels);
            } elseif (null !== $slaSubcategory && null === $slaMainCategory) {
                // this means only sub category matched
                // possible levels are 2, 4, 6, 9
                $possibleLevels = [2, 4, 6, 9];
                $hierarchyLevels = \array_intersect($hierarchyLevels, $possibleLevels);
            } elseif (null !== $slaMainCategory && null !== $slaSubcategory) {
                // this means both main and sub categories matched
                // possible levels are 1, 3, 5, 8
                $possibleLevels = [1, 3, 5, 8];
                $hierarchyLevels = \array_intersect($hierarchyLevels, $possibleLevels);
            } else {
                // categories exists, but does not match, so filter out.
                continue;
            }

            if (\count($hierarchyLevels) > 0) {
                $hierarchyLevel = \reset($hierarchyLevels);
                $slaHierarchy[$hierarchyLevel][] = $validSLA;
            }
        }

        // here we get the highest priority and return the 'latest' configured SLA
        foreach ($slaHierarchy as $slaGroup) {
            $sla = iter\reduce(function ($currentSla, $sla, $key) {
                if (null === $currentSla || $sla->getDateModified() > $currentSla->getDateModified()) {
                    return $sla;
                }

                return $currentSla;
            }, $slaGroup, null);

            if (null !== $sla) {
                break;
            }
        }

        return $sla;
    }
}
