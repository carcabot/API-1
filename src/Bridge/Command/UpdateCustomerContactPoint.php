<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 12/3/19
 * Time: 4:08 PM.
 */

namespace App\Bridge\Command;

use App\Disque\JobType;
use App\Entity\CustomerAccount;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCustomerContactPoint extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DisqueQueue
     */
    private $migrationQueue;

    /**
     * @var PhoneNumberUtil
     */
    private $phoneNumberUtil;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DisqueQueue            $migrationQueue
     * @param PhoneNumberUtil        $phoneNumberUtil
     * @param string                 $timezone
     */
    public function __construct(EntityManagerInterface $entityManager, DisqueQueue $migrationQueue, PhoneNumberUtil $phoneNumberUtil, string $timezone)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->migrationQueue = $migrationQueue;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:update-customer-contact-point')
            ->addOption('noqueue', null, InputOption::VALUE_NONE, 'Dont queue the job to run.')
            ->addOption('modifiedBefore', null, InputOption::VALUE_OPTIONAL, 'Date/Time', null)
            ->setDescription('Updating customer contact points to remove data redundancy in customer emails.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $dateString = $input->getOption('modifiedBefore');

        $date = new \DateTime('now', $this->timezone);
        $date->setTime(0, 0, 0);

        if (null !== $dateString) {
            $date = new \DateTime($dateString, $this->timezone);
        }
        $date->setTimezone(new \DateTimeZone('UTC'));

        $io->text('Checking customer accounts ..... ');
        $count = 0;
        $ids = [];
        $existing = [];
        $table = [];

        $getEmails = function ($contactPoint) {
            return \implode('||', $contactPoint->getEmails());
        };

        $getPhoneNumbers = function ($contactPoint) {
            $numbers = [];
            foreach ($contactPoint->getTelephoneNumbers() as $telephoneNumber) {
                $numbers[] = $this->phoneNumberUtil->format($telephoneNumber, PhoneNumberFormat::E164);
            }

            return \implode('||', $numbers);
        };

        $getMobileNumbers = function ($contactPoint) {
            $numbers = [];
            foreach ($contactPoint->getMobilePhoneNumbers() as $telephoneNumber) {
                $numbers[] = $this->phoneNumberUtil->format($telephoneNumber, PhoneNumberFormat::E164);
            }

            return \implode('||', $numbers);
        };

        $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customer');

        $customers = $qb->join('customer.personDetails', 'person')
            ->join('person.contactPoints', 'contactPoint')
            ->where($qb->expr()->lte('customer.dateModified', $qb->expr()->literal($date->format('c'))))
            ->groupBy('customer.id')
            ->addGroupBy('contactPoint.emails')
            ->having($qb->expr()->gt('jsonb_array_length(contactPoint.emails)', 1))
            ->getQuery()
            ->getResult();

        foreach ($customers as $customer) {
            ++$count;
            $existing[] = $customer->getId();

            $table[] = [
                $customer->getAccountNumber(),
                $customer->getPersonDetails()->getName(),
                $customer->getPersonDetails()->getContactPoints()[0]->getId(),
                $getEmails($customer->getPersonDetails()->getContactPoints()[0]),
                $getPhoneNumbers($customer->getPersonDetails()->getContactPoints()[0]),
                $getMobileNumbers($customer->getPersonDetails()->getContactPoints()[0]),
            ];

            if (false === $input->getOption('noqueue')) {
                $this->migrationQueue->push(new DisqueJob([
                    'data' => [
                        'id' => $customer->getId(),
                        'count' => $count,
                    ],
                    'type' => JobType::CLEAN_CUSTOMER_CONTACT_POINT,
                ]));
            }
        }

        $this->entityManager->clear();

        $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customer');

        $customers = $qb->join('customer.personDetails', 'person')
            ->join('person.contactPoints', 'contactPoint')
            ->join('contactPoint.mobilePhoneNumbers', 'contactPointMobile')
            ->where($qb->expr()->notIn('customer.id', $existing))
            ->andWhere($qb->expr()->lte('customer.dateModified', $qb->expr()->literal($date->format('c'))))
            ->groupBy('customer.id')
            ->having($qb->expr()->gt('count(contactPointMobile)', 1))
            ->getQuery()
            ->getResult();

        foreach ($customers as $customer) {
            ++$count;
            $existing[] = $customer->getId();

            $table[] = [
                $customer->getAccountNumber(),
                $customer->getPersonDetails()->getName(),
                $customer->getPersonDetails()->getContactPoints()[0]->getId(),
                $getEmails($customer->getPersonDetails()->getContactPoints()[0]),
                $getPhoneNumbers($customer->getPersonDetails()->getContactPoints()[0]),
                $getMobileNumbers($customer->getPersonDetails()->getContactPoints()[0]),
            ];

            if (false === $input->getOption('noqueue')) {
                $this->migrationQueue->push(new DisqueJob([
                    'data' => [
                        'id' => $customer->getId(),
                        'count' => $count,
                    ],
                    'type' => JobType::CLEAN_CUSTOMER_CONTACT_POINT,
                ]));
            }
        }

        $this->entityManager->clear();

        $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customer');

        $customers = $qb->join('customer.personDetails', 'person')
            ->join('person.contactPoints', 'contactPoint')
            ->join('contactPoint.telephoneNumbers', 'contactPointTelephone')
            ->where($qb->expr()->notIn('customer.id', $existing))
            ->andWhere($qb->expr()->lte('customer.dateModified', $qb->expr()->literal($date->format('c'))))
            ->groupBy('customer.id')
            ->having($qb->expr()->gt('count(contactPointTelephone)', 1))
            ->getQuery()
            ->getResult();

        foreach ($customers as $customer) {
            ++$count;

            $table[] = [
                $customer->getAccountNumber(),
                $customer->getPersonDetails()->getName(),
                $customer->getPersonDetails()->getContactPoints()[0]->getId(),
                $getEmails($customer->getPersonDetails()->getContactPoints()[0]),
                $getPhoneNumbers($customer->getPersonDetails()->getContactPoints()[0]),
                $getMobileNumbers($customer->getPersonDetails()->getContactPoints()[0]),
            ];
            if (false === $input->getOption('noqueue')) {
                $this->migrationQueue->push(new DisqueJob([
                    'data' => [
                        'id' => $customer->getId(),
                        'count' => $count,
                    ],
                    'type' => JobType::CLEAN_CUSTOMER_CONTACT_POINT,
                ]));
            }
        }

        $io->table(['Account Number', 'Name', 'Contact ID', 'Emails', 'Phone Numbers', 'Mobile Numbers'], $table);
        if (false === $input->getOption('noqueue')) {
            $io->success(\sprintf('Queued %s customers\' contact points clean up jobs.', $count));
        } else {
            $io->success(\sprintf('Found %s customers\' contact points to clean up.', $count));
        }

        return 0;
    }
}
