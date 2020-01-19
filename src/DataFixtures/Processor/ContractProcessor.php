<?php

declare(strict_types=1);

namespace App\DataFixtures\Processor;

use App\DataFixtures\Faker\Provider\CorporationProvider;
use App\DataFixtures\Faker\Provider\CustomerAccountProvider;
use App\DataFixtures\Faker\Provider\LeadProvider;
use App\DataFixtures\Faker\Provider\PersonProvider;
use App\Entity\ContactPoint;
use App\Entity\Contract;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\Identification;
use App\Entity\Person;
use App\Entity\RunningNumber;
use App\Entity\TariffRate;
use App\Enum\AccountCategory;
use App\Enum\AccountType;
use App\Enum\ContractType;
use App\Enum\GenderType;
use App\Enum\IdentificationName;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Faker\Factory;
use Fidry\AliceDataFixtures\ProcessorInterface;
use libphonenumber\PhoneNumber;

class ContractProcessor extends Contract implements ProcessorInterface
{
    const LENGTH = 8;
    const PREFIX = 'CR';
    const TYPE = 'contract';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function preProcess(string $id, $object): void
    {
        $faker = Factory::create('en_SG');
        $customerAccountProvider = new CustomerAccountProvider(Factory::create());

        if (!$object instanceof Contract) {
            return;
        }

        $em = $this->entityManager;
        $expr = $em->getExpressionBuilder();

        $tariffRate = $em->getRepository(TariffRate::class)->createQueryBuilder('tr');
        $customerAccount = $em->getRepository(CustomerAccount::class)->createQueryBuilder('ca');
        $corporationProvider = new CorporationProvider(Factory::create());
        $customerAccountProvider = new CustomerAccountProvider(Factory::create());
        $leadProvider = new LeadProvider(Factory::create());
        $personProvider = new PersonProvider(Factory::create());

        $customerResult = null;
        $tariffRateResult = null;
        $contactPersonResult = null;

        $identification = new Identification();
        $identification->setValue($faker->isbn10);

        $phoneNumber = new PhoneNumber();
        $phoneNumber->setNationalNumber('+65'.\mt_rand(8, 9).\mt_rand(1000000, 9999999));

        $contactPoint = new ContactPoint();
        $contactPoint->setContactType($faker->company);
        $contactPoint->addTelephoneNumber($phoneNumber);
        $contactPoint->addMobilePhoneNumber($phoneNumber);
        $contactPoint->addFaxNumber($phoneNumber);
        $contactPoint->addEmail($faker->email);

        $tariffRateDESCId = $tariffRate
            ->select('DISTINCT tr.id')
            ->orderBy('tr.id', 'DESC')
            ->getQuery()
            ->getResult();

        $customerDESCId = $customerAccount
            ->select('ca.id')
            ->where(
                $expr->andX($expr->eq(<<<'SQL'
                    jsonb_contains(CAST(ca.categories AS jsonb), :category)
SQL
                , $expr->literal(true))))
            ->setParameter('category', \json_encode(AccountCategory::CUSTOMER))
            ->orderBy('ca.id', 'DESC')
            ->getQuery()
            ->getResult();
        $contactPersonDESCId = $customerAccount
            ->select('ca.id')
            ->where(
                $expr->andX($expr->eq(<<<'SQL'
                    jsonb_contains(CAST(ca.categories AS jsonb), :category)
SQL
                , $expr->literal(true))))
            ->setParameter('category', \json_encode(AccountCategory::CONTACT_PERSON))
            ->orderBy('ca.id', 'DESC')
            ->getQuery()
            ->getResult();
        if (\count($customerDESCId) > 0) {
            $randomCustomerId = $customerDESCId[\array_rand($customerDESCId)]['id'];
            $customerResult = $customerAccount->select('ca')
                ->where($customerAccount->expr()->eq('ca.id', ':id'))
                ->setParameters([
                    'id' => $randomCustomerId,
                ])
                ->getQuery()
                ->getOneOrNullResult(Query::HYDRATE_OBJECT);
        } else {
            $customerResult = new CustomerAccount();
            $customerResult->addCategory(AccountCategory::CUSTOMER);
            $customerResult->setType($customerAccountProvider->generateCustomerAccountType());
            $customerResult->setStatus($customerAccountProvider->generateCustomerAccountStatus());
            $this->entityManager->persist($customerResult);

            if (AccountType::CORPORATE === $customerResult->getType()->getValue()) {
                $corporation = new Corporation();
                $corporation->setName($faker->company);
                $corporation->setLegalName($faker->company);
                $corporation->setIndustry($corporationProvider->generateIndustry());
                $corporation->addSameAsUrl($faker->url);
                $corporation->setUrl($faker->domainName);
                $corporation->setFoundingDate($faker->dateTimeBetween($startDate = '-30 years', $endDate = 'now'));
                $corporation->addContactPoint($contactPoint);
                $corporation->setDescription($faker->sentence);
                $identification->setName(new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER));
                $corporation->addIdentifier($identification);
                $this->entityManager->persist($corporation);

                $customerResult->setCorporationDetails($corporation);
            } else {
                $person = new Person();
                $person->setBirthDate($faker->dateTimeBetween($startDate = '-40 years', $endDate = '-30 years'));
                $person->setBirthPlace($faker->city);
                $person->setCountryOfOrigin($faker->country);
                $person->addContactPoint($contactPoint);
                $person->setGender($personProvider->generatePersonGender());
                if (null !== $person->getGender()) {
                    if (GenderType::FEMALE === $person->getGender()->getValue()) {
                        $person->setGivenName($faker->firstNameFemale);
                        $person->setFamilyName($faker->lastName);
                        $person->setName(\sprintf('%s %s', $person->getGivenName(), $person->getFamilyName()));
                        $person->setHonorificPrefix($faker->titleFemale);
                    } else {
                        $person->setGivenName($faker->firstNameMale);
                        $person->setFamilyName($faker->lastName);
                        $person->setName(\sprintf('%s %s', $person->getGivenName(), $person->getFamilyName()));
                        $person->setHonorificPrefix($faker->titleMale);
                    }
                }
                $person->setJobTitle($faker->jobTitle);
                $person->setMaritalStatus($personProvider->generateMaritalStatus());
                $person->addSameAsUrl($faker->url);
                $person->setDescription($faker->sentence);
                $identification->setName(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
                $person->addIdentifier($identification);

                $this->entityManager->persist($person);

                $customerResult->setPersonDetails($person);
                $this->entityManager->persist($customerResult);
            }
        }

        if (AccountType::CORPORATE === $customerResult->getType()->getValue()) {
            if (\count($contactPersonDESCId) > 0) {
                $randomContactPersonId = $contactPersonDESCId[\array_rand($contactPersonDESCId)]['id'];
                $contactPersonResult = $customerAccount->select('ca')
                    ->where($customerAccount->expr()->eq('ca.id', ':id'))
                    ->setParameters([
                        'id' => $randomContactPersonId,
                    ])
                    ->getQuery()
                    ->getOneOrNullResult(Query::HYDRATE_OBJECT);
            } else {
                $contactPersonResult = new CustomerAccount();
                $contactPersonResult->addCategory(AccountCategory::CONTACT_PERSON);
                $contactPersonResult->setType(new AccountType(AccountType::INDIVIDUAL));
                $contactPersonResult->setStatus($customerAccountProvider->generateCustomerAccountStatus());

                $person = new Person();
                $person->setBirthDate($faker->dateTimeBetween($startDate = '-40 years', $endDate = '-30 years'));
                $person->setBirthPlace($faker->city);
                $person->setCountryOfOrigin($faker->country);
                $person->addContactPoint($contactPoint);
                $person->setGender($personProvider->generatePersonGender());
                if (null !== $person->getGender()) {
                    if (GenderType::FEMALE === $person->getGender()->getValue()) {
                        $person->setGivenName($faker->firstNameFemale);
                        $person->setFamilyName($faker->lastName);
                        $person->setName(\sprintf('%s %s', $person->getGivenName(), $person->getFamilyName()));
                        $person->setHonorificPrefix($faker->titleFemale);
                    } else {
                        $person->setGivenName($faker->firstNameMale);
                        $person->setFamilyName($faker->lastName);
                        $person->setName(\sprintf('%s %s', $person->getGivenName(), $person->getFamilyName()));
                        $person->setHonorificPrefix($faker->titleMale);
                    }
                }
                $person->setJobTitle($faker->jobTitle);
                $person->setMaritalStatus($personProvider->generateMaritalStatus());
                $person->addSameAsUrl($faker->url);
                $person->setDescription($faker->sentence);
                $identification->setName(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
                $person->addIdentifier($identification);

                $this->entityManager->persist($person);

                $contactPersonResult->setPersonDetails($person);
                $this->entityManager->persist($contactPersonResult);
            }
        } else {
            $contactPersonResult = $customerResult;
        }

        if (\count($tariffRateDESCId) > 0) {
            $randomTariffRateId = $tariffRateDESCId[\array_rand($tariffRateDESCId)]['id'];
            $tariffRateResult = $tariffRate->select('tr')
                ->where($tariffRate->expr()->eq('tr.id', ':id'))
                ->setParameters([
                    'id' => $randomTariffRateId,
                ])
                ->getQuery()
                ->getOneOrNullResult(Query::HYDRATE_OBJECT);
        }

        $object->customer = $customerResult;
        $object->customerType = $customerResult->getType();

        if (AccountType::INDIVIDUAL === $customerResult->getType()->getValue()) {
            $object->personDetails = clone $customerResult->getPersonDetails();
        } else {
            $object->corporationDetails = clone $customerResult->getCorporationDetails();
        }

        $object->contactPerson = $contactPersonResult;
        $object->tariffRate = $tariffRateResult;

        if (ContractType::COMMERCIAL === $object->getType()->getValue()) {
            $object->subtype = $corporationProvider->generateIndustry();
        } else {
            $object->subtype = $leadProvider->generateDwellingType();
        }

        $object->ebsAccountNumber = \mt_rand(1000000000, 9200000000);
        $object->msslAccountNumber = \mt_rand(9300000000, 9399999999);
        $object->contractNumber = $this->generate($object);
        $this->entityManager->flush();
    }

    public function postProcess(string $id, $object): void
    {
        // TODO: Implement postProcess() method.
    }

    private function generate(Contract $contract)
    {
        $nextNumber = $this->getNextNumber(self::TYPE, (string) self::LENGTH);
        $leadNumber = \sprintf('%s%s', self::PREFIX, \str_pad((string) $nextNumber, self::LENGTH, '0', STR_PAD_LEFT));

        return $leadNumber;
    }

    private function getNextNumber(string $type, string $series)
    {
        $em = $this->entityManager;

        $runningNo = $em->getRepository(RunningNumber::class)->createQueryBuilder('rn');

        $runningNo = $runningNo->select('rn')
            ->where($runningNo->expr()->eq('rn.type', ':type'))
            ->andWhere($runningNo->expr()->eq('rn.series', ':series'))
            ->setParameters([
                'type' => $type,
                'series' => $series,
            ])
            ->getQuery()
            ->getOneOrNullResult(Query::HYDRATE_OBJECT);

        if (null !== $runningNo) {
            $seq = $runningNo->getNumber() + 1;
            $runningNo->setNumber($seq);
        } else {
            $runningNo = new RunningNumber();
            $runningNo->setType($type);
            $runningNo->setSeries($series);
            $runningNo->setNumber(1);
        }

        $em->persist($runningNo);

        return $runningNo->getNumber();
    }
}
