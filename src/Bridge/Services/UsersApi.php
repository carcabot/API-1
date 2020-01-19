<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 2/1/19
 * Time: 11:05 AM.
 */

namespace App\Bridge\Services;

use App\Document\Contract;
use App\Document\OldCustomerAccount;
use App\Entity\ApplicationRequest;
use App\Entity\BridgeUser;
use App\Entity\ContactPoint;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\Person;
use App\Entity\User;
use App\Enum\AccountType;
use App\Enum\CustomerAccountStatus;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberUtil as PhoneNumberUtil;
use Psr\Log\LoggerInterface;

final class UsersApi
{
    /**
     * @var CustomerApi
     */
    private $customerApi;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PhoneNumberUtil
     */
    private $phoneNumberUtils;

    /**
     * @param CustomerApi            $customerApi
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param PhoneNumberUtil        $phoneNumberUtils
     */
    public function __construct(CustomerApi $customerApi, DocumentManager $documentManager, EntityManagerInterface $entityManager, LoggerInterface $logger, PhoneNumberUtil $phoneNumberUtils)
    {
        $this->customerApi = $customerApi;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->phoneNumberUtils = $phoneNumberUtils;
    }

    /**
     * Create or update users into database.
     *
     * @param array $users
     *
     * @throws \Exception
     */
    public function createUser(array $users)
    {
        $batchSize = 1000;

        foreach ($users as $key => $userData) {
            if (empty($userData->getEmail())) {
                continue;
            }

            $user = $this->entityManager->getRepository(User::class)->findOneBy([
                'email' => \strtolower($userData->getEmail()),
            ]);

            if (null === $user) {
                $temporaryBridgeUser = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $userData->getId()]);
                if (null !== $temporaryBridgeUser) {
                    $user = $temporaryBridgeUser->getUser();
                }
            }

            if (false !== \stripos($userData->getLoginType(), 'ucrm')) {
                if (null !== $user) {
                    continue;
                }
            }

            if (null === $user) {
                $user = new User();
            }

            $bridgeUser = $user->getBridgeUser();

            if (null === $bridgeUser) {
                $bridgeUser = new BridgeUser();
                $bridgeUser->setUser($user);
            }

            if (!empty($userData->getAuthToken()) && null !== $userData->getAuthToken()) {
                $bridgeUser->setAuthToken($userData->getAuthToken());
            }

            if (null !== $userData->getId()) {
                $bridgeUser->setBridgeUserId($userData->getId());
            }

            if (null !== $userData->getEmail() && null === $user->getEmail()) {
                $user->setEmail(\strtolower($userData->getEmail()));
            }

            if (null !== $userData->getIsOnMobile()) {
                $user->setMobileDeviceLogin($userData->getIsOnMobile());
            }

            if (!empty($userData->getCreatedAt())) {
                try {
                    $user->setDateCreated($userData->getCreatedAt());
                } catch (\Exception $e) {
                    // do nothing
                }
            }

            if (!empty($userData->getUpdatedAt())) {
                try {
                    $user->setDateModified($userData->getUpdatedAt());
                } catch (\Exception $e) {
                    // do nothing
                }
            }

            if (!empty($userData->getLastLogin())) {
                try {
                    $user->setDateLastLogon($userData->getLastLogin());
                } catch (\Exception $e) {
                    // do nothing
                }
            }

            if (false !== \stripos($userData->getLoginType(), 'customer')) {
                $customerDocument = $this->documentManager->getRepository(OldCustomerAccount::class)->findOneBy(['id' => $userData->getCustomerId()]);
                if (null !== $customerDocument) {
                    /*if (null === $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerDocument->getCustomerId()])) {
                        $this->customerApi->createCustomer([$customerDocument]);
                    }*/
                    $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerDocument->getCustomerId()]);

                    if (null === $customer) {
                        if (null !== $customerDocument->getContactPerson()) {
                            if (null !== $customerDocument->getContactPerson()->getContract() && \count($customerDocument->getContactPerson()->getContract()) > 0) {
                                foreach ($customerDocument->getContactPerson()->getContract() as $contractId) {
                                    $contractDocument = $this->documentManager->getRepository(Contract::class)->findOneBy(['id' => $contractId]);
                                    if (null !== $contractDocument) {
                                        $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $contractDocument->getApplicationRequestNumber()]);
                                        if (null !== $applicationRequest) {
                                            if (null !== $applicationRequest->getCustomer()) {
                                                $customer = $applicationRequest->getCustomer();
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (null === $customer) {
                        $this->customerApi->createCustomer([$customerDocument]);
                        $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerDocument->getCustomerId()]);
                    }

                    if (null !== $customer) {
                        if (!empty($customer->getUser()) && null !== $customer->getUser()) {
                            $oldUser = $customer->getUser();

                            if ($oldUser->getEmail() !== $userData->getEmail()) {
                                if (false !== \stripos($userData->getCustomerType(), 'INDIVIDUAL')) {
                                    if (null !== $customer->getPersonDetails()) {
                                        if (!empty($customer->getPersonDetails()->getContactPoints()) && \count($customer->getPersonDetails()->getContactPoints()) > 0) {
                                            foreach ($customer->getPersonDetails()->getContactPoints() as $contactPoint) {
                                                if (!empty($contactPoint->getEmails()) && \count($contactPoint->getEmails()) > 0) {
                                                    foreach ($contactPoint->getEmails() as $email) {
                                                        if ($email === $oldUser->getEmail()) {
                                                            $user = $oldUser;
                                                        }
                                                    }
                                                } elseif ((!empty($oldUser->getDateLastLogon()) && null !== $oldUser->getDateLastLogon()) && !empty($userData->getLastLogin())) {
                                                    if ($oldUser->getDateLastLogon() > $userData->getLastLogin()) {
                                                        $user = $oldUser;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } elseif (false !== \stripos($userData->getCustomerType(), 'CORPORATE')) {
                                    if (null !== $customer->getCorporationDetails()) {
                                        if (!empty($customer->getCorporationDetails()->getContactPoints()) && \count($customer->getCorporationDetails()->getContactPoints()) > 0) {
                                            foreach ($customer->getCorporationDetails()->getContactPoints() as $contactPoint) {
                                                if (!empty($contactPoint->getEmails()) && \count($contactPoint->getEmails()) > 0) {
                                                    foreach ($contactPoint->getEmails() as $email) {
                                                        if ($email === $oldUser->getEmail()) {
                                                            $user = $oldUser;
                                                        }
                                                    }
                                                } elseif (!empty($oldUser->getDateLastLogon()) && null !== $oldUser->getDateLastLogon()) {
                                                    if ($oldUser->getDateLastLogon() > $userData->getLastLogin()) {
                                                        $user = $oldUser;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if (!empty($userData->getStatus()) && null !== $userData->getStatus()) {
                            $customer->setStatus($this->mapCustomerAccountStatus($userData->getStatus()));
                        }

                        if (false !== \stripos($userData->getCustomerType(), 'INDIVIDUAL')) {
                            $person = $customer->getPersonDetails();

                            if (null === $person) {
                                $person = new Person();
                                $customer->setPersonDetails($person);
                            }

                            if (!empty($userData->getFamilyName())) {
                                $person->setFamilyName($userData->getFamilyName());
                            }

                            if (!empty($userData->getGivenName())) {
                                $person->setGivenName($userData->getGivenName());
                            }

                            if (!empty($userData->getPreferredLanguage())) {
                                $person->setPreferredLanguage($userData->getPreferredLanguage());
                            }

                            if (!empty($person->getContactPoints()) && \count($person->getContactPoints()) > 0) {
                                foreach ($person->getContactPoints() as $contactPoints) {
                                    if (1 !== \count($person->getContactPoints())) {
                                        break;
                                    }
                                    $contactPoint = $contactPoints;

                                    if (!empty($userData->getMobileNumber()) && null !== $userData->getMobileNumber()) {
                                        if (null !== $userData->getMobileNumber()->getCountryCode() && null !== $userData->getMobileNumber()->getNumber()) {
                                            $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                                            $mobileNumber = $userData->getMobileNumber()->getCountryCode().$userData->getMobileNumber()->getNumber();
                                            try {
                                                $phoneNumberObject = $phoneNumberUtil->parse($mobileNumber);
                                                if (\count($contactPoint->getMobilePhoneNumbers()) > 0) {
                                                    foreach ($contactPoint->getMobilePhoneNumbers() as $mobilePhoneNumber) {
                                                        if ($phoneNumberObject->getNationalNumber() === $mobilePhoneNumber->getNationalNumber()) {
                                                            $contactPoint->removeMobilePhoneNumber($mobilePhoneNumber);
                                                        }
                                                    }
                                                    $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                                                } else {
                                                    $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                                                }
                                            } catch (\Exception $e) {
                                                $this->logger->info('Error while migrating mobile number of User: '.$e);
                                                $this->logger->info(\json_encode($userData, JSON_PRETTY_PRINT));
                                            }
                                        }
                                    }

                                    if (!empty($userData->getOfficeNumber()) && null !== $userData->getOfficeNumber()) {
                                        if (null !== $userData->getOfficeNumber()->getCountryCode() && null !== $userData->getOfficeNumber()->getNumber()) {
                                            $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                                            $officeNumber = $userData->getOfficeNumber()->getCountryCode().$userData->getOfficeNumber()->getNumber();
                                            try {
                                                $phoneNumberObject = $phoneNumberUtil->parse($officeNumber);
                                                if (\count($contactPoint->getTelephoneNumbers()) > 0) {
                                                    foreach ($contactPoint->getTelephoneNumbers() as $telephoneNumber) {
                                                        if ($phoneNumberObject->getNationalNumber() === $telephoneNumber->getNationalNumber()) {
                                                            $contactPoint->removeTelephoneNumber($telephoneNumber);
                                                        }
                                                    }
                                                    $contactPoint->addTelephoneNumber($phoneNumberObject);
                                                } else {
                                                    $contactPoint->addTelephoneNumber($phoneNumberObject);
                                                }
                                            } catch (\Exception $e) {
                                                $this->logger->info('Error while migrating telephone number of User: '.$e);
                                                $this->logger->info(\json_encode($userData, JSON_PRETTY_PRINT));
                                            }
                                        }
                                    }
                                    $person->removeContactPoint($contactPoints);
                                    $person->addContactPoint($contactPoint);
                                }
                            } else {
                                $contactPoint = new ContactPoint();

                                if (!empty($userData->getMobileNumber()) && null !== $userData->getMobileNumber()) {
                                    if (null !== $userData->getMobileNumber()->getCountryCode() && null !== $userData->getMobileNumber()->getNumber()) {
                                        $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                                        $mobileNumber = $userData->getMobileNumber()->getCountryCode().$userData->getMobileNumber()->getNumber();
                                        try {
                                            $phoneNumberObject = $phoneNumberUtil->parse($mobileNumber);
                                            $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                                        } catch (\Exception $e) {
                                            $this->logger->info('Error while migrating mobile number of User: '.$e);
                                            $this->logger->info(\json_encode($userData, JSON_PRETTY_PRINT));
                                        }
                                    }
                                }

                                if (!empty($userData->getOfficeNumber()) && null !== $userData->getOfficeNumber()) {
                                    if (null !== $userData->getOfficeNumber()->getCountryCode() && null !== $userData->getOfficeNumber()->getNumber()) {
                                        $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                                        $officeNumber = $userData->getOfficeNumber()->getCountryCode().$userData->getOfficeNumber()->getNumber();
                                        try {
                                            $phoneNumberObject = $phoneNumberUtil->parse($officeNumber);
                                            $contactPoint->addTelephoneNumber($phoneNumberObject);
                                        } catch (\Exception $e) {
                                            $this->logger->info('Error while migrating telephone number of User: '.$e);
                                            $this->logger->info(\json_encode($userData, JSON_PRETTY_PRINT));
                                        }
                                    }
                                }
                                $person->addContactPoint($contactPoint);
                            }
                        } elseif (false !== \stripos($userData->getCustomerType(), 'CORPORATE')) {
                            $corporate = $customer->getCorporationDetails();

                            if (null === $corporate) {
                                $corporate = new Corporation();
                                $customer->setCorporationDetails($corporate);
                            }

                            if (!empty($userData->getCompanyName())) {
                                $corporate->setName($userData->getCompanyName());
                            }

                            if (!empty($corporate->getContactPoints()) && \count($corporate->getContactPoints()) > 0) {
                                foreach ($corporate->getContactPoints() as $contactPoints) {
                                    if (1 !== \count($corporate->getContactPoints())) {
                                        break;
                                    }

                                    $contactPoint = $contactPoints;

                                    if (!empty($userData->getMobileNumber()) && null !== $userData->getMobileNumber()) {
                                        if (null !== $userData->getMobileNumber()->getCountryCode() && null !== $userData->getMobileNumber()->getNumber()) {
                                            $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                                            $mobileNumber = $userData->getMobileNumber()->getCountryCode().$userData->getMobileNumber()->getNumber();
                                            try {
                                                $phoneNumberObject = $phoneNumberUtil->parse($mobileNumber);
                                                if (\count($contactPoint->getMobilePhoneNumbers()) > 0) {
                                                    foreach ($contactPoint->getMobilePhoneNumbers() as $mobilePhoneNumber) {
                                                        if ($phoneNumberObject->getNationalNumber() === $mobilePhoneNumber->getNationalNumber()) {
                                                            $contactPoint->removeMobilePhoneNumber($mobilePhoneNumber);
                                                        }
                                                    }
                                                    $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                                                } else {
                                                    $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                                                }
                                            } catch (\Exception $e) {
                                                $this->logger->info('Error while migrating mobile number of User: '.$e);
                                                $this->logger->info(\json_encode($userData, JSON_PRETTY_PRINT));
                                            }
                                        }
                                    }

                                    if (!empty($userData->getOfficeNumber()) && null !== $userData->getOfficeNumber()) {
                                        if (null !== $userData->getOfficeNumber()->getCountryCode() && null !== $userData->getOfficeNumber()->getNumber()) {
                                            $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                                            $officeNumber = $userData->getOfficeNumber()->getCountryCode().$userData->getOfficeNumber()->getNumber();
                                            try {
                                                $phoneNumberObject = $phoneNumberUtil->parse($officeNumber);
                                                if (\count($contactPoint->getTelephoneNumbers()) > 0) {
                                                    foreach ($contactPoint->getTelephoneNumbers() as $telephoneNumber) {
                                                        if ($phoneNumberObject->getNationalNumber() === $telephoneNumber->getNationalNumber()) {
                                                            $contactPoint->removeTelephoneNumber($telephoneNumber);
                                                        }
                                                    }
                                                    $contactPoint->addTelephoneNumber($phoneNumberObject);
                                                } else {
                                                    $contactPoint->addTelephoneNumber($phoneNumberObject);
                                                }
                                            } catch (\Exception $e) {
                                                $this->logger->info('Error while migrating telephone number of User: '.$e);
                                                $this->logger->info(\json_encode($userData, JSON_PRETTY_PRINT));
                                            }
                                        }
                                    }
                                    $corporate->removeContactPoint($contactPoints);
                                    $corporate->addContactPoint($contactPoint);
                                }
                            } else {
                                $contactPoint = new ContactPoint();

                                if (!empty($userData->getMobileNumber()) && null !== $userData->getMobileNumber()) {
                                    if (null !== $userData->getMobileNumber()->getCountryCode() && null !== $userData->getMobileNumber()->getNumber()) {
                                        $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                                        $mobileNumber = $userData->getMobileNumber()->getCountryCode().$userData->getMobileNumber()->getNumber();
                                        try {
                                            $phoneNumberObject = $phoneNumberUtil->parse($mobileNumber);
                                            $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                                        } catch (\Exception $e) {
                                            $this->logger->info('Error while migrating mobile number of User: '.$e);
                                            $this->logger->info(\json_encode($userData, JSON_PRETTY_PRINT));
                                        }
                                    }
                                }

                                if (!empty($userData->getOfficeNumber()) && null !== $userData->getOfficeNumber()) {
                                    if (null !== $userData->getOfficeNumber()->getCountryCode() && null !== $userData->getOfficeNumber()->getNumber()) {
                                        $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                                        $officeNumber = $userData->getOfficeNumber()->getCountryCode().$userData->getOfficeNumber()->getNumber();
                                        try {
                                            $phoneNumberObject = $phoneNumberUtil->parse($officeNumber);
                                            $contactPoint->addTelephoneNumber($phoneNumberObject);
                                        } catch (\Exception $e) {
                                            $this->logger->info('Error while migrating telephone number of User: '.$e);
                                            $this->logger->info(\json_encode($userData, JSON_PRETTY_PRINT));
                                        }
                                    }
                                }
                                $corporate->addContactPoint($contactPoint);
                            }
                            $this->entityManager->persist($corporate);
                        }
                        $user->setCustomerAccount($customer);
                    }
                }
            } elseif (false !== \stripos($userData->getLoginType(), 'ucrm')) {
                $customer = new CustomerAccount();

                $customer->setAgent($user);

                $customer->setCreator($user);
                $customer->setType(new AccountType(AccountType::INDIVIDUAL));
                if (!empty($userData->getStatus()) && null !== $userData->getStatus()) {
                    $customer->setStatus($this->mapCustomerAccountStatus($userData->getStatus()));
                } else {
                    $customer->setStatus(new CustomerAccountStatus(CustomerAccountStatus::ACTIVE));
                }

                $person = $customer->getPersonDetails();

                if (null === $person) {
                    $person = new Person();
                    $customer->setPersonDetails($person);
                }

                if (!empty($userData->getGivenName())) {
                    $person->setGivenName($userData->getGivenName());
                }

                if (!empty($userData->getFamilyName())) {
                    $person->setFamilyName($userData->getFamilyName());
                }

                if (!empty($userData->getPreferredLanguage())) {
                    $person->setPreferredLanguage($userData->getPreferredLanguage());
                }

                if (null !== $userData->getGivenName() && null !== $userData->getFamilyName()) {
                    $person->setName($userData->getGivenName().' '.$userData->getFamilyName());
                } elseif (null !== $userData->getGivenName()) {
                    $person->setName($userData->getGivenName());
                } elseif (null !== $userData->getFamilyName()) {
                    $person->setName($userData->getFamilyName());
                }

                $contactPoint = new ContactPoint();

                if (null !== $userData->getEmail()) {
                    $contactPoint->addEmail($userData->getEmail());
                }

                if (!empty($userData->getMobileNumber()) && null !== $userData->getMobileNumber()) {
                    if (null !== $userData->getMobileNumber()->getCountryCode() && null !== $userData->getMobileNumber()->getNumber()) {
                        $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                        $mobileNumber = $userData->getMobileNumber()->getCountryCode().$userData->getMobileNumber()->getNumber();
                        try {
                            $phoneNumberObject = $phoneNumberUtil->parse($mobileNumber);
                            $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                        } catch (\Exception $e) {
                            $this->logger->info('Error while migrating mobile number of User: '.$e);
                            $this->logger->info(\json_encode($userData, JSON_PRETTY_PRINT));
                        }
                    }
                }

                if (!empty($userData->getOfficeNumber()) && null !== $userData->getOfficeNumber()) {
                    if (null !== $userData->getOfficeNumber()->getCountryCode() && null !== $userData->getOfficeNumber()->getNumber()) {
                        $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                        $officeNumber = $userData->getOfficeNumber()->getCountryCode().$userData->getOfficeNumber()->getNumber();
                        try {
                            $phoneNumberObject = $phoneNumberUtil->parse($officeNumber);
                            $contactPoint->addTelephoneNumber($phoneNumberObject);
                        } catch (\Exception $e) {
                            $this->logger->info('Error while migrating telephone number of User: '.$e);
                            $this->logger->info(\json_encode($userData, JSON_PRETTY_PRINT));
                        }
                    }
                }

                $person->addContactPoint($contactPoint);

                $user->addRole('ROLE_ADMIN');
                $user->setCustomerAccount($customer);
                $this->entityManager->persist($customer);
            }
            $this->entityManager->persist($bridgeUser);
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear(); // Detaches all objects from doctrine
            }
        }
        $this->entityManager->flush(); // Persist objects that did not make up an entire branch
        //remove for now
        //$this->entityManager->clear();
    }

    private function mapCustomerAccountStatus(string $status)
    {
        $typesMap = [
            'ACTIVE' => new CustomerAccountStatus(CustomerAccountStatus::ACTIVE),
            'INACTIVE' => new CustomerAccountStatus(CustomerAccountStatus::INACTIVE),
        ];

        return $typesMap[$status];
    }
}
