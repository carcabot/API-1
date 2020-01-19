<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 3/1/19
 * Time: 5:04 PM.
 */

namespace App\Bridge\Services;

use App\Document\Contract;
use App\Document\OldCustomerAccount;
use App\Document\OldCustomerIds;
use App\Document\OldUsers;
use App\Entity\ApplicationRequest;
use App\Entity\BridgeUser;
use App\Entity\ContactPoint;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountPostalAddress;
use App\Entity\CustomerAccountRelationship;
use App\Entity\DigitalDocument;
use App\Entity\EmailActivity;
use App\Entity\Identification;
use App\Entity\Note;
use App\Entity\Person;
use App\Entity\PhoneContactActivity;
use App\Entity\PostalAddress;
use App\Entity\RunningNumber;
use App\Entity\User;
use App\Enum\AccountType;
use App\Enum\CallDirection;
use App\Enum\ContactMethod;
use App\Enum\CustomerAccountStatus;
use App\Enum\CustomerRelationshipType;
use App\Enum\GenderType;
use App\Enum\IdentificationName;
use App\Enum\Industry;
use App\Enum\MaritalStatus;
use App\Enum\NoteType;
use App\Enum\PostalAddressType;
use App\Enum\Source;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use GuzzleHttp\Client as GuzzleClient;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use libphonenumber\PhoneNumberUtil;
use Psr\Log\LoggerInterface;

final class CustomerApi
{
    /**
     * @var string
     */
    private $bridgeApiUrl;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

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
    private $phoneNumberUtil;

    /**
     * @param string                 $bridgeApiUrl
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param PhoneNumberUtil        $phoneNumberUtil
     */
    public function __construct(string $bridgeApiUrl, DocumentManager $documentManager, EntityManagerInterface $entityManager, LoggerInterface $logger, PhoneNumberUtil $phoneNumberUtil)
    {
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->baseUri = HttpUri::createFromString($this->bridgeApiUrl);
        $this->client = new GuzzleClient();
        $this->logger = $logger;
        $this->phoneNumberUtil = $phoneNumberUtil;
    }

    /**
     * Create customer ids into database.
     *
     * @param OldCustomerIds $customerId
     */
    public function createIds(OldCustomerIds $customerId)
    {
        if (true === $customerId->getisActive()) {
            $runningNumber = new RunningNumber();

            if (!empty($customerId->getNextNumber()) && null !== $customerId->getNextNumber()) {
                $runningNumber->setNumber($customerId->getNextNumber() - 1);
            }

            if (!empty($customerId->getDatePrefix()) && false !== $customerId->getDatePrefix()) {
                $runningNumber->setSeries('ym');
            }
            $runningNumber->setType('SWCC');

            $this->entityManager->persist($runningNumber);
            $this->entityManager->flush();
        }
    }

    /**
     * Create customers into database.
     *
     * @param array $customers
     *
     * @throws \Exception
     */
    public function createCustomer(array $customers)
    {
        $batchSize = 800;

        foreach ($customers as $key => $customerData) {
            $existingCustomer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()]);

            if (null !== $existingCustomer) {
                continue;
            }
            $customer = new CustomerAccount();
            $customer->setAccountNumber($customerData->getCustomerId());

            if (null !== $customerData->getCategory()) {
                $customer->setType($this->mapCustomerAccountType($customerData->getCategory()));
            }

            if (!empty($customerData->getExternalCustomerId())) {
                $customer->setExternalCustomerNumber($customerData->getExternalCustomerId());
            }

            if (!empty($customerData->getReferralCode())) {
                $customer->setReferralCode($customerData->getReferralCode());
            }

            if (null !== $customerData->getStatus()) {
                $customer->setStatus($this->mapCustomerAccountStatus($customerData->getStatus()));
            }

            if (!empty($customerData->getSource())) {
                $customer->setSource($this->mapSource($customerData->getSource()));
            }

            if (null !== $customerData->getType() && \count($customerData->getType()) > 0) {
                foreach ($customerData->getType() as $type) {
                    $customer->addCategory($type);
                }
            }

            if (null !== $customerData->getContactPerson()) {
                if (false !== \stripos($customerData->getCategory(), 'INDIVIDUAL')) {
                    $person = $customer->getPersonDetails();

                    if (null === $person) {
                        $person = new Person();
                        $customer->setPersonDetails($person);
                    }

                    if (!empty($customerData->getContactPerson()->getAdditionalName())) {
                        $person->setAdditionalName($customerData->getContactPerson()->getAdditionalName());
                    }

                    if (!empty($customerData->getContactPerson()->getAlternateName())) {
                        $person->setAlternateName($customerData->getContactPerson()->getAlternateName());
                    }

                    if (!empty($customerData->getContactPerson()->getDateOfBirth())) {
                        $person->setBirthDate($customerData->getContactPerson()->getDateOfBirth());
                    }

                    if (!empty($customerData->getContactPerson()->getDateOfDeath()) && null !== $customerData->getContactPerson()->getDateOfDeath()) {
                        $person->setDeathDate($customerData->getContactPerson()->getDateOfDeath());
                    }

                    if (!empty($customerData->getContactPerson()->getDesignation()) && null !== $customerData->getContactPerson()->getDesignation()) {
                        $person->setJobTitle($customerData->getContactPerson()->getDesignation());
                    }

                    if (!empty($customerData->getContactPerson()->getCountryOrigin()) && null !== $customerData->getContactPerson()->getCountryOrigin()) {
                        $person->setCountryOfOrigin($customerData->getContactPerson()->getCountryOrigin());
                    }

                    if (!empty($customerData->getContactPerson()->getFamilyName())) {
                        $person->setFamilyName($customerData->getContactPerson()->getFamilyName());
                    }

                    if (!empty($customerData->getContactPerson()->getGivenName())) {
                        $person->setGivenName($customerData->getContactPerson()->getGivenName());
                    }

                    if (!empty($customerData->getContactPerson()->getGender()) && null !== $customerData->getContactPerson()->getGender()) {
                        $person->setGender($this->mapGender($customerData->getContactPerson()->getGender()));
                    }

                    if (!empty($customerData->getContactPerson()->getHonorificPrefix())) {
                        $person->setHonorificPrefix($customerData->getContactPerson()->getHonorificPrefix());
                    }

                    if (!empty($customerData->getContactPerson()->getMartialStatus()) && null !== $customerData->getContactPerson()->getMartialStatus()) {
                        $person->setMaritalStatus($this->mapMaritalStatus($customerData->getContactPerson()->getMartialStatus()));
                    }

                    if (!empty($customerData->getContactPerson()->getName())) {
                        $person->setName($customerData->getContactPerson()->getName());
                    }

                    if (!empty($customerData->getContactPerson()->getNationality())) {
                        $person->setNationality($customerData->getContactPerson()->getNationality());
                    }

                    if (!empty($customerData->getContactPerson()->getPlaceOfBirth())) {
                        $person->setBirthPlace($customerData->getContactPerson()->getPlaceOfBirth());
                    }

                    if (null !== $customerData->getContactPerson()->getContact()) {
                        if (!empty($customerData->getContactPerson()->getContact()->getDoNotContact()) && null !== $customerData->getContactPerson()->getContact()->getDoNotContact()) {
                            $customer->setDoNotContact($customerData->getContactPerson()->getContact()->getDoNotContact());
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getPreferContactMethod()) && null !== $customerData->getContactPerson()->getContact()->getPreferContactMethod()) {
                            $customer->setPreferredContactMethod($this->mapCustomerContactMethod($customerData->getContactPerson()->getContact()->getPreferContactMethod()));
                        }

                        if (null !== $customerData->getContactPerson()->getContact()->getSocialMediaAccount() && \count($customerData->getContactPerson()->getContact()->getSocialMediaAccount()) > 0) {
                            foreach ($customerData->getContactPerson()->getContact()->getSocialMediaAccount() as $socialMediaAccount) {
                                $person->addSameAsUrl($socialMediaAccount['social_url']);
                            }
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getWebsite()) && null !== $customerData->getContactPerson()->getContact()->getWebsite()) {
                            $person->addSameAsUrl($customerData->getContactPerson()->getContact()->getWebsite());
                        }

                        $contactPoint = new ContactPoint();

                        if (!empty($customerData->getContactPerson()->getContact()->getEmail()) && null !== $customerData->getContactPerson()->getContact()->getEmail()) {
                            $contactPoint->addEmail($customerData->getContactPerson()->getContact()->getEmail());
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getFaxNumber()) && null !== $customerData->getContactPerson()->getContact()->getFaxNumber()) {
                            if (null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber()) {
                                $faxNumber = $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber();
                                try {
                                    $phoneNumberObject = $this->phoneNumberUtil->parse($faxNumber);
                                    $contactPoint->addFaxNumber($phoneNumberObject);
                                } catch (\Exception $e) {
                                    $this->logger->info('Error while migrating fax number of Individual Customer : '.$e);
                                    $this->logger->info(\json_encode($customerData, JSON_PRETTY_PRINT));
                                }
                            }
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getMobileNumber()) && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()) {
                            if (null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber()) {
                                $mobileNumber = $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber();
                                try {
                                    $phoneNumberObject = $this->phoneNumberUtil->parse($mobileNumber);
                                    $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                                } catch (\Exception $e) {
                                    $this->logger->info('Error while migrating mobile number of Individual Customer : '.$e);
                                    $this->logger->info(\json_encode($customerData, JSON_PRETTY_PRINT));
                                }
                            }
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getPhoneNumber()) && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()) {
                            if (null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber()) {
                                $phoneNumber = $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber();
                                try {
                                    $phoneNumberObject = $this->phoneNumberUtil->parse($phoneNumber);
                                    $contactPoint->addTelephoneNumber($phoneNumberObject);
                                } catch (\Exception $e) {
                                    $this->logger->info('Error while migrating phone number of Individual Customer : '.$e);
                                    $this->logger->info(\json_encode($customerData, JSON_PRETTY_PRINT));
                                }
                            }
                        }

                        $person->addContactPoint($contactPoint);
                    }
                    $this->entityManager->persist($person);
                } elseif (false !== \stripos($customerData->getCategory(), 'CORPORATE')) {
                    $corporation = $customer->getCorporationDetails();

                    if (null === $corporation) {
                        $corporation = new Corporation();
                        $customer->setCorporationDetails($corporation);
                    }

                    if (!empty($customerData->getContactPerson()->getCompanyName())) {
                        $corporation->setName($customerData->getContactPerson()->getCompanyName());
                    }

                    if (!empty($customerData->getContactPerson()->getFoundDate())) {
                        $corporation->setFoundingDate($customerData->getContactPerson()->getFoundDate());
                    }

                    if (!empty($customerData->getContactPerson()->getIndustry()) && null !== $customerData->getContactPerson()->getIndustry()) {
                        $corporation->setIndustry($this->mapCorporateIndustry($customerData->getContactPerson()->getIndustry()));
                    }

                    if (null !== $customerData->getContactPerson()->getContact()) {
                        if (!empty($customerData->getContactPerson()->getContact()->getDoNotContact()) && null !== $customerData->getContactPerson()->getContact()->getDoNotContact()) {
                            $customer->setDoNotContact($customerData->getContactPerson()->getContact()->getDoNotContact());
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getPreferContactMethod()) && null !== $customerData->getContactPerson()->getContact()->getPreferContactMethod()) {
                            $customer->setPreferredContactMethod($this->mapCustomerContactMethod($customerData->getContactPerson()->getContact()->getPreferContactMethod()));
                        }

                        $contactPoint = new ContactPoint();

                        if (!empty($customerData->getContactPerson()->getContact()->getEmail()) && null !== $customerData->getContactPerson()->getContact()->getEmail()) {
                            $contactPoint->addEmail($customerData->getContactPerson()->getContact()->getEmail());
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getFaxNumber()) && null !== $customerData->getContactPerson()->getContact()->getFaxNumber()) {
                            if (null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode() &&
                                null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber()) {
                                $faxNumber = $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber();
                                try {
                                    $phoneNumberObject = $this->phoneNumberUtil->parse($faxNumber);
                                    $contactPoint->addFaxNumber($phoneNumberObject);
                                } catch (\Exception $e) {
                                    $this->logger->info('Error while migrating fax number of Corporate Customer : '.$e);
                                    $this->logger->info(\json_encode($customerData, JSON_PRETTY_PRINT));
                                }
                            }
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getMobileNumber()) && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()) {
                            if (null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber()) {
                                $mobileNumber = $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber();
                                try {
                                    $phoneNumberObject = $this->phoneNumberUtil->parse($mobileNumber);
                                    $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                                } catch (\Exception $e) {
                                    $this->logger->info('Error while migrating mobile number of Corporate Customer : '.$e);
                                    $this->logger->info(\json_encode($customerData, JSON_PRETTY_PRINT));
                                }
                            }
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getPhoneNumber()) && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()) {
                            if (null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber()) {
                                $phoneNumber = $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber();
                                try {
                                    $phoneNumberObject = $this->phoneNumberUtil->parse($phoneNumber);
                                    $contactPoint->addTelephoneNumber($phoneNumberObject);
                                } catch (\Exception $e) {
                                    $this->logger->info('Error while migrating phone number of Corporate Customer : '.$e);
                                    $this->logger->info(\json_encode($customerData, JSON_PRETTY_PRINT));
                                }
                            }
                        }

                        $corporation->addContactPoint($contactPoint);
                    }
                    $this->entityManager->persist($corporation);
                }
            }

            $this->entityManager->persist($customer);

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        //remove for now
        //$this->entityManager->clear();
    }

    /**
     * Create customers activity into database.
     *
     * @param array $customers
     */
    public function createCustomerActivity(array $customers)
    {
        $batchSize = 200;

        foreach ($customers as $key => $customerData) {
            $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()]);
            if (null !== $customer) {
                if (null !== $customerData->getActivity() && \count($customerData->getActivity()) > 0) {
                    if (!empty($customer->getActivities()) && \count($customer->getActivities()) > 0) {
                        foreach ($customer->getActivities() as $activity) {
                            $customer->removeActivity($activity);
                        }
                    }
                    foreach ($customerData->getActivity() as $activityData) {
                        if (!empty($activityData['data']['type'])) {
                            if (false !== \stripos($activityData['data']['type'], 'PHONE')) {
                                $phoneActivity = new PhoneContactActivity();

                                $phoneActivity->setDirection($this->mapCustomerActivityCallDirection($activityData['data']['direction']));

                                if (!empty($activityData['data']['phone_number']['country_number']) && !empty($activityData['data']['phone_number']['number'])) {
                                    if (null !== $activityData['data']['phone_number']['country_number'] && null !== $activityData['data']['phone_number']['number']) {
                                        $phoneNumber = $activityData['data']['phone_number']['country_number'].$activityData['data']['phone_number']['number'];

                                        try {
                                            $phoneNumberObject = $this->phoneNumberUtil->parse($phoneNumber);
                                            $phoneActivity->setRecipientTelephoneNumber($phoneNumberObject);
                                        } catch (\Exception $e) {
                                            $this->logger->info('Error while migrating phone number of Customer Activity : '.$e);
                                            $this->logger->info(\json_encode($customerData, JSON_PRETTY_PRINT));
                                        }
                                    }
                                }

                                if (!empty($activityData['data']['desc'])) {
                                    $phoneActivity->setText($activityData['data']['desc']);
                                }

                                if (!empty($activityData['_createdBy'])) {
                                    $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $activityData['_createdBy']]);
                                    if (null !== $creatorDocument) {
                                        $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                                        if (null !== $creator) {
                                            $phoneActivity->setCreator($creator->getUser());
                                        }
                                    }
                                }

                                if (!empty($activityData['_updatedBy'])) {
                                    $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $activityData['_updatedBy']]);
                                    if (null !== $agentDocument) {
                                        $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                                        if (null !== $agent) {
                                            $phoneActivity->setAgent($agent->getUser());
                                        }
                                    }
                                }

                                if (!empty($activityData['_createdAt']) && null !== $activityData['_createdAt']) {
                                    $phoneActivity->setDateCreated(new \DateTime(\date('Y-M-d h:i:s', $activityData['_createdAt']->sec)));
                                }

                                if (!empty($activityData['_updatedAt']) && null !== $activityData['_updatedAt']) {
                                    $phoneActivity->setDateModified(new \DateTime(\date('Y-M-d h:i:s', $activityData['_updatedAt']->sec)));
                                }
                                $customer->addActivity($phoneActivity);
                            } elseif (false !== \stripos($activityData['data']['type'], 'EMAIL')) {
                                $emailActivity = new EmailActivity();

                                if (!empty($activityData['data']['subject'])) {
                                    $emailActivity->setAbout($activityData['data']['subject']);
                                }

                                if (!empty($activityData['data']['bcc'])) {
                                    $emailActivity->addBccRecipient($activityData['data']['bcc']);
                                }

                                if (!empty($activityData['data']['cc'])) {
                                    $emailActivity->addCcRecipient($activityData['data']['cc']);
                                }

                                if (!empty($activityData['data']['template'])) {
                                    $emailActivity->setTemplate($activityData['data']['template']);
                                }

                                if (!empty($activityData['data']['to'])) {
                                    $emailActivity->addToRecipient($activityData['data']['to']);
                                }

                                if (!empty($activityData['data']['message'])) {
                                    $emailActivity->setText($activityData['data']['message']);
                                }

                                if (!empty($activityData['data']['attachment'])) {
                                    $messageAttached = new DigitalDocument();

                                    $appendUri = \sprintf('%s/%s', 'email', $activityData['data']['attachment']);

                                    $modifier = new AppendSegment($appendUri);
                                    $messageUri = $modifier->process($this->baseUri);

                                    $messageAttached->setName($activityData['data']['attachment']);
                                    $messageAttached->setUrl((string) $messageUri);

                                    if (!empty($activityData['_createdBy'])) {
                                        $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $activityData['_createdBy']]);
                                        if (null !== $creatorDocument) {
                                            $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                                            if (null !== $creator) {
                                                $messageAttached->setCreator($creator->getUser());
                                            }
                                        }
                                    }

                                    if (!empty($activityData['_updatedBy'])) {
                                        $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $activityData['_updatedBy']]);
                                        if (null !== $agentDocument) {
                                            $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                                            if (null !== $agent) {
                                                $messageAttached->setAgent($agent->getUser());
                                            }
                                        }
                                    }

                                    if (!empty($activityData['_createdAt']) && null !== $activityData['_createdAt']) {
                                        $messageAttached->setDateCreated(new \DateTime(\date('Y-M-d h:i:s', $activityData['_createdAt']->sec)));
                                    }

                                    if (!empty($activityData['_updatedAt']) && null !== $activityData['_updatedAt']) {
                                        $messageAttached->setDateModified(new \DateTime(\date('Y-M-d h:i:s', $activityData['_updatedAt']->sec)));
                                    }

                                    $emailActivity->addMessageAttachment($messageAttached);
                                }

                                if (!empty($activityData['_createdBy'])) {
                                    $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $activityData['_createdBy']]);
                                    if (null !== $creatorDocument) {
                                        $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                                        if (null !== $creator) {
                                            $emailActivity->setCreator($creator->getUser());
                                        }
                                    }
                                }

                                if (!empty($activityData['_updatedBy'])) {
                                    $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $activityData['_updatedBy']]);
                                    if (null !== $agentDocument) {
                                        $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                                        if (null !== $agent) {
                                            $emailActivity->setAgent($agent->getUser());
                                        }
                                    }
                                }

                                if (!empty($activityData['_createdAt']) && null !== $activityData['_createdAt']) {
                                    $emailActivity->setDateCreated(new \DateTime(\date('Y-M-d h:i:s', $activityData['_createdAt']->sec)));
                                }

                                if (!empty($activityData['_updatedAt']) && null !== $activityData['_updatedAt']) {
                                    $emailActivity->setDateModified(new \DateTime(\date('Y-M-d h:i:s', $activityData['_updatedAt']->sec)));
                                }

                                $customer->addActivity($emailActivity);
                            }
                        }
                    }
                    $this->entityManager->persist($customer);
                }
            }
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        //remove for now
        //$this->entityManager->clear();
    }

    /**
     * Create customers address into database.
     *
     * @param array $customers
     *
     * @throws \Exception
     */
    public function createCustomerAddress(array $customers)
    {
        $batchSize = 1000;

        foreach ($customers as $key => $customerData) {
            $existingCustomer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()]);
            if (null !== $existingCustomer) {
                $customer = $existingCustomer;
                if (null !== $customerData->getContactPerson() && null !== $customerData->getContactPerson()->getContact()) {
                    if (\count($customerData->getContactPerson()->getContact()->getAddress()) > 0 && !empty($customerData->getContactPerson()->getContact()->getAddress())) {
                        if (!empty($customer->getAddresses()) && \count($customer->getAddresses()) > 0) {
                            foreach ($customer->getAddresses() as $address) {
                                $customer->removeAddress($address);
                            }
                        }
                        foreach ($customerData->getContactPerson()->getContact()->getAddress() as $addressData) {
                            $address = new PostalAddress();

                            if (!empty($addressData['country'])) {
                                if (2 === \strlen($addressData['country'])) {
                                    $address->setAddressCountry($addressData['country']);
                                } elseif (false !== \stripos($addressData['country'], 'singapore')) {
                                    $address->setAddressCountry('SG');
                                }
                            }

                            if (!empty($addressData['region'])) {
                                $address->setAddressRegion($addressData['region']);
                            }

                            if (!empty($addressData['city'])) {
                                $address->setAddressLocality($addressData['city']);
                            }

                            if (!empty($addressData['building_name'])) {
                                $address->setBuildingName($addressData['building_name']);
                            }

                            if (!empty($addressData['floor'])) {
                                $address->setFloor($addressData['floor']);
                            }

                            if (!empty($addressData['house_no'])) {
                                $address->setHouseNumber($addressData['house_no']);
                            }

                            if (!empty($addressData['postal_code'])) {
                                $address->setPostalCode($addressData['postal_code']);
                            }

                            if (!empty($addressData['street'])) {
                                $address->setStreetAddress($addressData['street']);
                            }

                            if (!empty($addressData['address_type'])) {
                                $address->setType($this->mapCustomerAddressType($addressData['address_type']));
                            }

                            if (!empty($addressData['unit_no'])) {
                                $address->setUnitNumber($addressData['unit_no']);
                            }

                            $customerAddress = new CustomerAccountPostalAddress();

                            $customerAddress->setAddress($address);
                            $customerAddress->setCustomerAccount($customer);

                            if (!empty($addressData['valid_from'])) {
                                $customerAddress->setValidFrom(new \DateTime(\date('Y-M-d h:i:s', $addressData['valid_from']->sec)));
                            }

                            if (!empty($addressData['valid_to'])) {
                                $customerAddress->setValidThrough(new \DateTime(\date('Y-M-d h:i:s', $addressData['valid_from']->sec)));
                            }
                            $customer->addAddress($customerAddress);
                        }
                    }
                }
                $this->entityManager->persist($customer);
            }
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        //remove for now
        //$this->entityManager->clear();
    }

    /**
     * Create customers attachment into database.
     *
     * @param array $customers
     */
    public function createCustomerAttachment(array $customers)
    {
        $batchSize = 20;

        foreach ($customers as $key => $customerData) {
            $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()]);

            if (null !== $customer) {
                if (null !== $customerData->getAttachments() && \count($customerData->getAttachments()) > 0) {
                    foreach ($customerData->getAttachments() as $attachment) {
                        $supplementaryFiles = new DigitalDocument();

                        if (!empty($attachment['desc'])) {
                            $supplementaryFiles->setText($attachment['desc']);
                        }

                        if (!empty($attachment['attached'])) {
                            $appendUri = \sprintf('%s/%s', 'quotation', $attachment['attached']);

                            $modifier = new AppendSegment($appendUri);
                            $attachmentUri = $modifier->process($this->baseUri);

                            $supplementaryFiles->setName($attachment['attached']);
                            $supplementaryFiles->setUrl((string) $attachmentUri);
                        }

                        if (!empty($attachment['_createdBy'])) {
                            $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $attachment['_createdBy']]);
                            if (null !== $creatorDocument) {
                                $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                                if (null !== $creator) {
                                    $supplementaryFiles->setCreator($creator->getUser());
                                }
                            }
                        }

                        if (!empty($attachment['_updatedBy'])) {
                            $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $attachment['_updatedBy']]);
                            if (null !== $agentDocument) {
                                $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                                if (null !== $agent) {
                                    $supplementaryFiles->setAgent($agent->getUser());
                                }
                            }
                        }

                        if (!empty($attachment['_createdAt']) && null !== $attachment['_createdAt']) {
                            $supplementaryFiles->setDateCreated(new \DateTime(\date('Y-M-d h:i:s', $attachment['_createdAt']->sec)));
                        }

                        if (!empty($attachment['_updatedAt']) && null !== $attachment['_updatedAt']) {
                            $supplementaryFiles->setDateModified(new \DateTime(\date('Y-M-d h:i:s', $attachment['_updatedAt']->sec)));
                        }

                        $customer->addSupplementaryFile($supplementaryFiles);
                    }
                    $this->entityManager->persist($customer);
                }
            }
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        //remove for now
        //$this->entityManager->clear();
    }

    /**
     * Create or updates customer accounts agent and creator into database.
     *
     * @param array $customers
     *
     * @throws \Exception
     */
    public function createCustomerAgentCreator(array $customers)
    {
        $batchSize = 1000;

        foreach ($customers as $key => $customerData) {
            if (null === $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()])) {
                $this->createCustomer([$customerData]);
            }
            $existingCustomer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()]);

            $expr = $this->entityManager->getExpressionBuilder();
            $userRepo = $this->entityManager->getRepository(User::class)->createQueryBuilder('user')->select('user')
                ->join('user.bridgeUser', 'bridgeUser');

            if (null !== $existingCustomer) {
                $customer = $existingCustomer;

                if (!empty($customerData->getCreatedBy())) {
                    /**
                     * @var User|null
                     */
                    $createdBy = $userRepo
                            ->where($expr->eq('bridgeUser.bridgeUserId', ':id'))
                            ->setParameters([
                                'id' => $customerData->getCreatedBy(),
                            ])
                            ->getQuery()
                            ->getOneOrNullResult(Query::HYDRATE_OBJECT);
                    $customer->setCreator($createdBy);
                }

                if (!empty($customerData->getUpdatedBy())) {
                    /**
                     * @var User|null
                     */
                    $updatedBy = $userRepo
                            ->where($expr->eq('bridgeUser.bridgeUserId', ':id'))
                            ->setParameters([
                                'id' => $customerData->getUpdatedBy(),
                            ])
                            ->getQuery()
                            ->getOneOrNullResult(Query::HYDRATE_OBJECT);
                    $customer->setAgent($updatedBy);
                }

                if (null !== $customerData->getCreatedAt()) {
                    $customer->setDateCreated($customerData->getCreatedAt());
                }
                if (null !== $customerData->getUpdatedAt()) {
                    $customer->setDateModified($customerData->getUpdatedAt());
                }

                $this->entityManager->persist($customer);
            }
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * Create or update customers identifiers into database.
     *
     * @param array $customers
     *
     * @throws \Exception
     */
    public function createCustomerIdentifier(array $customers)
    {
        $batchSize = 1000;

        foreach ($customers as $key => $customerData) {
            if (null === $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()])) {
                $this->createCustomer($customerData);
            }
            $existingCustomer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()]);
            if (null !== $existingCustomer) {
                $customer = $existingCustomer;
                if (false !== \stripos($customerData->getCategory(), 'INDIVIDUAL')) {
                    $person = $customer->getPersonDetails();
                    if (null !== $person) {
                        if (null !== $customerData->getContactPerson()) {
                            if (null !== $customerData->getContactPerson()->getIdentification()) {
                                $identifier = null;
                                if (!empty($customerData->getContactPerson()->getIdentification()->getNricFin()) || !empty($customerData->getContactPerson()->getIdentification()->getUen())) {
                                    if (\count($person->getIdentifiers()) > 0) {
                                        foreach ($person->getIdentifiers() as $identification) {
                                            if ($customerData->getContactPerson()->getIdentification()->getNricFin() === $identification->getValue() || $customerData->getContactPerson()->getIdentification()->getUen() === $identification->getValue()) {
                                                $identifier = $identification;
                                                $person->removeIdentifier($identification);
                                                break;
                                            }
                                        }
                                    }
                                    if (null === $identifier) {
                                        $identifier = new Identification();
                                    }

                                    if (!empty($customerData->getContactPerson()->getIdentification()->getDescription())) {
                                        $identifier->setDescription($customerData->getContactPerson()->getIdentification()->getDescription());
                                    }

                                    if (!empty($customerData->getContactPerson()->getIdentification()->getNricFin()) && null !== $customerData->getContactPerson()->getIdentification()->getNricFin()) {
                                        $identifier->setName($this->mapIdentificationName('NATIONAL_REGISTRATION_IDENTITY_CARD'));
                                        $identifier->setValue($customerData->getContactPerson()->getIdentification()->getNricFin());
                                    } elseif (!empty($customerData->getContactPerson()->getIdentification()->getUen()) && null !== $customerData->getContactPerson()->getIdentification()->getUen()) {
                                        $identifier->setName($this->mapIdentificationName('UNIQUE_ENTITY_NUMBER'));
                                        $identifier->setValue($customerData->getContactPerson()->getIdentification()->getUen());
                                    }

                                    if (!empty($customerData->getContactPerson()->getIdentification()->getValidFrom())) {
                                        $identifier->setValidFrom($customerData->getContactPerson()->getIdentification()->getValidFrom());
                                    }

                                    if (!empty($customerData->getContactPerson()->getIdentification()->getValidThrough())) {
                                        $identifier->setValidThrough($customerData->getContactPerson()->getIdentification()->getValidThrough());
                                    }

                                    /*if (!empty($customerData->getCreatedBy())) {
                                        $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $customerData->getCreatedBy()]);
                                        if (null !== $creatorDocument) {
                                            $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                                            if (null !== $creator) {
                                                $identifier->setCreator($creator->getUser());
                                            }
                                        }
                                    }

                                    if (!empty($customerData->getUpdatedBy())) {
                                        $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $customerData->getUpdatedBy()]);
                                        if (null !== $agentDocument) {
                                            $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                                            if (null !== $agent) {
                                                $identifier->setAgent($agent->getUser());
                                            }
                                        }
                                    }*/

                                    if (null !== $customer->getCreator()) {
                                        $identifier->setCreator($customer->getCreator());
                                    }

                                    if (null !== $customer->getAgent()) {
                                        $identifier->setAgent($customer->getAgent());
                                    }

                                    if (null !== $customer->getDateCreated()) {
                                        $identifier->setDateCreated($customer->getDateCreated());
                                    }

                                    if (null !== $customer->getDateModified()) {
                                        $identifier->setDateModified($customer->getDateModified());
                                    }
                                    $person->addIdentifier($identifier);
                                }
                            }
                        }
                    }
                } elseif (false !== \stripos($customerData->getCategory(), 'CORPORATE')) {
                    $corporation = $customer->getCorporationDetails();
                    if (null !== $corporation) {
                        if (null !== $customerData->getContactPerson()) {
                            if (null !== $customerData->getContactPerson()->getIdentification()) {
                                $identifier = null;
                                if (!empty($customerData->getContactPerson()->getIdentification()->getNricFin()) || !empty($customerData->getContactPerson()->getIdentification()->getUen())) {
                                    if (\count($corporation->getIdentifiers()) > 0) {
                                        foreach ($corporation->getIdentifiers() as $identification) {
                                            if ($customerData->getContactPerson()->getIdentification()->getNricFin() === $identification->getValue() || $customerData->getContactPerson()->getIdentification()->getUen() === $identification->getValue()) {
                                                $identifier = $identification;
                                                $corporation->removeIdentifier($identification);
                                                break;
                                            }
                                        }
                                    }

                                    if (null === $identifier) {
                                        $identifier = new Identification();
                                    }

                                    if (!empty($customerData->getContactPerson()->getIdentification()->getDescription())) {
                                        $identifier->setDescription($customerData->getContactPerson()->getIdentification()->getDescription());
                                    }

                                    if (!empty($customerData->getContactPerson()->getIdentification()->getUen()) && null !== $customerData->getContactPerson()->getIdentification()->getUen()) {
                                        $identifier->setName($this->mapIdentificationName('UNIQUE_ENTITY_NUMBER'));
                                        $identifier->setValue($customerData->getContactPerson()->getIdentification()->getUen());
                                    } elseif (!empty($customerData->getContactPerson()->getIdentification()->getNricFin()) && null !== $customerData->getContactPerson()->getIdentification()->getNricFin()) {
                                        $identifier->setName($this->mapIdentificationName('NATIONAL_REGISTRATION_IDENTITY_CARD'));
                                        $identifier->setValue($customerData->getContactPerson()->getIdentification()->getNricFin());
                                    }

                                    if (!empty($customerData->getContactPerson()->getIdentification()->getValidFrom())) {
                                        $identifier->setValidFrom($customerData->getContactPerson()->getIdentification()->getValidFrom());
                                    }

                                    if (!empty($customerData->getContactPerson()->getIdentification()->getValidThrough())) {
                                        $identifier->setValidThrough($customerData->getContactPerson()->getIdentification()->getValidThrough());
                                    }

                                    /*                                    if (!empty($customerData->getCreatedBy())) {
                                                                            $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $customerData->getCreatedBy()]);
                                                                            if (null !== $creatorDocument) {
                                                                                $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                                                                                if (null !== $creator) {
                                                                                    $identifier->setCreator($creator->getUser());
                                                                                }
                                                                            }
                                                                        }

                                                                        if (!empty($customerData->getUpdatedBy())) {
                                                                            $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $customerData->getUpdatedBy()]);
                                                                            if (null !== $agentDocument) {
                                                                                $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                                                                                if (null !== $agent) {
                                                                                    $identifier->setAgent($agent->getUser());
                                                                                }
                                                                            }
                                                                        }

                                                                        if (!empty($customerData->getCreatedAt()) && null !== $customerData->getCreatedAt()) {
                                                                            $identifier->setDateCreated($customerData->getCreatedAt());
                                                                        }

                                                                        if (!empty($customerData->getUpdatedAt()) && null !== $customerData->getUpdatedAt()) {
                                                                            $identifier->setDateModified($customerData->getUpdatedAt());
                                                                        }*/

                                    if (null !== $customer->getCreator()) {
                                        $identifier->setCreator($customer->getCreator());
                                    }

                                    if (null !== $customer->getAgent()) {
                                        $identifier->setAgent($customer->getAgent());
                                    }

                                    if (null !== $customer->getDateCreated()) {
                                        $identifier->setDateCreated($customer->getDateCreated());
                                    }

                                    if (null !== $customer->getDateModified()) {
                                        $identifier->setDateModified($customer->getDateModified());
                                    }
                                    $corporation->addIdentifier($identifier);
                                }
                            }
                        }
                        $this->entityManager->persist($corporation);
                    }
                }
                $this->entityManager->persist($customer);
            }

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        //remove for now
        //$this->entityManager->clear();
    }

    /**
     * Create customers relationship into database.
     *
     * @param array $customers
     *
     * @throws \Exception
     */
    public function createCustomerRelationship(array $customers)
    {
        $batchSize = 1000;

        foreach ($customers as $key => $customerData) {
            $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()]);
            if (null !== $customer) {
                if (null !== $customerData->getContactPerson()) {
                    if (null !== $customerData->getContactPerson()->getRelationship() && \count($customerData->getContactPerson()->getRelationship()) > 0) {
                        foreach ($customerData->getContactPerson()->getRelationship() as $relationshipData) {
                            $customerAccountRelationship = new CustomerAccountRelationship();

                            if ('IS' !== \substr($relationshipData['type'], 0, 2)) {
                                $fromCustomerDocument = $this->documentManager->getRepository(OldCustomerAccount::class)->findOneBy(['id' => $relationshipData['customer_id']]);
                                if (null !== $fromCustomerDocument) {
                                    if (null === $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $fromCustomerDocument->getCustomerId()])) {
                                        $this->createCustomer([$fromCustomerDocument]);
                                    }
                                    $fromCustomer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $fromCustomerDocument->getCustomerId()]);
                                    if (null !== $fromCustomer) {
                                        $customerAccountRelationship->setFrom($fromCustomer);
                                    }
                                }
                                $customerAccountRelationship->setTo($customer);
                            } elseif ('IS' === \substr($relationshipData['type'], 0, 2)) {
                                $customerAccountRelationship->setFrom($customer);
                                $toCustomerDocument = $this->documentManager->getRepository(OldCustomerAccount::class)->findOneBy(['id' => $relationshipData['customer_id']]);
                                if (null !== $toCustomerDocument) {
                                    if (null === $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $toCustomerDocument->getCustomerId()])) {
                                        $this->createCustomer([$toCustomerDocument]);
                                    }
                                    $toCustomer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $toCustomerDocument->getCustomerId()]);
                                    if (null !== $toCustomer) {
                                        $customerAccountRelationship->setTo($toCustomer);
                                    }
                                }
                            }

                            if (!empty($relationshipData['contract_id'])) {
                                $contractDocument = $this->documentManager->getRepository(Contract::class)->findOneBy(['id' => $relationshipData['contract_id']]);
                                if (null !== $contractDocument) {
                                    $contract = $this->entityManager->getRepository(\App\Entity\Contract::class)->findOneBy(['contractNumber' => $contractDocument->getContractId()]);
                                    if (null !== $contract) {
                                        $customerAccountRelationship->addContract($contract);
                                    }
                                }
                            }

                            if (\count($customer->getRelationships()) > 0) {
                                foreach ($customer->getRelationships() as $relationship) {
                                    if ($customerAccountRelationship->getTo()->getId() === $relationship->getTo()->getId() && $customerAccountRelationship->getFrom()->getId() === $relationship->getFrom()->getId()) {
                                        if ($relationshipData['type'] === 'HAS_'.$relationship->getType()->getValue() || $relationshipData['type'] === 'IS_'.$relationship->getType()->getValue()) {
                                            $customerAccountRelationship = $relationship;
                                            $customer->removeRelationship($relationship);
                                            break;
                                        }
                                    }
                                }
                            }

                            if (!empty($relationshipData['_createdBy'])) {
                                $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $relationshipData['_createdBy']]);
                                if (null !== $creatorDocument) {
                                    $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                                    if (null !== $creator) {
                                        $customerAccountRelationship->setCreator($creator->getUser());
                                    }
                                }
                            }

                            if (!empty($relationshipData['_updatedBy'])) {
                                $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $relationshipData['_updatedBy']]);
                                if (null !== $agentDocument) {
                                    $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                                    if (null !== $agent) {
                                        $customerAccountRelationship->setAgent($agent->getUser());
                                    }
                                }
                            }

                            if (!empty($relationshipData['_createdAt']) && null !== $relationshipData['_createdAt']) {
                                $customerAccountRelationship->setDateCreated(new \DateTime(\date('Y-M-d h:i:s', $relationshipData['_createdAt']->sec)));
                            }

                            if (!empty($relationshipData['_updatedAt']) && null !== $relationshipData['_updatedAt']) {
                                $customerAccountRelationship->setDateModified(new \DateTime(\date('Y-M-d h:i:s', $relationshipData['_updatedAt']->sec)));
                            }

                            if (!empty($relationshipData['type'])) {
                                $customerAccountRelationship->setType($this->mapCustomerRelationshipType($relationshipData['type']));
                            }

                            if (!empty($relationshipData['type']) && false !== \stripos($relationshipData['type'], 'MANAGE_SSP')) {
                                $customerAccountRelationship->setCustomerPortalEnabled(true);
                            }

                            if (!empty($relationshipData['valid_from'])) {
                                $customerAccountRelationship->setValidFrom(new \DateTime(\date('Y-M-d h:i:s', $relationshipData['valid_from']->sec)));
                            }

                            if (!empty($relationshipData['valid_to'])) {
                                $customerAccountRelationship->setValidThrough(new \DateTime(\date('Y-M-d h:i:s', $relationshipData['valid_to']->sec)));
                            }

                            $customer->addRelationship($customerAccountRelationship);
                        }
                        $this->entityManager->persist($customer);
                    }
                }
            }
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        //remove for now
        //$this->entityManager->clear();
    }

    /**
     * Create or update customers note into database.
     *
     * @param array $customers
     */
    public function createCustomerNote(array $customers)
    {
        $batchSize = 200;

        foreach ($customers as $key => $customerData) {
            $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()]);
            if (null !== $customer) {
                if (null !== $customerData->getNote() && \count($customerData->getNote()) > 0) {
                    if (!empty($customer->getNotes()) && \count($customer->getNotes()) > 0) {
                        foreach ($customer->getNotes() as $note) {
                            $customer->removeNote($note);
                        }
                    }
                    foreach ($customerData->getNote() as $noteData) {
                        if (!empty($noteData['desc'])) {
                            $note = new Note();

                            $note->setText($noteData['desc']);

                            if (!empty($noteData['note_attached']) && \count($noteData['note_attached']) > 0) {
                                foreach ($noteData['note_attached'] as $attachedData) {
                                    $file = new DigitalDocument();

                                    $appendUri = \sprintf('%s/%s', 'note', $attachedData);

                                    $modifier = new AppendSegment($appendUri);
                                    $messageUri = $modifier->process($this->baseUri);

                                    $file->setName($attachedData);
                                    $file->setUrl((string) $messageUri);

                                    if (!empty($noteData['_createdBy'])) {
                                        $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $noteData['_createdBy']]);
                                        if (null !== $creatorDocument) {
                                            $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                                            if (null !== $creator) {
                                                $file->setCreator($creator->getUser());
                                            }
                                        }
                                    }

                                    if (!empty($noteData['_updatedBy'])) {
                                        $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $noteData['_updatedBy']]);
                                        if (null !== $agentDocument) {
                                            $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                                            if (null !== $agent) {
                                                $file->setAgent($agent->getUser());
                                            }
                                        }
                                    }

                                    if (!empty($noteData['_createdAt']) && null !== $noteData['_createdAt']) {
                                        $file->setDateCreated(new \DateTime(\date('Y-M-d h:i:s', $noteData['_createdAt']->sec)));
                                    }

                                    if (!empty($noteData['_updatedAt']) && null !== $noteData['_updatedAt']) {
                                        $file->setDateModified(new \DateTime(\date('Y-M-d h:i:s', $noteData['_updatedAt']->sec)));
                                    }

                                    $note->addFile($file);
                                }
                            }

                            if (!empty($noteData['note_type'])) {
                                $note->setType($this->mapNoteType($noteData['note_type']));
                            }

                            if (!empty($noteData['_createdBy'])) {
                                $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $noteData['_createdBy']]);
                                if (null !== $creatorDocument) {
                                    $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                                    if (null !== $creator) {
                                        $note->setCreator($creator->getUser());
                                    }
                                }
                            }

                            if (!empty($noteData['_updatedBy'])) {
                                $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $noteData['_updatedBy']]);
                                if (null !== $agentDocument) {
                                    $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                                    if (null !== $agent) {
                                        $note->setAgent($agent->getUser());
                                    }
                                }
                            }

                            if (!empty($noteData['_createdAt']) && null !== $noteData['_createdAt']) {
                                $note->setDateCreated(new \DateTime(\date('Y-M-d h:i:s', $noteData['_createdAt']->sec)));
                            }

                            if (!empty($noteData['_updatedAt']) && null !== $noteData['_updatedAt']) {
                                $note->setDateModified(new \DateTime(\date('Y-M-d h:i:s', $noteData['_updatedAt']->sec)));
                            }

                            $customer->addNote($note);
                        }
                    }
                    $this->entityManager->persist($customer);
                }
            }
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
    }

    /**
     * Update customers into database.
     *
     * @param array $customers
     *
     * @throws \Exception
     */
    public function updateCustomer(array $customers)
    {
        $batchSize = 1000;

        foreach ($customers as $key => $customerData) {
            $existingCustomer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()]);

            if (null === $existingCustomer) {
                if (null !== $customerData->getContactPerson()) {
                    if (!empty($customerData->getContactPerson()->getContract()) && \count($customerData->getContactPerson()->getContract()) > 0) {
                        foreach ($customerData->getContactPerson()->getContract() as $contractId) {
                            $contractDocument = $this->documentManager->getRepository(Contract::class)->findOneBy(['id' => $contractId]);
                            if (null !== $contractDocument) {
                                $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $contractDocument->getApplicationRequestNumber()]);
                                if (null !== $applicationRequest) {
                                    if (null !== $applicationRequest->getCustomer()) {
                                        $existingCustomer = $applicationRequest->getCustomer();
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (null !== $existingCustomer) {
                $customer = $existingCustomer;
                $customer->setAccountNumber($customerData->getCustomerId());
            } else {
                $customer = new CustomerAccount();
                $customer->setAccountNumber($customerData->getCustomerId());
            }

            if (null !== $customerData->getCategory()) {
                $customer->setType($this->mapCustomerAccountType($customerData->getCategory()));
            }

            if (!empty($customerData->getExternalCustomerId())) {
                $customer->setExternalCustomerNumber($customerData->getExternalCustomerId());
            }

            if (!empty($customerData->getReferralCode())) {
                $customer->setReferralCode($customerData->getReferralCode());
            }

            if (null !== $customerData->getStatus()) {
                $customer->setStatus($this->mapCustomerAccountStatus($customerData->getStatus()));
            }

            if (!empty($customerData->getSource()) && null !== $customerData->getSource()) {
                $customer->setSource($this->mapSource($customerData->getSource()));
            }

            if (null !== $customerData->getType() && \count($customerData->getType()) > 0) {
                if (\count($customer->getCategories()) > 0) {
                    foreach ($customer->getCategories() as $category) {
                        $customer->removeCategory($category);
                    }
                    foreach ($customerData->getType() as $type) {
                        $customer->addCategory($type);
                    }
                } else {
                    foreach ($customerData->getType() as $type) {
                        $customer->addCategory($type);
                    }
                }
            }

            if (null !== $customerData->getContactPerson()) {
                if (false !== \stripos($customerData->getCategory(), 'INDIVIDUAL')) {
                    $person = $customer->getPersonDetails();

                    if (null === $person) {
                        $person = new Person();
                        $customer->setPersonDetails($person);
                    }

                    if (!empty($customerData->getContactPerson()->getAdditionalName())) {
                        $person->setAdditionalName($customerData->getContactPerson()->getAdditionalName());
                    }

                    if (!empty($customerData->getContactPerson()->getAlternateName())) {
                        $person->setAlternateName($customerData->getContactPerson()->getAlternateName());
                    }

                    if (!empty($customerData->getContactPerson()->getDateOfBirth())) {
                        $person->setBirthDate($customerData->getContactPerson()->getDateOfBirth());
                    }

                    if (!empty($customerData->getContactPerson()->getDateOfDeath()) && null !== $customerData->getContactPerson()->getDateOfDeath()) {
                        $person->setDeathDate($customerData->getContactPerson()->getDateOfDeath());
                    }

                    if (!empty($customerData->getContactPerson()->getDesignation()) && null !== $customerData->getContactPerson()->getDesignation()) {
                        $person->setJobTitle($customerData->getContactPerson()->getDesignation());
                    }

                    if (!empty($customerData->getContactPerson()->getCountryOrigin()) && null !== $customerData->getContactPerson()->getCountryOrigin()) {
                        $person->setCountryOfOrigin($customerData->getContactPerson()->getCountryOrigin());
                    }

                    if (!empty($customerData->getContactPerson()->getFamilyName())) {
                        $person->setFamilyName($customerData->getContactPerson()->getFamilyName());
                    }

                    if (!empty($customerData->getContactPerson()->getGivenName())) {
                        $person->setGivenName($customerData->getContactPerson()->getGivenName());
                    }

                    if (!empty($customerData->getContactPerson()->getGender()) && null !== $customerData->getContactPerson()->getGender()) {
                        $person->setGender($this->mapGender($customerData->getContactPerson()->getGender()));
                    }

                    if (!empty($customerData->getContactPerson()->getHonorificPrefix())) {
                        $person->setHonorificPrefix($customerData->getContactPerson()->getHonorificPrefix());
                    }

                    if (!empty($customerData->getContactPerson()->getMartialStatus()) && null !== $customerData->getContactPerson()->getMartialStatus()) {
                        $person->setMaritalStatus($this->mapMaritalStatus($customerData->getContactPerson()->getMartialStatus()));
                    }

                    if (!empty($customerData->getContactPerson()->getName())) {
                        $person->setName($customerData->getContactPerson()->getName());
                    }

                    if (!empty($customerData->getContactPerson()->getNationality())) {
                        $person->setNationality($customerData->getContactPerson()->getNationality());
                    }

                    if (!empty($customerData->getContactPerson()->getPlaceOfBirth())) {
                        $person->setBirthPlace($customerData->getContactPerson()->getPlaceOfBirth());
                    }

                    if (null !== $customerData->getContactPerson()->getContact()) {
                        if (null !== $customerData->getContactPerson()->getContact()->getDoNotContact()) {
                            $customer->setDoNotContact($customerData->getContactPerson()->getContact()->getDoNotContact());
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getPreferContactMethod()) && null !== $customerData->getContactPerson()->getContact()->getPreferContactMethod()) {
                            $customer->setPreferredContactMethod($this->mapCustomerContactMethod($customerData->getContactPerson()->getContact()->getPreferContactMethod()));
                        }

                        if (null !== $customerData->getContactPerson()->getContact()->getSocialMediaAccount() && \count($customerData->getContactPerson()->getContact()->getSocialMediaAccount()) > 0) {
                            foreach ($customerData->getContactPerson()->getContact()->getSocialMediaAccount() as $socialMediaAccount) {
                                if (!empty($socialMediaAccount['social_url']) && null !== $socialMediaAccount['social_url']) {
                                    if (\count($person->getSameAsUrls()) > 0) {
                                        foreach ($person->getSameAsUrls() as $url) {
                                            if ($socialMediaAccount['social_url'] === $url) {
                                                $person->removeSameAsUrl($url);
                                            }
                                        }
                                    }
                                    $person->addSameAsUrl($socialMediaAccount['social_url']);
                                }
                            }
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getWebsite()) && null !== $customerData->getContactPerson()->getContact()->getWebsite()) {
                            if (\count($person->getSameAsUrls()) > 0) {
                                foreach ($person->getSameAsUrls() as $url) {
                                    if ($customerData->getContactPerson()->getContact()->getWebsite() === $url) {
                                        $person->removeSameAsUrl($url);
                                    }
                                }
                            }
                            $person->addSameAsUrl($customerData->getContactPerson()->getContact()->getWebsite());
                        }

                        if (!empty($person->getContactPoints()) && \count($person->getContactPoints()) > 0) {
                            foreach ($person->getContactPoints() as $contactPoints) {
                                if (1 !== \count($person->getContactPoints())) {
                                    break;
                                }
                                $contactPoint = $contactPoints;

                                if (!empty($customerData->getContactPerson()->getContact()->getEmail()) && null !== $customerData->getContactPerson()->getContact()->getEmail()) {
                                    if (\count($contactPoint->getEmails()) > 0) {
                                        foreach ($contactPoint->getEmails() as $email) {
                                            if ($customerData->getContactPerson()->getContact()->getEmail() === $email) {
                                                $contactPoint->removeEmail($email);
                                            }
                                        }
                                        $contactPoint->addEmail($customerData->getContactPerson()->getContact()->getEmail());
                                    } else {
                                        $contactPoint->addEmail($customerData->getContactPerson()->getContact()->getEmail());
                                    }
                                }

                                if (!empty($customerData->getContactPerson()->getContact()->getFaxNumber()) && null !== $customerData->getContactPerson()->getContact()->getFaxNumber()) {
                                    if (null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber()) {
                                        $faxNumber = $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber();
                                        try {
                                            $phoneNumberObject = $this->phoneNumberUtil->parse($faxNumber);
                                            if (\count($contactPoint->getFaxNumbers()) > 0) {
                                                foreach ($contactPoint->getFaxNumbers() as $faxNumber) {
                                                    if ($phoneNumberObject->getNationalNumber() === $faxNumber->getNationalNumber()) {
                                                        $contactPoint->removeFaxNumber($faxNumber);
                                                    }
                                                }
                                                $contactPoint->addFaxNumber($phoneNumberObject);
                                            } else {
                                                $contactPoint->addFaxNumber($phoneNumberObject);
                                            }
                                        } catch (\Exception $e) {
                                            $this->logger->warning('Error while migrating fax number of Individual Customer : '.$e);
                                            $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                        }
                                    }
                                }

                                if (!empty($customerData->getContactPerson()->getContact()->getMobileNumber()) && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()) {
                                    if (null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber()) {
                                        $mobileNumber = $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber();
                                        try {
                                            $phoneNumberObject = $this->phoneNumberUtil->parse($mobileNumber);
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
                                            $this->logger->warning('Error while migrating mobile number of Individual Customer : '.$e);
                                            $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                        }
                                    }
                                }
                                if (!empty($customerData->getContactPerson()->getContact()->getPhoneNumber()) && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()) {
                                    if (null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber()) {
                                        $phoneNumber = $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber();
                                        try {
                                            $phoneNumberObject = $this->phoneNumberUtil->parse($phoneNumber);
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
                                            $this->logger->warning('Error while migrating phone number of Individual Customer : '.$e);
                                            $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                        }
                                    }
                                }
                                $person->removeContactPoint($contactPoints);
                                $person->addContactPoint($contactPoint);
                            }
                        } else {
                            $contactPoint = new ContactPoint();

                            if (!empty($customerData->getContactPerson()->getContact()->getEmail()) && null !== $customerData->getContactPerson()->getContact()->getEmail()) {
                                $contactPoint->addEmail($customerData->getContactPerson()->getContact()->getEmail());
                            }

                            if (!empty($customerData->getContactPerson()->getContact()->getFaxNumber()) && null !== $customerData->getContactPerson()->getContact()->getFaxNumber()) {
                                if (null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber()) {
                                    $faxNumber = $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber();
                                    try {
                                        $phoneNumberObject = $this->phoneNumberUtil->parse($faxNumber);
                                        $contactPoint->addFaxNumber($phoneNumberObject);
                                    } catch (\Exception $e) {
                                        $this->logger->warning('Error while migrating fax number of Individual Customer : '.$e);
                                        $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                    }
                                }
                            }

                            if (!empty($customerData->getContactPerson()->getContact()->getMobileNumber()) && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()) {
                                if (null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber()) {
                                    $mobileNumber = $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber();
                                    try {
                                        $phoneNumberObject = $this->phoneNumberUtil->parse($mobileNumber);
                                        $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                                    } catch (\Exception $e) {
                                        $this->logger->warning('Error while migrating mobile number of Individual Customer : '.$e);
                                        $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                    }
                                }
                            }

                            if (!empty($customerData->getContactPerson()->getContact()->getPhoneNumber()) && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()) {
                                if (null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber()) {
                                    $phoneNumber = $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber();
                                    try {
                                        $phoneNumberObject = $this->phoneNumberUtil->parse($phoneNumber);
                                        $contactPoint->addTelephoneNumber($phoneNumberObject);
                                    } catch (\Exception $e) {
                                        $this->logger->warning('Error while migrating phone number of Individual Customer : '.$e);
                                        $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                    }
                                }
                            }
                            $person->addContactPoint($contactPoint);
                        }
                    }
                } elseif (false !== \stripos($customerData->getCategory(), 'CORPORATE')) {
                    $corporation = $customer->getCorporationDetails();

                    if (null === $corporation) {
                        $corporation = new Corporation();
                        $customer->setCorporationDetails($corporation);
                    }

                    if (!empty($customerData->getContactPerson()->getCompanyName())) {
                        $corporation->setName($customerData->getContactPerson()->getCompanyName());
                    }

                    if (!empty($customerData->getContactPerson()->getFoundDate())) {
                        $corporation->setFoundingDate($customerData->getContactPerson()->getFoundDate());
                    }

                    if (!empty($customerData->getContactPerson()->getIndustry()) && null !== $customerData->getContactPerson()->getIndustry()) {
                        $corporation->setIndustry($this->mapCorporateIndustry($customerData->getContactPerson()->getIndustry()));
                    }

                    if (null !== $customerData->getContactPerson()->getContact()) {
                        if (null !== $customerData->getContactPerson()->getContact()->getDoNotContact()) {
                            $customer->setDoNotContact($customerData->getContactPerson()->getContact()->getDoNotContact());
                        }

                        if (!empty($customerData->getContactPerson()->getContact()->getPreferContactMethod()) && null !== $customerData->getContactPerson()->getContact()->getPreferContactMethod()) {
                            $customer->setPreferredContactMethod($this->mapCustomerContactMethod($customerData->getContactPerson()->getContact()->getPreferContactMethod()));
                        }

                        if (!empty($corporation->getContactPoints()) && \count($corporation->getContactPoints()) > 0) {
                            foreach ($corporation->getContactPoints() as $contactPoints) {
                                if (1 !== \count($corporation->getContactPoints())) {
                                    break;
                                }
                                $contactPoint = $contactPoints;

                                if (!empty($customerData->getContactPerson()->getContact()->getEmail()) && null !== $customerData->getContactPerson()->getContact()->getEmail()) {
                                    if (\count($contactPoint->getEmails()) > 0) {
                                        foreach ($contactPoint->getEmails() as $email) {
                                            if ($customerData->getContactPerson()->getContact()->getEmail() === $email) {
                                                $contactPoint->removeEmail($email);
                                            }
                                        }
                                        $contactPoint->addEmail($customerData->getContactPerson()->getContact()->getEmail());
                                    } else {
                                        $contactPoint->addEmail($customerData->getContactPerson()->getContact()->getEmail());
                                    }
                                }

                                if (!empty($customerData->getContactPerson()->getContact()->getFaxNumber()) && null !== $customerData->getContactPerson()->getContact()->getFaxNumber()) {
                                    if (null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber()) {
                                        $faxNumber = $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber();
                                        try {
                                            $phoneNumberObject = $this->phoneNumberUtil->parse($faxNumber);
                                            if (\count($contactPoint->getFaxNumbers()) > 0) {
                                                foreach ($contactPoint->getFaxNumbers() as $faxNumber) {
                                                    if ($phoneNumberObject->getNationalNumber() === $faxNumber->getNationalNumber()) {
                                                        $contactPoint->removeFaxNumber($faxNumber);
                                                    }
                                                }
                                                $contactPoint->addFaxNumber($phoneNumberObject);
                                            } else {
                                                $contactPoint->addFaxNumber($phoneNumberObject);
                                            }
                                        } catch (\Exception $e) {
                                            $this->logger->warning('Error while migrating fax number of Corporate Customer : '.$e);
                                            $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                        }
                                    }
                                }

                                if (!empty($customerData->getContactPerson()->getContact()->getMobileNumber()) && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()) {
                                    if (null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber()) {
                                        $mobileNumber = $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber();
                                        try {
                                            $phoneNumberObject = $this->phoneNumberUtil->parse($mobileNumber);
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
                                            $this->logger->warning('Error while migrating mobile number of Corporate Customer : '.$e);
                                            $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                        }
                                    }
                                }
                                if (!empty($customerData->getContactPerson()->getContact()->getPhoneNumber()) && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()) {
                                    if (null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber()) {
                                        $phoneNumber = $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber();
                                        try {
                                            $phoneNumberObject = $this->phoneNumberUtil->parse($phoneNumber);
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
                                            $this->logger->warning('Error while migrating phone number of Corporate Customer : '.$e);
                                            $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                        }
                                    }
                                }
                                $corporation->removeContactPoint($contactPoints);
                                $corporation->addContactPoint($contactPoint);
                            }
                        } else {
                            $contactPoint = new ContactPoint();

                            if (!empty($customerData->getContactPerson()->getContact()->getEmail()) && null !== $customerData->getContactPerson()->getContact()->getEmail()) {
                                $contactPoint->addEmail($customerData->getContactPerson()->getContact()->getEmail());
                            }

                            if (!empty($customerData->getContactPerson()->getContact()->getFaxNumber()) && null !== $customerData->getContactPerson()->getContact()->getFaxNumber()) {
                                if (null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber()) {
                                    $faxNumber = $customerData->getContactPerson()->getContact()->getFaxNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getFaxNumber()->getNumber();
                                    try {
                                        $phoneNumberObject = $this->phoneNumberUtil->parse($faxNumber);
                                        $contactPoint->addFaxNumber($phoneNumberObject);
                                    } catch (\Exception $e) {
                                        $this->logger->warning('Error while migrating fax number of Corporate Customer : '.$e);
                                        $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                    }
                                }
                            }

                            if (!empty($customerData->getContactPerson()->getContact()->getMobileNumber()) && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()) {
                                if (null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber()) {
                                    $mobileNumber = $customerData->getContactPerson()->getContact()->getMobileNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getMobileNumber()->getNumber();
                                    try {
                                        $phoneNumberObject = $this->phoneNumberUtil->parse($mobileNumber);
                                        $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                                    } catch (\Exception $e) {
                                        $this->logger->warning('Error while migrating mobile number of Corporate Customer : '.$e);
                                        $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                    }
                                }
                            }

                            if (!empty($customerData->getContactPerson()->getContact()->getPhoneNumber()) && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()) {
                                if (null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode() && null !== $customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber()) {
                                    $phoneNumber = $customerData->getContactPerson()->getContact()->getPhoneNumber()->getCountryCode().$customerData->getContactPerson()->getContact()->getPhoneNumber()->getNumber();
                                    try {
                                        $phoneNumberObject = $this->phoneNumberUtil->parse($phoneNumber);
                                        $contactPoint->addTelephoneNumber($phoneNumberObject);
                                    } catch (\Exception $e) {
                                        $this->logger->warning('Error while migrating phone number of Corporate Customer : '.$e);
                                        $this->logger->warning(\json_encode($customerData, JSON_PRETTY_PRINT));
                                    }
                                }
                            }
                            $corporation->addContactPoint($contactPoint);
                        }
                    }
                    $this->entityManager->persist($corporation);
                }
            }
            $this->entityManager->persist($customer);

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        //remove for now
        //$this->entityManager->clear();
    }

    /**
     * Update customers referral code into database.
     *
     * @param array $customers
     *
     * @throws \Exception
     */
    public function updateCustomerReferralCode(array $customers)
    {
        $batchSize = 1000;
        foreach ($customers as $key => $customerData) {
            $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerData->getCustomerId()]);

            if (null !== $customer) {
                if (null === $customer->getReferralCode() || empty($customer->getReferralCode())) {
                    if (!empty($customerData->getReferralCode()) && null !== $customerData->getReferralCode()) {
                        $customer->setReferralCode($customerData->getReferralCode());
                        $this->entityManager->persist($customer);
                    }
                }
            }

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
    }

    private function mapCustomerAccountType(string $type)
    {
        $typesMap = [
            'CORPORATE' => new AccountType(AccountType::CORPORATE),
            'INDIVIDUAL' => new AccountType(AccountType::INDIVIDUAL),
        ];

        return $typesMap[$type];
    }

    private function mapCustomerActivityCallDirection(string $direction)
    {
        $typesMap = [
            'INBOUND' => new CallDirection(CallDirection::INBOUND),
            'OUTBOUND' => new CallDirection(CallDirection::OUTBOUND),
        ];

        return $typesMap[$direction];
    }

    private function mapCustomerContactMethod(string $method)
    {
        $typesMap = [
            'BOTH' => new ContactMethod(ContactMethod::EMAIL_AND_PHONE),
            'EMAIL' => new ContactMethod(ContactMethod::EMAIL),
            'PHONE' => new ContactMethod(ContactMethod::PHONE),
        ];

        return $typesMap[$method];
    }

    private function mapCustomerRelationshipType(string $relation)
    {
        if (false !== \stripos($relation, 'CONTACT_PERSON') || false !== \stripos($relation, 'MANAGE_SSP')) {
            return new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON);
        } elseif (false !== \stripos($relation, 'PARTNER_CONTACT_PERSON')) {
            return new CustomerRelationshipType(CustomerRelationshipType::PARTNER_CONTACT_PERSON);
        } elseif (false !== \stripos($relation, 'POWER_OF_ATTORNEY')) {
            return new CustomerRelationshipType(CustomerRelationshipType::POWER_OF_ATTORNEY);
        } elseif (false !== \stripos($relation, 'TENANT')) {
            return new CustomerRelationshipType(CustomerRelationshipType::TENANT);
        }
    }

    private function mapCustomerAccountStatus(string $status)
    {
        $typesMap = [
            'ACTIVE' => new CustomerAccountStatus(CustomerAccountStatus::ACTIVE),
            'INACTIVE' => new CustomerAccountStatus(CustomerAccountStatus::INACTIVE),
        ];

        return $typesMap[$status];
    }

    private function mapCustomerAddressType(string $address)
    {
        $typesMap = [
            'CORRES_ADDRESS' => new PostalAddressType(PostalAddressType::CORRESPONDENCE_ADDRESS),
            'CORRESPONDENCE_ADDRESS' => new PostalAddressType(PostalAddressType::CORRESPONDENCE_ADDRESS),
            'BILL_ADDRESS' => new PostalAddressType(PostalAddressType::MAILING_ADDRESS),
            'MAILING_ADDRESS' => new PostalAddressType(PostalAddressType::MAILING_ADDRESS),
            'PREMISE_ADDRESS' => new PostalAddressType(PostalAddressType::PREMISE_ADDRESS),
            'REFUND_ADDRESS' => new PostalAddressType(PostalAddressType::REFUND_ADDRESS),
        ];

        return $typesMap[$address];
    }

    private function mapCorporateIndustry(string $industry)
    {
        $typesMap = [
            'CHARITABLE_ORGANISATIONS' => new Industry(Industry::CHARITABLE_ORGANISATIONS),
            'CONSTRUCTION' => new Industry(Industry::CONSTRUCTION),
            'DORMITORIES' => new Industry(Industry::DORMITORIES),
            'EDUCATIONAL_INSTITUTIONS' => new Industry(Industry::EDUCATIONAL_INSTITUTIONS),
            'ELECTRONICS_SEMICONDUCTORS' => new Industry(Industry::ELECTRONICS_SEMICONDUCTORS),
            'F_B_OUTLETS' => new Industry(Industry::F_B_OUTLETS),
            'HOTELS' => new Industry(Industry::HOTELS),
            'LOGISTICS' => new Industry(Industry::LOGISTICS),
            'MCST_CONDOS' => new Industry(Industry::MCST_CONDOS),
            'OFFICE_REAL_ESTATE' => new Industry(Industry::OFFICE_REAL_ESTATE),
            'OTHER_HEAVY_MANUFACTURING' => new Industry(Industry::OTHER_HEAVY_MANUFACTURING),
            'OTHER_LIGHT_MANUFACTURING' => new Industry(Industry::OTHER_LIGHT_MANUFACTURING),
            'OTHERS' => new Industry(Industry::OTHERS),
            'PHARMACEUTICALS' => new Industry(Industry::PHARMACEUTICALS),
            'PORTS' => new Industry(Industry::PORTS),
            'PRECISION_INDUSTRIES' => new Industry(Industry::PRECISION_INDUSTRIES),
            'REFINERIES_PETROCHEMICALS' => new Industry(Industry::REFINERIES_PETROCHEMICALS),
            'RETAIL_OUTLETS' => new Industry(Industry::RETAIL_OUTLETS),
            'SHOPPING_MALLS' => new Industry(Industry::SHOPPING_MALLS),
            'TRANSPORTATION' => new Industry(Industry::TRANSPORTATION),
        ];

        return $typesMap[$industry];
    }

    private function mapGender(string $gender)
    {
        $typesMap = [
            'MALE' => new GenderType(GenderType::MALE),
            'FEMALE' => new GenderType(GenderType::FEMALE),
        ];

        return $typesMap[$gender];
    }

    private function mapIdentificationName(string $identity)
    {
        $typesMap = [
            'COMPANY_REGISTRATION_NUMBER' => new IdentificationName(IdentificationName::COMPANY_REGISTRATION_NUMBER),
            'MALAYSIA_IDENTITY_CARD' => new IdentificationName(IdentificationName::MALAYSIA_IDENTITY_CARD),
            'NATIONAL_REGISTRATION_IDENTITY_CARD' => new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD),
            'UNIQUE_ENTITY_NUMBER' => new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER),
        ];

        return $typesMap[$identity];
    }

    private function mapMaritalStatus(string $marital)
    {
        $typesMap = [
            'DIVORCED' => new MaritalStatus(MaritalStatus::DIVORCED),
            'MARRIED' => new MaritalStatus(MaritalStatus::MARRIED),
            'NOT_REPORTED' => new MaritalStatus(MaritalStatus::NOT_REPORTED),
            'SEPARATED' => new MaritalStatus(MaritalStatus::SEPARATED),
            'SINGLE' => new MaritalStatus(MaritalStatus::SINGLE),
            'WIDOWED' => new MaritalStatus(MaritalStatus::WIDOWED),
        ];

        return $typesMap[$marital];
    }

    private function mapNoteType(string $type)
    {
        $typesMap = [
            'ALERT' => new NoteType(NoteType::ALERT),
            'DESCRIPTION_NOTE' => new NoteType(NoteType::DESCRIPTION_NOTE),
            'FOLLOW_UP' => new NoteType(NoteType::FOLLOW_UP),
            'GENERAL' => new NoteType(NoteType::GENERAL),
            'INTERNAL_NOTE' => new NoteType(NoteType::INTERNAL_NOTE),
            'LOST_REASON' => new NoteType(NoteType::LOST_REASON),
            'OTHERS' => new NoteType(NoteType::OTHERS),
            'REJECT_REASON' => new NoteType(NoteType::REJECT_REASON),
            'RESOLUTION_NOTE' => new NoteType(NoteType::RESOLUTION_NOTE),
            'TASK' => new NoteType(NoteType::TASK),
        ];

        return $typesMap[$type];
    }

    private function mapSource(string $source)
    {
        $typesMap = [
            'ADVERTISEMENT' => new Source(Source::ADVERTISEMENT),
            'APPLICATION_REQUEST' => new Source(Source::APPLICATION_REQUEST),
            'CLIENT_HOMEPAGE' => new Source(Source::CLIENT_HOMEPAGE),
            'EMAIL' => new Source(Source::EMAIL),
            'FACE_TO_FACE' => new Source(Source::FACE_TO_FACE),
            'HOMEPAGE' => new Source(Source::CLIENT_HOMEPAGE),
            'LEAD' => new Source(Source::LEAD),
            'LETTER' => new Source(Source::LETTER),
            'MANUAL_ENTRY' => new Source(Source::MANUAL_ENTRY),
            'MIGRATED' => new Source(Source::MIGRATED),
            'PARTNER' => new Source(Source::PARTNERSHIP_PORTAL),
            'PARTNERSHIP_PORTAL' => new Source(Source::PARTNERSHIP_PORTAL),
            'PHONE' => new Source(Source::TELEPHONE),
            'QUOTATION' => new Source(Source::QUOTATION),
            'SELF_SERVICE_PORTAL' => new Source(Source::SELF_SERVICE_PORTAL),
            'SSP' => new Source(Source::SELF_SERVICE_PORTAL),
            'TELEPHONE' => new Source(Source::TELEPHONE),
            'UCRM' => new Source(Source::MANUAL_ENTRY),
        ];

        return $typesMap[$source]->getValue();
    }
}
