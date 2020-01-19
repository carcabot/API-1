<?php

declare(strict_types=1);

namespace App\Tests\Service;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\ContactPoint;
use App\Entity\Contract;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountRelationship;
use App\Entity\Identification;
use App\Entity\Person;
use App\Entity\User;
use App\Enum\IdentificationName;
use App\Service\UserCreationHelper;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UserCreationHelperTest extends TestCase
{
    public function testCreateUser()
    {
        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccount = $customerAccountProphecy->reveal();

        $user = new User();
        $user->setUsername('testUsername');
        $user->setEmail(\strtolower('email@test.com'));
        $user->setCustomerAccount($customerAccount);

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($user)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $actualUser = $userCreationHelper->createUser('testUsername', 'email@test.com', $customerAccount);

        $this->assertEquals($user, $actualUser);
    }

    public function testIsEmailMatchWithCorrectEmail()
    {
        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['email@test.com']);
        $contactPoint = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getContactPoints()->willReturn([$contactPoint]);
        $person = $personProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getPersonDetails()->willReturn($person);
        $customerAccount = $customerAccountProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $actualData = $userCreationHelper->isEmailMatch($customerAccount, 'email@test.com');

        $this->assertTrue($actualData);
    }

    public function testIsEmailMatchWithInCorrectEmail()
    {
        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['email@test.com']);
        $contactPoint = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getContactPoints()->willReturn([$contactPoint]);
        $person = $personProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getPersonDetails()->willReturn($person);
        $customerAccount = $customerAccountProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $actualData = $userCreationHelper->isEmailMatch($customerAccount, 'test@test.com');

        $this->assertFalse($actualData);
    }

    public function testIsNRICMatchWithCorrectValuesAndNoValidFrom()
    {
        $identifierProphecy = $this->prophesize(Identification::class);
        $identifierProphecy->getValue()->willReturn('nric123456');
        $identifierProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identifierProphecy->getValidFrom()->willReturn(null);
        $identifier = $identifierProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getIdentifiers()->willReturn([$identifier]);
        $person = $personProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getPersonDetails()->willReturn($person);
        $customerAccount = $customerAccountProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $actualData = $userCreationHelper->isNRICMatch($customerAccount, 'nric123456');

        $this->assertTrue($actualData);
    }

    public function testIsNRICMatchWithCorrectValues()
    {
        $identifierProphecy = $this->prophesize(Identification::class);
        $identifierProphecy->getValue()->willReturn('nric123456');
        $identifierProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identifierProphecy->getValidFrom()->willReturn(new \DateTime('2017-02-20'));
        $identifierProphecy->getValidThrough()->willReturn(new \DateTime('2020-02-20'));
        $identifier = $identifierProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getIdentifiers()->willReturn([$identifier]);
        $person = $personProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getPersonDetails()->willReturn($person);
        $customerAccount = $customerAccountProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $actualData = $userCreationHelper->isNRICMatch($customerAccount, 'nric123456');

        $this->assertTrue($actualData);
    }

    public function testIsNRICMatchWithoutPersonDetails()
    {
        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getPersonDetails()->willReturn(null);
        $customerAccount = $customerAccountProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $actualData = $userCreationHelper->isNRICMatch($customerAccount, 'nric123456');

        $this->assertFalse($actualData);
    }

    public function testIsUENMatchWithDifferentIdNumber()
    {
        $identifierProphecy = $this->prophesize(Identification::class);
        $identifierProphecy->getValue()->willReturn('uen123456');
        $identifierProphecy->getName()->willReturn(new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER));
        $identifier = $identifierProphecy->reveal();

        $corporationProphecy = $this->prophesize(Corporation::class);
        $corporationProphecy->getIdentifiers()->willReturn([$identifier]);
        $corporation = $corporationProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporation);
        $customerAccount = $customerAccountProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $actualData = $userCreationHelper->isUENMatch($customerAccount, '123456');

        $this->assertFalse($actualData);
    }

    public function testIsUENMatchWithoutCorporation()
    {
        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCorporationDetails()->willReturn(null);
        $customerAccount = $customerAccountProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $actualData = $userCreationHelper->isUENMatch($customerAccount, '123456');

        $this->assertFalse($actualData);
    }

    public function testIsUENMatchWithValidThroughExpired()
    {
        $identifierProphecy = $this->prophesize(Identification::class);
        $identifierProphecy->getValue()->willReturn('uen123456');
        $identifierProphecy->getName()->willReturn(new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER));
        $identifierProphecy->getValidFrom()->willReturn(new \DateTime('2017-02-20'));
        $identifierProphecy->getValidThrough()->willReturn(new \DateTime('2017-08-20'));
        $identifier = $identifierProphecy->reveal();

        $corporationProphecy = $this->prophesize(Corporation::class);
        $corporationProphecy->getIdentifiers()->willReturn([$identifier]);
        $corporation = $corporationProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporation);
        $customerAccount = $customerAccountProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $actualData = $userCreationHelper->isUENMatch($customerAccount, 'uen123456');

        $this->assertFalse($actualData);
    }

    public function testRelationshipHasContract()
    {
        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getId()->willReturn(123456);
        $contract = $contractProphecy->reveal();

        $customerContractProphecy = $this->prophesize(Contract::class);
        $customerContractProphecy->getId()->willReturn(123456);
        $customerContract = $customerContractProphecy->reveal();

        $customerRelationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $customerRelationshipProphecy->getContracts()->willReturn([$customerContract]);
        $customerRelationship = $customerRelationshipProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $actualData = $userCreationHelper->relationshipHasContract($customerRelationship, $contract);

        $this->assertTrue($actualData);
    }

    public function testRelationshipHasContractWithNotSameIds()
    {
        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getId()->willReturn(123456);
        $contract = $contractProphecy->reveal();

        $customerContractProphecy = $this->prophesize(Contract::class);
        $customerContractProphecy->getId()->willReturn(23456);
        $customerContract = $customerContractProphecy->reveal();

        $customerRelationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $customerRelationshipProphecy->getContracts()->willReturn([$customerContract]);
        $customerRelationship = $customerRelationshipProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $actualData = $userCreationHelper->relationshipHasContract($customerRelationship, $contract);

        $this->assertFalse($actualData);
    }

    public function testWelcomeEmailJob()
    {
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn(123456);
        $userProphecy->getUsername()->willReturn('testUsername');
        $user = $userProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($user)->willReturn('testIri');
        $iriConverter = $iriConverterProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueueProphecy->push(new DisqueJob([
            'data' => [
                'user' => 'testIri',
            ],
            'type' => JobType::USER_CREATED,
            'user' => [
                '@id' => 'testIri',
                'username' => 'testUsername',
                'password' => 'testPassword',
            ],
        ]))->shouldBeCalled();
        $disqueQueue = $disqueQueueProphecy->reveal();

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $userCreationHelper->queueWelcomeEmailJob($user, 'testPassword');
    }

    public function testWelcomeEmailJobWithoutUserId()
    {
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getId()->willReturn(null);
        $user = $userProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Critical Error! User has not been created.');

        $userCreationHelper = new UserCreationHelper($disqueQueue, $entityManager, $iriConverter);
        $userCreationHelper->queueWelcomeEmailJob($user, 'testPassword');
    }
}
