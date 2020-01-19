<?php

declare(strict_types=1);

namespace App\DataFixtures\Processor;

use App\DataFixtures\Faker\Provider\CorporationProvider;
use App\DataFixtures\Faker\Provider\PartnerProvider;
use App\DataFixtures\Faker\Provider\PersonProvider;
use App\Entity\ContactPoint;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\Identification;
use App\Entity\Partner;
use App\Entity\Person;
use App\Entity\RunningNumber;
use App\Enum\AccountCategory;
use App\Enum\AccountType;
use App\Enum\GenderType;
use App\Enum\IdentificationName;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Faker\Factory;
use Fidry\AliceDataFixtures\ProcessorInterface;
use libphonenumber\PhoneNumber;

class CustomerAccountProcessor extends CustomerAccount implements ProcessorInterface
{
    const LENGTH = 8;
    const CUSTOMER_PREFIX = 'CC';
    const CUSTOMER_TYPE = 'customer_account';
    const USER_PREFIX = 'UU';
    const USER_TYPE = 'user_account';
    const PARTNER_PREFIX = 'PP';
    const PARTNER_TYPE = 'partner';
    const PARTNER_CONTACT_PERSON_PREFIX = 'PC';
    const PARTNER_CONTACT_PERSON_TYPE = 'partner_contact_person';
    const PARTNER_NUMBER_PREFIX = 'PS';
    const SERIAL_NUMBER_LENGTH = 8;
    const SERIAL_NUMBER_SERIES = 'ym';

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
        if (!$object instanceof CustomerAccount) {
            return;
        }

        $faker = Factory::create('en_SG');
        $corporationProvider = new CorporationProvider(Factory::create());
        $partnerProvider = new PartnerProvider(Factory::create());
        $personProvider = new PersonProvider(Factory::create());

        $identification = new Identification();
        $identification->setDescription($faker->sentence);
        $identification->setValue($faker->isbn10);

        $phoneNumber = new PhoneNumber();
        $phoneNumber->setNationalNumber('+65'.\mt_rand(8, 9).\mt_rand(1000000, 9999999));

        $contactPoint = new ContactPoint();
        $contactPoint->setContactType($faker->company);
        $contactPoint->addTelephoneNumber($phoneNumber);
        $contactPoint->addMobilePhoneNumber($phoneNumber);
        $contactPoint->addFaxNumber($phoneNumber);
        $contactPoint->addEmail($faker->email);

        if (AccountType::CORPORATE === $object->type->getValue()) {
            $object->personDetails = null;
            if (true !== \in_array(AccountCategory::PARTNER, $object->categories, true) &&
                true !== \in_array(AccountCategory::PARTNER_CONTACT_PERSON, $object->categories, true)) {
                $object->partnerDetails = null;
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
            } else {
                $object->corporationDetails = null;

                $partner = new Partner();
                $partner->setAdministrator($object->getUser());
                $partner->addAnnouncementAudience($partnerProvider->generateAnnouncementAudience());
                $partner->setPayoutCycle($partnerProvider->generatePayoutCycle());
                $this->entityManager->persist($partner);

                $object->partnerDetails = $partner;
            }
        }
        if (AccountType::INDIVIDUAL === $object->type->getValue()) {
            $object->corporationDetails = null;
            if (true !== \in_array(AccountCategory::PARTNER, $object->categories, true) &&
                true !== \in_array(AccountCategory::PARTNER_CONTACT_PERSON, $object->categories, true)) {
                $object->partnerDetails = null;
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
            } else {
                $object->personDetails = null;

                $partner = new Partner();
                $partner->setAdministrator($object->getUser());
                $partner->addAnnouncementAudience($partnerProvider->generateAnnouncementAudience());
                $partner->setPayoutCycle($partnerProvider->generatePayoutCycle());
                $this->entityManager->persist($partner);

                $object->partnerDetails = $partner;
            }
        }

        $object->accountNumber = $this->generate($object);
        $this->entityManager->flush();
    }

    public function postProcess(string $id, $object): void
    {
        // TODO: Implement postProcess() method.
    }

    private function generate(CustomerAccount $customerAccount)
    {
        $prefix = self::USER_PREFIX;
        $length = self::LENGTH;
        $series = $length;

        $accountCategories = $customerAccount->getCategories();

        if (0 !== \count($accountCategories)) {
            $prefix = self::CUSTOMER_PREFIX;
            $type = self::CUSTOMER_TYPE;
            $series = $length;

            // hack for shit requirement
            if (\in_array(AccountCategory::SALES_REPRESENTATIVE, $accountCategories, true)) {
                return null;
            }

            if (\in_array(AccountCategory::PARTNER, $accountCategories, true)) {
                if (!empty(self::PARTNER_NUMBER_PREFIX)) {
                    $prefix = self::PARTNER_NUMBER_PREFIX;
                } else {
                    $prefix = self::PARTNER_PREFIX;
                }
            }

            // hack for shit requirement
            if (\in_array(AccountCategory::PARTNER_CONTACT_PERSON, $accountCategories, true)) {
                $prefix = self::PARTNER_CONTACT_PERSON_PREFIX;
            }
        }

        if (!empty(self::SERIAL_NUMBER_LENGTH)) {
            $length = self::SERIAL_NUMBER_LENGTH;
        }

        if (!empty(self::SERIAL_NUMBER_SERIES)) {
            $prefixDateSuffixFormat = self::SERIAL_NUMBER_SERIES;
            $now = new \DateTime();
            $series = $now->format($prefixDateSuffixFormat);
        }

        if ($series === $length) {
            $numberPrefix = $prefix;
        } else {
            $numberPrefix = $prefix.$series;
        }

        // should use $series, but noob client don't want to reset each month
        $nextNumber = $this->getNextNumber($prefix, $prefix);
        $customerAccountNumber = \sprintf('%s%s', $numberPrefix, \str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT));

        return $customerAccountNumber;
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
