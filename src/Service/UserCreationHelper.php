<?php

declare(strict_types=1);

namespace App\Service;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountRelationship;
use App\Entity\User;
use App\Enum\IdentificationName;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;

class UserCreationHelper
{
    /**
     * @var DisqueQueue
     */
    private $disqueQueue;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @param DisqueQueue            $disqueQueue
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     */
    public function __construct(DisqueQueue $disqueQueue, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter)
    {
        $this->disqueQueue = $disqueQueue;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
    }

    public function createUser(?string $username, string $email, CustomerAccount $customerAccount): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail(\strtolower($email));
        $user->setCustomerAccount($customerAccount);

        $this->entityManager->persist($user);

        return $user;
    }

    public function isEmailMatch(CustomerAccount $customerAccount, string $email): bool
    {
        if (null !== $customerAccount->getPersonDetails()) {
            foreach ($customerAccount->getPersonDetails()->getContactPoints() as $contactPoint) {
                foreach ($contactPoint->getEmails() as $contactEmail) {
                    if (\strtolower($email) === \strtolower($contactEmail)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function isIdMatch(array $ids, string $idNumber, string $name): bool
    {
        $now = new \DateTime();
        foreach ($ids as $id) {
            if (\strtolower($idNumber) === \strtolower($id->getValue()) && $name === $id->getName()->getValue()) {
                if (null === $id->getValidFrom()) {
                    return true;
                }

                if ($id->getValidFrom() <= $now && $id->getValidThrough() >= $now) {
                    return true;
                }

                return false;
            }
        }

        return false;
    }

    public function isNRICMatch(CustomerAccount $customerAccount, string $nric): bool
    {
        $personDetails = $customerAccount->getPersonDetails();

        if (null !== $personDetails) {
            return $this->isIdMatch($personDetails->getIdentifiers(), $nric, IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
        }

        return false;
    }

    public function isUENMatch(CustomerAccount $customerAccount, string $uen): bool
    {
        $corporation = $customerAccount->getCorporationDetails();

        if (null !== $corporation) {
            return $this->isIdMatch($corporation->getIdentifiers(), $uen, IdentificationName::UNIQUE_ENTITY_NUMBER);
        }

        return false;
    }

    public function relationshipHasContract(CustomerAccountRelationship $relationship, Contract $contract): bool
    {
        foreach ($relationship->getContracts() as $relationshipContract) {
            if ($relationshipContract->getId() === $contract->getId()) {
                return true;
            }
        }

        return false;
    }

    public function queueWelcomeEmailJob(User $user, string $plainPassword): void
    {
        // not flushed yet
        if (null === $user->getId()) {
            throw new \Exception('Critical Error! User has not been created.');
        }

        $job = new DisqueJob([
            'data' => [
                'user' => $this->iriConverter->getIriFromItem($user),
            ],
            'type' => JobType::USER_CREATED,
            'user' => [
                '@id' => $this->iriConverter->getIriFromItem($user),
                'username' => $user->getUsername() ?? $user->getEmail(),
                'password' => $plainPassword,
            ],
        ]);
        $this->disqueQueue->push($job);
    }
}
