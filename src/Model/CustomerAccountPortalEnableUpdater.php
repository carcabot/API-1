<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\CustomerAccount;
use App\Enum\AccountCategory;
use App\Enum\ContractStatus;
use Doctrine\ORM\EntityManagerInterface;
use iter;

class CustomerAccountPortalEnableUpdater
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

    public function update(CustomerAccount $customerAccount, \DateTime $date = null)
    {
        if (null === $date) {
            $date = new \DateTime();
            $date->sub(new \DateInterval('P90D'));
        }

        $this->updateCustomerPortalEnabled($customerAccount, $date);

        $this->entityManager->persist($customerAccount);
        $this->entityManager->flush();
    }

    public function updateCustomerPortalEnabled(CustomerAccount $customerAccount, \DateTime $date)
    {
        $contracts = [];

        $accountCategories = $customerAccount->getCategories();
        foreach ($accountCategories as $accountCategory) {
            if (AccountCategory::CONTACT_PERSON === $accountCategory) {
                $relationships = $customerAccount->getRelationships();

                foreach ($relationships as $relationship) {
                    $contracts = \array_merge($contracts, $relationship->getContracts());
                }
            } elseif (AccountCategory::CUSTOMER === $accountCategory) {
                $ownContracts = iter\toArray(iter\filter(function ($contract) {
                    return null !== $contract->getContractNumber();
                }, $customerAccount->getContracts()));

                $contracts = \array_merge($contracts, $ownContracts);
            }
        }

        if (0 === \count($contracts)) {
            $customerAccount->setCustomerPortalEnabled(false);

            return;
        }

        $latestInActiveContract = $contracts[0];

        foreach ($contracts as $contract) {
            if (null === $contract->getEndDate()) {
                $customerAccount->setCustomerPortalEnabled(true);

                return;
            }

            if (ContractStatus::ACTIVE === $contract->getStatus()->getValue() && $contract->getEndDate() > $date) {
                $customerAccount->setCustomerPortalEnabled(true);

                return;
            }

            if (null !== $latestInActiveContract->getEndDate()) {
                if ($contract->getEndDate() > $latestInActiveContract->getEndDate()) {
                    $latestInActiveContract = $contract;
                }
            }
        }

        if (null !== $latestInActiveContract->getEndDate()) {
            if ($date > $latestInActiveContract->getEndDate()) {
                $customerAccount->setCustomerPortalEnabled(false);
            } else {
                $customerAccount->setCustomerPortalEnabled(true);
            }
        }
    }
}
