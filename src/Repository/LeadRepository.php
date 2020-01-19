<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CustomerAccount;
use App\Entity\Lead;
use App\Enum\AccountType;
use App\Enum\IdentificationName;
use App\Service\ReportHelper;
use Doctrine\ORM\EntityRepository;

class LeadRepository extends EntityRepository
{
    use KeywordSearchRepositoryTrait;

    /**
     * @param string $customerId
     *
     * @return array
     */
    public function findLeads(string $customerId): array
    {
        $leads = null;
        $identificationValue = null;
        $name = null;

        $customer = $this->getEntityManager()->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerId]);

        if (null !== $customer) {
            if (AccountType::INDIVIDUAL === $customer->getType()->getValue()) {
                $personDetails = $customer->getPersonDetails();
                if (null !== $personDetails) {
                    $name = $personDetails->getName();
                    $identificationValue = ReportHelper::mapIdentifiers($personDetails->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
                }
            } elseif (AccountType::CORPORATE === $customer->getType()->getValue()) {
                $corporationDetails = $customer->getCorporationDetails();
                if (null !== $corporationDetails) {
                    $name = $corporationDetails->getName();
                    $identificationValue = ReportHelper::mapIdentifiers($corporationDetails->getIdentifiers(), IdentificationName::UNIQUE_ENTITY_NUMBER);
                }
            }
        }

        $qb = $this->getEntityManager()->getRepository(Lead::class)->createQueryBuilder('lead');
        $expr = $qb->expr();
        if (null !== $name && null !== $identificationValue) {
            $leads = $qb->leftJoin('lead.personDetails', 'person')
                ->leftJoin('lead.corporationDetails', 'corporation')
                ->leftJoin('person.identifiers', 'personIdentity')
                ->leftJoin('corporation.identifiers', 'corporationIdentity')
                ->where(
                    $expr->orX(
                        $expr->andX(
                            $expr->eq('personIdentity.value', ':identity'),
                            $expr->eq('person.name', ':name')
                        ),
                        $expr->andX(
                            $expr->eq('corporationIdentity.value', ':identity'),
                            $expr->eq('corporation.name', ':name')
                        )
                    )
                )
                ->setParameter('name', $name)
                ->setParameter('identity', $identificationValue)
                ->getQuery()
                ->getResult();
        }

        return null !== $leads ? $leads : [];
    }
}
