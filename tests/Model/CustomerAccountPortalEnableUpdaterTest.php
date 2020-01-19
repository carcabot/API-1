<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountRelationship;
use App\Enum\ContractStatus;
use App\Model\CustomerAccountPortalEnableUpdater;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CustomerAccountPortalEnableUpdaterTest extends TestCase
{
    public function testInactiveContractWithEndDateLessThanNinetyDays()
    {
        $comparingDate = new \DateTime('2019-01-01 T00:00:00.000Z');
        $date = new \DateTime('2019-03-01 T00:00:00.000Z');

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('123');
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::INACTIVE));
        $contractProphecy->getEndDate()->willReturn($date);
        $activeContract = $contractProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getContracts()->willReturn([$activeContract]);
        $customerAccountProphecy->setCustomerPortalEnabled(true)->shouldBeCalled();
        $customerAccountProphecy->getCategories()->willReturn(['CUSTOMER']);
        $customerAccount = $customerAccountProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($customerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountPortalEnableUpdater = new CustomerAccountPortalEnableUpdater($entityManager);
        $customerAccountPortalEnableUpdater->update($customerAccount, $comparingDate);
    }

    public function testInactiveContractWithEndDateMoreThanNinetyDays()
    {
        $comparingDate = new \DateTime('2019-01-01 T00:00:00.000Z');
        $date = new \DateTime('2018-11-01 T00:00:00.000Z');

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('123');
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::INACTIVE));
        $contractProphecy->getEndDate()->willReturn($date);
        $activeContract = $contractProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getContracts()->willReturn([$activeContract]);
        $customerAccountProphecy->setCustomerPortalEnabled(false)->shouldBeCalled();
        $customerAccountProphecy->getCategories()->willReturn(['CUSTOMER']);
        $customerAccount = $customerAccountProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($customerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountPortalEnableUpdater = new CustomerAccountPortalEnableUpdater($entityManager);
        $customerAccountPortalEnableUpdater->update($customerAccount, $comparingDate);
    }

    public function testManyContracts()
    {
        $comparingDate = new \DateTime('2019-01-01 T00:00:00.000Z');
        $date = new \DateTime('2018-12-01 T00:00:00.000Z');
        $date1 = new \DateTime('2019-03-01 T00:00:00.000Z');
        $date2 = new \DateTime('2019-04-05 T00:00:00.000Z');
        $date3 = new \DateTime('2018-01-30 T00:00:00.000Z');

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('123');
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::ACTIVE));
        $contractProphecy->getEndDate()->willReturn($date);
        $activeContract = $contractProphecy->reveal();

        $contractProphecy2 = $this->prophesize(Contract::class);
        $contractProphecy2->getContractNumber()->willReturn('124');
        $contractProphecy2->getStatus()->willReturn(new ContractStatus(ContractStatus::INACTIVE));
        $contractProphecy2->getEndDate()->willReturn($date1);
        $inactiveContract = $contractProphecy2->reveal();

        $contractProphecy3 = $this->prophesize(Contract::class);
        $contractProphecy3->getContractNumber()->willReturn('125');
        $contractProphecy3->getStatus()->willReturn(new ContractStatus(ContractStatus::INACTIVE));
        $contractProphecy3->getEndDate()->willReturn($date2);
        $inactiveContract2 = $contractProphecy3->reveal();

        $contractProphecy4 = $this->prophesize(Contract::class);
        $contractProphecy4->getContractNumber()->willReturn('126');
        $contractProphecy4->getStatus()->willReturn(new ContractStatus(ContractStatus::ACTIVE));
        $contractProphecy4->getEndDate()->willReturn($date3);
        $activeContract2 = $contractProphecy4->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getContracts()->willReturn([$activeContract, $inactiveContract, $activeContract2, $inactiveContract2]);
        $customerAccountProphecy->setCustomerPortalEnabled(true)->shouldBeCalled();
        $customerAccountProphecy->getCategories()->willReturn(['CUSTOMER']);
        $customerAccount = $customerAccountProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($customerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountPortalEnableUpdater = new CustomerAccountPortalEnableUpdater($entityManager);
        $customerAccountPortalEnableUpdater->update($customerAccount, $comparingDate);
    }

    public function testManyContractsAllInactive()
    {
        $comparingDate = new \DateTime('2019-01-01 T00:00:00.000Z');
        $date = new \DateTime('2018-12-01 T00:00:00.000Z');
        $date1 = new \DateTime('2018-03-01 T00:00:00.000Z');
        $date2 = new \DateTime('2018-04-05 T00:00:00.000Z');
        $date3 = new \DateTime('2019-01-01 T00:00:00.000Z');

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('123');
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::INACTIVE));
        $contractProphecy->getEndDate()->willReturn($date);
        $activeContract = $contractProphecy->reveal();

        $contractProphecy2 = $this->prophesize(Contract::class);
        $contractProphecy2->getContractNumber()->willReturn('124');
        $contractProphecy2->getStatus()->willReturn(new ContractStatus(ContractStatus::INACTIVE));
        $contractProphecy2->getEndDate()->willReturn($date1);
        $inactiveContract = $contractProphecy2->reveal();

        $contractProphecy3 = $this->prophesize(Contract::class);
        $contractProphecy3->getContractNumber()->willReturn('125');
        $contractProphecy3->getStatus()->willReturn(new ContractStatus(ContractStatus::INACTIVE));
        $contractProphecy3->getEndDate()->willReturn($date2);
        $inactiveContract2 = $contractProphecy3->reveal();

        $contractProphecy4 = $this->prophesize(Contract::class);
        $contractProphecy4->getContractNumber()->willReturn('126');
        $contractProphecy4->getStatus()->willReturn(new ContractStatus(ContractStatus::INACTIVE));
        $contractProphecy4->getEndDate()->willReturn($date3);
        $activeContract2 = $contractProphecy4->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getContracts()->willReturn([$activeContract, $inactiveContract, $activeContract2, $inactiveContract2]);
        $customerAccountProphecy->setCustomerPortalEnabled(true)->shouldBeCalled();
        $customerAccountProphecy->getCategories()->willReturn(['CUSTOMER']);
        $customerAccount = $customerAccountProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($customerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountPortalEnableUpdater = new CustomerAccountPortalEnableUpdater($entityManager);
        $customerAccountPortalEnableUpdater->update($customerAccount, $comparingDate);
    }

    public function testNoContracts()
    {
        $comparingDate = new \DateTime('2019-01-01 T00:00:00.000Z');

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getContracts()->willReturn([]);
        $customerAccountProphecy->setCustomerPortalEnabled(false)->shouldBeCalled();
        $customerAccountProphecy->getCategories()->willReturn(['CUSTOMER']);
        $customerAccount = $customerAccountProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($customerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountPortalEnableUpdater = new CustomerAccountPortalEnableUpdater($entityManager);
        $customerAccountPortalEnableUpdater->update($customerAccount, $comparingDate);
    }

    public function testContractWithEndDateAndLockInDateIsNull()
    {
        $comparingDate = new \DateTime('2019-01-01 T00:00:00.000Z');
        $date = new \DateTime('2019-01-02 T00:00:00.000Z');

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('123');
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::ACTIVE));
        $contractProphecy->getEndDate()->willReturn(null);
        $activeContract = $contractProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getContracts()->willReturn([$activeContract]);
        $customerAccountProphecy->setCustomerPortalEnabled(true)->shouldBeCalled();
        $customerAccountProphecy->getCategories()->willReturn(['CUSTOMER']);
        $customerAccount = $customerAccountProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($customerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountPortalEnableUpdater = new CustomerAccountPortalEnableUpdater($entityManager);
        $customerAccountPortalEnableUpdater->update($customerAccount, $comparingDate);
    }

    public function testCustomerAccountIsContactPerson()
    {
        $comparingDate = new \DateTime('2019-01-01 T00:00:00.000Z');
        $date = new \DateTime('2019-01-02 T00:00:00.000Z');

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('123');
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::ACTIVE));
        $contractProphecy->getEndDate()->willReturn($date);
        $activeContract = $contractProphecy->reveal();

        $customerAccountRelationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $customerAccountRelationshipProphecy->getContracts()->willReturn([$activeContract]);
        $customerAccountRelationship = $customerAccountRelationshipProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getRelationships()->willReturn([$customerAccountRelationship]);
        $customerAccountProphecy->setCustomerPortalEnabled(true)->shouldBeCalled();
        $customerAccountProphecy->getCategories()->willReturn(['CONTACT_PERSON']);
        $customerAccount = $customerAccountProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($customerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountPortalEnableUpdater = new CustomerAccountPortalEnableUpdater($entityManager);
        $customerAccountPortalEnableUpdater->update($customerAccount, $comparingDate);
    }

    public function testCustomerAccountIsContactPersonNoContracts()
    {
        $comparingDate = new \DateTime('2019-01-01 T00:00:00.000Z');
        $date = new \DateTime('2019-01-02 T00:00:00.000Z');

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('123');
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::ACTIVE));
        $contractProphecy->getEndDate()->willReturn($date);
        $activeContract = $contractProphecy->reveal();

        $customerAccountRelationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $customerAccountRelationshipProphecy->getContracts()->willReturn([]);
        $customerAccountRelationship = $customerAccountRelationshipProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getRelationships()->willReturn([$customerAccountRelationship]);
        $customerAccountProphecy->setCustomerPortalEnabled(false)->shouldBeCalled();
        $customerAccountProphecy->getCategories()->willReturn(['CONTACT_PERSON']);
        $customerAccount = $customerAccountProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($customerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountPortalEnableUpdater = new CustomerAccountPortalEnableUpdater($entityManager);
        $customerAccountPortalEnableUpdater->update($customerAccount, $comparingDate);
    }

    public function testCustomerAccountIsContactPersonNoRelations()
    {
        $comparingDate = new \DateTime('2019-01-01 T00:00:00.000Z');

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getRelationships()->willReturn([]);
        $customerAccountProphecy->setCustomerPortalEnabled(false)->shouldBeCalled();
        $customerAccountProphecy->getCategories()->willReturn(['CONTACT_PERSON']);
        $customerAccount = $customerAccountProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($customerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountPortalEnableUpdater = new CustomerAccountPortalEnableUpdater($entityManager);
        $customerAccountPortalEnableUpdater->update($customerAccount, $comparingDate);
    }
}
