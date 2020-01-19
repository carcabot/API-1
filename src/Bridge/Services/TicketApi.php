<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 4/2/19
 * Time: 11:01 AM.
 */

namespace App\Bridge\Services;

use App\Document\Contract;
use App\Document\OldCustomerAccount;
use App\Document\OldTicket;
use App\Document\OldTicketCategories;
use App\Document\OldTicketIds;
use App\Document\OldTicketType;
use App\Document\OldUsers;
use App\Entity\BridgeUser;
use App\Entity\ContactPoint;
use App\Entity\CustomerAccount;
use App\Entity\DigitalDocument;
use App\Entity\EmailActivity;
use App\Entity\Note;
use App\Entity\Person;
use App\Entity\PhoneContactActivity;
use App\Entity\RunningNumber;
use App\Entity\Ticket;
use App\Entity\TicketCategory;
use App\Entity\TicketType;
use App\Enum\CallDirection;
use App\Enum\NoteType;
use App\Enum\Priority;
use App\Enum\Source;
use App\Enum\TicketCategoryType;
use App\Enum\TicketStatus;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use libphonenumber\PhoneNumberUtil as PhoneNumberUtil;
use Psr\Log\LoggerInterface;

final class TicketApi
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
    private $phoneNumberUtils;

    /**
     * @param string                 $bridgeApiUrl
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param PhoneNumberUtil        $phoneNumberUtils
     */
    public function __construct(string $bridgeApiUrl, DocumentManager $documentManager, EntityManagerInterface $entityManager, LoggerInterface $logger, PhoneNumberUtil $phoneNumberUtils)
    {
        $this->documentManager = $documentManager;
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->baseUri = HttpUri::createFromString($this->bridgeApiUrl);
        $this->client = new GuzzleClient();
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->phoneNumberUtils = $phoneNumberUtils;
    }

    /**
     * Create ticket ids into database.
     *
     * @param OldTicketIds $ticketId
     */
    public function createIds(OldTicketIds $ticketId)
    {
        $runningNumber = new RunningNumber();

        if (!empty($ticketId->getNextNumber()) && null !== $ticketId->getNextNumber()) {
            $runningNumber->setNumber($ticketId->getNextNumber());
        }

        if (!empty($ticketId->getDatePrefix()) && false !== $ticketId->getDatePrefix()) {
            $runningNumber->setSeries('ym');
        }
        $runningNumber->setType('ticket');

        $this->entityManager->persist($runningNumber);
        $this->entityManager->flush();
    }

    /**
     * Create ticket into database.
     *
     * @param array $tickets
     */
    public function createTicket(array $tickets)
    {
        $batchSize = 1000;

        foreach ($tickets as $key => $ticketData) {
            $ticket = null;

            if (!empty($ticketData->getTicketId())) {
                $ticket = $this->entityManager->getRepository(Ticket::class)->findOneBy(['ticketNumber' => $ticketData->getTicketId()]);
            }

            if (null === $ticket) {
                $ticket = new Ticket();
            } else {
                continue;
            }

            if (!empty($ticketData->getActualCompletionDate())) {
                $ticket->setDateClosed($ticketData->getActualCompletionDate());
            }

            if (!empty($ticketData->getActualStartDateDate())) {
                $ticket->setDateOpened($ticketData->getActualStartDateDate());
            }

            if (null !== $ticketData->getAttachments() && \count($ticketData->getAttachments()) > 0) {
                foreach ($ticketData->getAttachments() as $attachment) {
                    $supplementaryFiles = new DigitalDocument();

                    if (!empty($attachment['desc'])) {
                        $supplementaryFiles->setText($attachment['desc']);
                    }

                    if (!empty($attachment['attached'])) {
                        $modifier = new AppendSegment($attachment['attached']);
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

                    $ticket->addSupplementaryFile($supplementaryFiles);
                }
            }

            if (!empty($ticketData->getCustomerId())) {
                $customerDocument = $this->documentManager->getRepository(OldCustomerAccount::class)->findOneBy(['id' => $ticketData->getCustomerId()]);
                if (null !== $customerDocument) {
                    $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerDocument->getCustomerId()]);
                    if (null !== $customer) {
                        $ticket->setCustomer($customer);
                    }
                }
            }

            if (!empty($ticketData->getChannel())) {
                $ticket->setChannel($ticketData->getChannel());
            }

            if (!empty($ticketData->getCreatedAt())) {
                $ticket->setDateCreated($ticketData->getCreatedAt());
            }

            if (!empty($ticketData->getCreatedBy())) {
                $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $ticketData->getCreatedBy()]);
                if (null !== $creatorDocument) {
                    $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                    if (null !== $creator) {
                        $ticket->setCreator($creator->getUser());
                    }
                }
            }

            //temp remove to make it work
            /*if (null !== $ticket->getCreator()) {
                $ticket->setAcquiredFrom($ticket->getCreator()->getCustomerAccount());
            }*/

            if (!empty($ticketData->getContractId())) {
                $contractDocument = $this->documentManager->getRepository(Contract::class)->findOneBy(['id' => $ticketData->getContractId()]);
                if (null !== $contractDocument) {
                    $contract = $this->entityManager->getRepository(\App\Entity\Contract::class)->findOneBy(['contractNumber' => $contractDocument->getContractId()]);
                    if (null !== $contract) {
                        $ticket->setContract($contract);
                    }
                }
            }

            if (!empty($ticketData->getDescription())) {
                $ticket->setDescription($ticketData->getDescription());
            }

            if (!empty($ticketData->getEmployeeAssign())) {
                $bridgeUser = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $ticketData->getEmployeeAssign()]);
                if (null !== $bridgeUser) {
                    $ticket->setAssignee($bridgeUser->getUser());
                }
            }

            if (!empty($ticketData->getIncidentDate()) && null !== $ticketData->getIncidentDate()) {
                $ticket->setStartDate($ticketData->getIncidentDate());
            }

            if (!empty($ticketData->getMainCategory())) {
                $mainCategoryDocument = $this->documentManager->getRepository(OldTicketCategories::class)->findOneBy(['id' => $ticketData->getMainCategory()]);
                if (null !== $mainCategoryDocument) {
                    $mainCategory = $this->entityManager->getRepository(TicketCategory::class)->findOneBy(['code' => $mainCategoryDocument->getName()]);
                    if (null !== $mainCategory) {
                        $ticket->setCategory($mainCategory);
                    }
                }
            }

            if (!empty($ticketData->getSubCategory())) {
                $subCategoryDocument = $this->documentManager->getRepository(OldTicketCategories::class)->findOneBy(['id' => $ticketData->getSubCategory()]);
                if (null !== $subCategoryDocument) {
                    $subCategory = $this->entityManager->getRepository(TicketCategory::class)->findOneBy(['code' => $subCategoryDocument->getName()]);
                    if (null !== $subCategory) {
                        $ticket->setSubcategory($subCategory);
                    }
                }
            }

            if (!empty($ticketData->getPlannedCompletionDate())) {
                $ticket->setPlannedCompletionDate($ticketData->getPlannedCompletionDate());
            }

            if (!empty($ticketData->getContactName()) || !empty($ticketData->getContactEmail()) || (!empty($ticketData->getContactMobileNumber()) && null !== $ticketData->getContactMobileNumber()) || (!empty($ticketData->getContactPhoneNumber()) && null !== $ticketData->getContactPhoneNumber())) {
                $person = $ticket->getPersonDetails();

                if (null === $person) {
                    $person = new Person();
                    $ticket->setPersonDetails($person);
                }

                if (!empty($ticketData->getContactName())) {
                    $person->setName($ticketData->getContactName());
                }

                if (\count($person->getContactPoints()) > 0) {
                    foreach ($person->getContactPoints() as $contactPoints) {
                        $person->removeContactPoint($contactPoints);
                    }
                }

                $contactPoint = new ContactPoint();

                if (!empty($ticketData->getContactEmail()) && null !== $ticketData->getContactEmail()) {
                    $contactPoint->addEmail($ticketData->getContactEmail());
                }

                if (!empty($ticketData->getContactMobileNumber()) && null !== $ticketData->getContactMobileNumber()) {
                    if (null !== $ticketData->getContactMobileNumber()->getCountryCode() && null !== $ticketData->getContactMobileNumber()->getNumber()) {
                        $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                        $mobileNumber = $ticketData->getContactMobileNumber()->getCountryCode().$ticketData->getContactMobileNumber()->getNumber();
                        try {
                            $phoneNumberObject = $phoneNumberUtil->parse($mobileNumber);
                            $contactPoint->addMobilePhoneNumber($phoneNumberObject);
                        } catch (\Exception $e) {
                            $this->logger->info('Error while migrating mobile number of ticket: '.$e);
                            $this->logger->info(\json_encode($ticketData, JSON_PRETTY_PRINT));
                        }
                    }
                }

                if (!empty($ticketData->getContactPhoneNumber()) && null !== $ticketData->getContactPhoneNumber()) {
                    if (null !== $ticketData->getContactPhoneNumber()->getCountryCode() && null !== $ticketData->getContactPhoneNumber()->getNumber()) {
                        $phoneNumberUtil = $this->phoneNumberUtils::getInstance();
                        $phoneNumber = $ticketData->getContactPhoneNumber()->getCountryCode().$ticketData->getContactPhoneNumber()->getNumber();
                        try {
                            $phoneNumberObject = $phoneNumberUtil->parse($phoneNumber);
                            $contactPoint->addTelephoneNumber($phoneNumberObject);
                        } catch (\Exception $e) {
                            $this->logger->info('Error while migrating phone number of ticket: '.$e);
                            $this->logger->info(\json_encode($ticketData, JSON_PRETTY_PRINT));
                        }
                    }
                }

                $person->addContactPoint($contactPoint);
                $this->entityManager->persist($person);
            } elseif (!empty($ticketData->getCustomerContactPersonId())) {
                $customerDocument = $this->documentManager->getRepository(OldCustomerAccount::class)->findOneBy(['id' => $ticketData->getCustomerContactPersonId()]);
                if (null !== $customerDocument) {
                    $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $customerDocument->getCustomerId()]);
                    if (null !== $customer) {
                        if (false !== \stripos($customer->getType()->getValue(), 'INDIVIDUAL')) {
                            $person = $customer->getPersonDetails();
                            if (null !== $person) {
                                $ticket->setPersonDetails(clone $person);
                            }
                        } elseif (false !== \stripos($customer->getType()->getValue(), 'CORPORATE')) {
                            $corporation = $customer->getCorporationDetails();
                            if (null !== $corporation) {
                                $person = new Person();
                                $name = $corporation->getName();

                                if (null === $name) {
                                    $name = $corporation->getLegalName();
                                }

                                if (null !== $name) {
                                    $person->setName($name);
                                }

                                if (\count($corporation->getContactPoints()) > 0) {
                                    foreach ($corporation->getContactPoints() as $contactPoint) {
                                        $person->addContactPoint(clone $contactPoint);
                                    }
                                }
                                $ticket->setPersonDetails($person);
                            }
                        }
                    }
                }
            }

            if (!empty($ticketData->getPriority()) && null !== $ticketData->getPriority()) {
                $ticket->setPriority($this->mapPriority($ticketData->getPriority()));
            }

            if (!empty($ticketData->getResolutionOfficer())) {
                $ticket->setResolutionOfficer($ticketData->getResolutionOfficer());
            }

            if (!empty($ticketData->getStatus())) {
                $ticket->setStatus($this->mapTicketStatus($ticketData->getStatus()));
            }

            if (!empty($ticketData->getSource()) && null !== $ticketData->getSource()) {
                $ticket->setSource($this->mapSource($ticketData->getSource()));
            }

            if (!empty($ticketData->getTicketId())) {
                $ticket->setTicketNumber($ticketData->getTicketId());
            }

            if (!empty($ticketData->getTicketType())) {
                $ticketTypeDocument = $this->documentManager->getRepository(OldTicketType::class)->findOneBy(['id' => $ticketData->getTicketType()]);
                if (null !== $ticketTypeDocument) {
                    $ticketType = $this->entityManager->getRepository(TicketType::class)->findOneBy(['name' => $ticketTypeDocument->getName()]);
                    if (null !== $ticketType) {
                        $ticket->setType($ticketType);
                    }
                }
            }

            if (!empty($ticketData->getUpdatedAt())) {
                $ticket->setDateModified($ticketData->getUpdatedAt());
            }

            if (!empty($ticketData->getUpdatedBy())) {
                $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $ticketData->getUpdatedBy()]);
                if (null !== $agentDocument) {
                    $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                    if (null !== $agent) {
                        $ticket->setAgent($agent->getUser());
                    }
                }
            }

            $this->entityManager->persist($ticket);
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * Create ticket activity and note into database.
     *
     * @param array $tickets
     *
     * @throws \Exception
     */
    public function createTicketActivityNote(array $tickets)
    {
        $batchSize = 30;

        foreach ($tickets as $key => $ticketData) {
            if (null === $this->entityManager->getRepository(Ticket::class)->findOneBy(['ticketNumber' => $ticketData->getTicketId()])) {
                $this->createTicket($ticketData);
            }
            $ticket = $this->entityManager->getRepository(Ticket::class)->findOneBy(['ticketNumber' => $ticketData->getTicketId()]);

            if (null !== $ticket) {
                if (!empty($ticketData->getActivity()) && \count($ticketData->getActivity()) > 0) {
                    foreach ($ticketData->getActivity() as $activityData) {
                        if (\count($ticket->getActivities()) > 0) {
                            foreach ($ticket->getActivities() as $activity) {
                                $ticket->removeActivity($activity);
                            }
                        }
                        if (!empty($activityData['data']['type'])) {
                            if (false !== \stripos($activityData['data']['type'], 'PHONE')) {
                                $phoneActivity = new PhoneContactActivity();

                                $phoneActivity->setDirection($this->mapCustomerActivityCallDirection($activityData['data']['direction']));

                                $phoneNumberUtil = $this->phoneNumberUtils::getInstance();

                                if (!empty($activityData['data']['phone_number']['country_number']) && !empty($activityData['data']['phone_number']['number'])) {
                                    if (null !== $activityData['data']['phone_number']['country_number'] && null !== $activityData['data']['phone_number']['number']) {
                                        $phoneNumber = $activityData['data']['phone_number']['country_number'].$activityData['data']['phone_number']['number'];

                                        try {
                                            $phoneNumberObject = $phoneNumberUtil->parse($phoneNumber);
                                            $phoneActivity->setRecipientTelephoneNumber($phoneNumberObject);
                                        } catch (\Exception $e) {
                                            $this->logger->info('Error while migrating phone number of ticket Activity : '.$e);
                                            $this->logger->info(\json_encode($ticketData, JSON_PRETTY_PRINT));
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
                                $ticket->addActivity($phoneActivity);
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

                                $ticket->addActivity($emailActivity);
                            }
                        }
                    }
                }

                if (!empty($ticketData->getNote()) && \count($ticketData->getNote()) > 0) {
                    if (\count($ticket->getNotes()) > 0) {
                        foreach ($ticket->getNotes() as $note) {
                            $ticket->removeNote($note);
                        }
                    }
                    foreach ($ticketData->getNote() as $noteData) {
                        if (!empty($noteData['desc']) && null !== $noteData['desc']) {
                            $note = new Note();

                            $note->setText($noteData['desc']);

                            if (!empty($noteData['note_attached']) && \count($noteData['note_attached']) > 0) {
                                foreach ($noteData['note_attached'] as $attachedData) {
                                    $file = new DigitalDocument();

                                    $modifier = new AppendSegment($attachedData);
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

                            $ticket->addNote($note);
                        }
                    }
                }

                if (!empty($ticketData->getComplaintReferenceNumber())) {
                    $parentTicketDocument = $this->documentManager->getRepository(OldTicket::class)->findOneBy(['id' => $ticketData->getComplaintReferenceNumber()]);
                    if (null !== $parentTicketDocument) {
                        if (null === $this->entityManager->getRepository(Ticket::class)->findOneBy(['ticketNumber' => $parentTicketDocument->getTicketId()])) {
                            $this->createTicket([$parentTicketDocument]);
                        }
                        $parentTicket = $this->entityManager->getRepository(Ticket::class)->findOneBy(['ticketNumber' => $parentTicketDocument->getTicketId()]);
                        if (null !== $parentTicket) {
                            $ticket->setParent($parentTicket);
                        }
                    }
                }

                $this->entityManager->persist($ticket);
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
     * Create ticket category into database.
     *
     * @param array $tickets
     *
     * @throws \Exception
     */
    public function createTicketCategory(array $tickets)
    {
        $batchSize = 20;

        foreach ($tickets as $key => $ticketData) {
            $ticketCategory = new TicketCategory();

            if (!empty($ticketData->getCreatedAt())) {
                $ticketCategory->setDateCreated($ticketData->getCreatedAt());
            }

            if (!empty($ticketData->getCreatedBy())) {
                $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $ticketData->getCreatedBy()]);
                if (null !== $creatorDocument) {
                    $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                    if (null !== $creator) {
                        $ticketCategory->setCreator($creator->getUser());
                    }
                }
            }

            if (!empty($ticketData->getDescription())) {
                $ticketCategory->setDescription($ticketData->getDescription());
            }

            if (!empty($ticketData->getName())) {
                $ticketCategory->setName($ticketData->getName());
                $ticketCategory->setCode($ticketData->getName());
            }

            if (null !== $ticketData->getStatus()) {
                $ticketCategory->setEnabled($ticketData->getStatus());
            }

            if (null !== $ticketData->getTaskIndicator()) {
                $ticketCategory->setTaskIndicator($ticketData->getTaskIndicator());
            }

            if (!empty($ticketData->getType())) {
                $ticketCategory->setType($this->mapTicketCategoryType($ticketData->getType()));
            }

            if (!empty($ticketData->getUpdatedAt())) {
                $ticketCategory->setDateModified($ticketData->getUpdatedAt());
            }

            if (!empty($ticketData->getUpdatedBy())) {
                $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $ticketData->getUpdatedBy()]);
                if (null !== $agentDocument) {
                    $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                    if (null !== $agent) {
                        $ticketCategory->setAgent($agent->getUser());
                    }
                }
            }
            $this->entityManager->persist($ticketCategory);

            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * Create ticket type into database.
     *
     * @param array $tickets
     *
     * @throws \Exception
     */
    public function createTicketType(array $tickets)
    {
        $batchSize = 2;

        foreach ($tickets as $key => $ticketData) {
            $ticketType = new TicketType();

            if (!empty($ticketData->getCreatedAt())) {
                $ticketType->setDateCreated($ticketData->getCreatedAt());
            }

            if (!empty($ticketData->getCreatedBy())) {
                $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $ticketData->getCreatedBy()]);
                if (null !== $creatorDocument) {
                    $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                    if (null !== $creator) {
                        $ticketType->setCreator($creator->getUser());
                    }
                }
            }

            if (!empty($ticketData->getName())) {
                $ticketType->setName($ticketData->getName());
            }

            if (null !== $ticketData->getStatus()) {
                $ticketType->setEnabled($ticketData->getStatus());
            }

            if (!empty($ticketData->getUpdatedAt())) {
                $ticketType->setDateModified($ticketData->getUpdatedAt());
            }

            if (!empty($ticketData->getUpdatedBy())) {
                $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $ticketData->getUpdatedBy()]);
                if (null !== $agentDocument) {
                    $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                    if (null !== $agent) {
                        $ticketType->setAgent($agent->getUser());
                    }
                }
            }

            $this->entityManager->persist($ticketType);
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * Create ticket type's category and category's type into database.
     *
     * @param array $tickets
     *
     * @throws \Exception
     */
    public function createTicketTypeCategory(array $tickets)
    {
        $batchSize = 5;

        foreach ($tickets as $key => $ticketData) {
            $ticketCategory = null;
            $ticketType = null;

            if (!empty($ticketData->getTicketCategoryId())) {
                $ticketCategoryDocument = $this->documentManager->getRepository(OldTicketCategories::class)->findOneBy(['id' => $ticketData->getTicketCategoryId()]);
                if (null !== $ticketCategoryDocument) {
                    $ticketCategory = $this->entityManager->getRepository(TicketCategory::class)->findOneBy(['code' => $ticketCategoryDocument->getName()]);
                }
            }

            if (!empty($ticketData->getTicketTypeId())) {
                $ticketTypeDocument = $this->documentManager->getRepository(OldTicketType::class)->findOneBy(['id' => $ticketData->getTicketTypeId()]);
                if (null !== $ticketTypeDocument) {
                    $ticketType = $this->entityManager->getRepository(TicketType::class)->findOneBy(['name' => $ticketTypeDocument->getName()]);
                }
            }

            if (null !== $ticketType && null !== $ticketCategory) {
                if (\count($ticketCategory->getTicketTypes()) > 0) {
                    foreach ($ticketCategory->getTicketTypes() as $ticketTypes) {
                        if ($ticketType->getName() === $ticketTypes->getName()) {
                            $ticketCategory->removeTicketType($ticketTypes);
                        }
                    }
                }
                $ticketCategory->addTicketType($ticketType);

                $this->entityManager->persist($ticketCategory);
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
     * Update ticket category's category into database.
     *
     * @param array $tickets
     *
     * @throws \Exception
     */
    public function updateTicketCategorysCategory(array $tickets)
    {
        $batchSize = 200;

        foreach ($tickets as $key => $ticketData) {
            $ticketCategory = $this->entityManager->getRepository(TicketCategory::class)->findOneBy(['code' => $ticketData->getName()]);

            if (null !== $ticketCategory) {
                if (!empty($ticketData->getType())) {
                    if (false !== \stripos($ticketData->getType(), 'SUB_CATEGORY')) {
                        $mainCategoryDocument = $this->documentManager->getRepository(OldTicketCategories::class)->findOneBy(['id' => $ticketData->getMainCategory()]);
                        if (null !== $mainCategoryDocument) {
                            $mainCategory = $this->entityManager->getRepository(TicketCategory::class)->findOneBy(['code' => $mainCategoryDocument->getName()]);
                            if (null !== $mainCategory) {
                                $mainCategory->addChild($ticketCategory);
                            }
                        }
                    }
                }
                $this->entityManager->persist($ticketCategory);
            }
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function mapCustomerActivityCallDirection(string $direction)
    {
        $typesMap = [
            'INBOUND' => new CallDirection(CallDirection::INBOUND),
            'OUTBOUND' => new CallDirection(CallDirection::OUTBOUND),
        ];

        return $typesMap[$direction];
    }

    private function mapNoteType(string $type)
    {
        $typesMap = [
            'ALERT' => new NoteType(NoteType::ALERT),
            'DESCRIPTION_NOTE' => new NoteType(NoteType::DESCRIPTION_NOTE),
            'FOLLOW_UP' => new NoteType(NoteType::FOLLOW_UP),
            'FOLLOW_UP_ROLE_NOTE' => new NoteType(NoteType::FOLLOW_UP),
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

    private function mapPriority(string $priority)
    {
        $typesMap = [
            'HIGH' => new Priority(Priority::HIGH),
            'LOW' => new Priority(Priority::LOW),
            'MEDIUM' => new Priority(Priority::MEDIUM),
            'VERY_HIGH' => new Priority(Priority::VERY_HIGH),
        ];

        return $typesMap[$priority];
    }

    private function mapSource(string $source)
    {
        $typesMap = [
            'EMAIL' => new Source(Source::EMAIL),
            'FACE_TO_FACE' => new Source(Source::FACE_TO_FACE),
            'LETTER' => new Source(Source::LETTER),
            'PARTNER' => new Source(Source::PARTNERSHIP_PORTAL),
            'PARTNERSHIP_PORTAL' => new Source(Source::PARTNERSHIP_PORTAL),
            'PHONE' => new Source(Source::TELEPHONE),
            'SELF_SERVICE_PORTAL' => new Source(Source::SELF_SERVICE_PORTAL),
            'SSP' => new Source(Source::SELF_SERVICE_PORTAL),
            'TELEPHONE' => new Source(Source::TELEPHONE),
            'UCRM' => new Source(Source::MANUAL_ENTRY),
        ];

        return $typesMap[$source]->getValue();
    }

    private function mapTicketStatus(string $status)
    {
        $typesMap = [
            'ASSIGNED' => new TicketStatus(TicketStatus::ASSIGNED),
            'CANCELLED' => new TicketStatus(TicketStatus::CANCELLED),
            'COMPLETED' => new TicketStatus(TicketStatus::COMPLETED),
            'IN_PROGRESS' => new TicketStatus(TicketStatus::IN_PROGRESS),
            'NEW' => new TicketStatus(TicketStatus::NEW),
            'PENDING_BILLING_TEAM' => new TicketStatus(TicketStatus::PENDING_BILLING_TEAM),
            'PENDING_CUSTOMER_ACTION' => new TicketStatus(TicketStatus::PENDING_CUSTOMER_ACTION),
        ];

        return $typesMap[$status];
    }

    private function mapTicketCategoryType(string $type)
    {
        $typesMap = [
            'MAIN_CATEGORY' => new TicketCategoryType(TicketCategoryType::MAIN_CATEGORY),
            'SUB_CATEGORY' => new TicketCategoryType(TicketCategoryType::SUB_CATEGORY),
        ];

        return $typesMap[$type];
    }
}
