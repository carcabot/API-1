<?php

declare(strict_types=1);

namespace App\DataFixtures\Processor;

use App\DataFixtures\Faker\Provider\CorporationProvider;
use App\DataFixtures\Faker\Provider\LeadProvider;
use App\DataFixtures\Faker\Provider\PersonProvider;
use App\Entity\ContactPoint;
use App\Entity\Corporation;
use App\Entity\Identification;
use App\Entity\Lead;
use App\Entity\Person;
use App\Entity\RunningNumber;
use App\Enum\AccountType;
use App\Enum\ContractType;
use App\Enum\GenderType;
use App\Enum\IdentificationName;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Faker\Factory;
use Fidry\AliceDataFixtures\ProcessorInterface;
use libphonenumber\PhoneNumber;

class LeadProcessor extends Lead implements ProcessorInterface
{
    const LENGTH = 9;
    const PREFIX = 'L';
    const TYPE = 'lead';

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
        if (!$object instanceof Lead) {
            return;
        }
        $faker = Factory::create('en_SG');
        $corporationProvider = new CorporationProvider(Factory::create());
        $leadProvider = new LeadProvider(Factory::create());
        $personProvider = new PersonProvider(Factory::create());

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
        if (null !== $object->getContractType()) {
            if (ContractType::COMMERCIAL === $object->getContractType()->getValue()) {
                $object->contractSubtype = $corporationProvider->generateIndustry();
            } else {
                $object->contractSubtype = $leadProvider->generateDwellingType();
            }
        }
        if (AccountType::CORPORATE === $object->type->getValue()) {
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

            $object->corporationDetails = $corporation;
        }
        if (AccountType::INDIVIDUAL === $object->type->getValue()) {
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
                    $person->setName($faker->name('female'));
                    $person->setHonorificPrefix($faker->titleFemale);
                } else {
                    $person->setGivenName($faker->firstNameMale);
                    $person->setFamilyName($faker->lastName);
                    $person->setName($faker->name('male'));
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

            $object->personDetails = $person;
        }

        $object->leadNumber = $this->generate($object);
        $this->entityManager->flush();
    }

    public function postProcess(string $id, $object): void
    {
        // TODO: Implement postProcess() method.
    }

    private function generate(Lead $lead)
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
