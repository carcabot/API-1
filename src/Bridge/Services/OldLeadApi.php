<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 18/1/19
 * Time: 2:46 PM.
 */

namespace App\Bridge\Services;

use App\Document\OldLeadIds;
use App\Document\OldUsers;
use App\Document\Tariff;
use App\Entity\BridgeUser;
use App\Entity\ContactPoint;
use App\Entity\Corporation;
use App\Entity\DigitalDocument;
use App\Entity\EmailActivity;
use App\Entity\Identification;
use App\Entity\Lead;
use App\Entity\Note;
use App\Entity\Person;
use App\Entity\PhoneContactActivity;
use App\Entity\PostalAddress;
use App\Entity\QuantitativeValue;
use App\Entity\RunningNumber;
use App\Entity\TariffRate;
use App\Enum\AccountType;
use App\Enum\CallDirection;
use App\Enum\ContactMethod;
use App\Enum\ContractType;
use App\Enum\DwellingType;
use App\Enum\IdentificationName;
use App\Enum\Industry;
use App\Enum\LeadScore;
use App\Enum\LeadStatus;
use App\Enum\MeterType;
use App\Enum\NoteType;
use App\Enum\PostalAddressType;
use App\Enum\ReferralSource;
use App\Enum\Source;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use libphonenumber\PhoneNumberUtil as PhoneNumberUtil;
use Psr\Log\LoggerInterface;

final class OldLeadApi
{
    /**
     * @var HttpUri
     */
    private $baseUri;

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
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->baseUri = HttpUri::createFromString($bridgeApiUrl);
    }

    /**
     * Create lead ids into database.
     *
     * @param OldLeadIds $leadId
     */
    public function createIds(OldLeadIds $leadId)
    {
        $runningNumber = new RunningNumber();

        if (false === \stripos($leadId->getPrefix(), 'L')) {
            if (!empty($leadId->getNextNumber()) && null !== $leadId->getNextNumber()) {
                $runningNumber->setNumber($leadId->getNextNumber() - 1);
            }

            if (!empty($leadId->getDatePrefix()) && false !== $leadId->getDatePrefix()) {
                $runningNumber->setSeries('ym');
            }
            $runningNumber->setType('lead');

            $this->entityManager->persist($runningNumber);
            $this->entityManager->flush();
        }
    }

    /**
     * Create lead into database.
     *
     * @param array $leadDoc
     */
    public function createLead(array $leadDoc)
    {
        $batchSize = 600;

        $qb = $this->entityManager->getRepository(Lead::class)->createQueryBuilder('lead');
        $expr = $qb->expr();

        $qb->delete()
            ->where('lead.id > :number')
            ->setParameter('number', -1)
            ->getQuery()
            ->execute();

        foreach ($leadDoc as $key => $leadData) {
            $lead = new Lead();

            if (!empty($leadData->getLeadId())) {
                $existingLead = $this->entityManager->getRepository(Lead::class)->findOneBy(['leadNumber' => $leadData->getLeadId()]);

                if (null !== $existingLead) {
                    $lead = $existingLead;
                } else {
                    $lead->setLeadNumber($leadData->getLeadId());
                }
            }

            if (!empty($leadData->getEmployeeResponseId())) {
                $bridgeUser = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $leadData->getEmployeeResponseId()]);
                if (null !== $bridgeUser) {
                    $lead->setAssignee($bridgeUser->getUser());
                }
            }

            if (!empty($leadData->getCreatedBy())) {
                $bridgeUser = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $leadData->getCreatedBy()]);
                if (null !== $bridgeUser) {
                    $lead->setAssignor($bridgeUser->getUser());
                }
            }

            if (!empty($leadData->getConsumptionAmount())) {
                $lead->setAverageConsumption(new QuantitativeValue((string) $leadData->getConsumptionAmount()));
            }

            if (!empty($leadData->getCreatedBy())) {
                $creatorDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $leadData->getCreatedBy()]);
                if (null !== $creatorDocument) {
                    $creator = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $creatorDocument->getId()]);
                    if (null !== $creator) {
                        $lead->setCreator($creator->getUser());
                    }
                }
            }

            if (!empty($leadData->getUpdatedBy())) {
                $agentDocument = $this->documentManager->getRepository(OldUsers::class)->findOneBy(['id' => $leadData->getUpdatedBy()]);
                if (null !== $agentDocument) {
                    $agent = $this->entityManager->getRepository(BridgeUser::class)->findOneBy(['bridgeUserId' => $agentDocument->getId()]);
                    if (null !== $agent) {
                        $lead->setAgent($agent->getUser());
                    }
                }
            }

            if (!empty($leadData->getCreatedAt())) {
                $lead->setDateCreated($leadData->getCreatedAt());
            }

            if (!empty($leadData->getUpdatedAt())) {
                $lead->setDateModified($leadData->getUpdatedAt());
            }

            if (null !== $leadData->getCategory()) {
                $lead->setType($this->mapLeadType($leadData->getCategory()));
            }

            if (null !== $leadData->getContractType()) {
                $lead->setContractType($this->mapContractType($leadData->getContractType()));
            }

            if (null !== $leadData->getDwellingType() && !empty($leadData->getDwellingType())) {
                $lead->setContractSubtype($this->mapDwellingType($leadData->getDwellingType()));
            }

            if (!empty($leadData->getFollowUpDate())) {
                $lead->setDateFollowedUp($leadData->getFollowUpDate());
            }

            if (null !== $leadData->getExistingCustomer()) {
                $lead->setExistingCustomer($leadData->getExistingCustomer());
            }

            if (null !== $leadData->getMeterType()) {
                $lead->setMeterType($this->mapMeterType($leadData->getMeterType()));
            }
            if (!empty($leadData->getNote())) {
                $oldNote = $leadData->getNote();
                if (isset($oldNote['_id'])) {
                    $oldNote = [$oldNote];
                }
                if (null !== $oldNote && \count($oldNote) > 0) {
                    foreach ($oldNote as $noteData) {
                        if (!empty($noteData['desc'])) {
                            $note = new Note();

                            $note->setText($noteData['desc']);

                            if (!empty($noteData['note_attached']) && \count($noteData['note_attached']) > 0) {
                                foreach ($noteData['note_attached'] as $attachedData) {
                                    $file = new DigitalDocument();
                                    $file->setUrl($attachedData);
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
                            $lead->addNote($note);
                        }
                    }
                }
            }

            if (!empty($leadData->getPurchaseTimeFrame())) {
                $lead->setPurchaseTimeFrame(new QuantitativeValue((string) $leadData->getPurchaseTimeFrame()));
            }

            if (null !== $leadData->getRate()) {
                $lead->setScore($this->mapLeadScore($leadData->getRate()));
            }

            if (!empty($leadData->getReferenceSource()) && null !== $leadData->getReferenceSource()) {
                $lead->setReferralSource($this->mapReferralSource($leadData->getReferenceSource()));
            }

            if (!empty($leadData->getReferenceSource()) && false !== \stripos($leadData->getReferenceSource(), 'OTHERS')) {
                if (!empty($leadData->getIndicate()) && null !== $leadData->getIndicate()) {
                    $lead->setSpecifiedReferralSource($leadData->getIndicate());
                }
            }

            if (!empty($leadData->getStatus())) {
                $lead->setStatus($this->mapLeadStatus($leadData->getStatus()));
            }

            if (!empty($leadData->getSource())) {
                $lead->setSource($this->mapSource($leadData->getSource()));
            }

            if (!empty($leadData->getTariffRateId())) {
                $tariffDocument = $this->documentManager->getRepository(Tariff::class)->findOneBy(['id' => $leadData->getTariffRateId()]);
                if (null !== $tariffDocument) {
                    $tariffRate = $this->entityManager->getRepository(TariffRate::class)->findOneBy([
                        'tariffRateNumber' => $tariffDocument->getTariffCode(),
                        'isBasedOn' => null,
                    ]);
                    if (null !== $tariffRate) {
                        $lead->setTariffRate($tariffRate);
                    }
                }
            }
            if (null !== $leadData->getContactPerson()) {
                if (null !== $leadData->getContactPerson()->getLeadContact()) {
                    if (null !== $leadData->getContactPerson()->getLeadContact()->getDoNotContact()) {
                        $lead->setDoNotContact($leadData->getContactPerson()->getLeadContact()->getDoNotContact());
                    }

                    if (!empty($leadData->getContactPerson()->getLeadContact()->getPreferContactMethod()) && null !== $leadData->getContactPerson()->getLeadContact()->getPreferContactMethod()) {
                        $lead->setPreferredContactMethod($this->mapLeadContactMethod($leadData->getContactPerson()->getLeadContact()->getPreferContactMethod()));
                    }

                    if (!empty($leadData->getContactPerson()->getLeadContact()->getAddress()) && null !== $leadData->getContactPerson()->getLeadContact()->getAddress()) {
                        $address = new PostalAddress();

                        if (!empty($leadData->getContactPerson()->getLeadContact()->getAddress()->getAddressType()) && null !== $leadData->getContactPerson()->getLeadContact()->getAddress()->getAddressType()) {
                            $address->setType($this->mapLeadAddressType($leadData->getContactPerson()->getLeadContact()->getAddress()->getAddressType()));
                        }

                        if (!empty($leadData->getContactPerson()->getLeadContact()->getAddress()->getCountry()) && null !== $leadData->getContactPerson()->getLeadContact()->getAddress()->getAddressType()) {
                            if (2 === \strlen($leadData->getContactPerson()->getLeadContact()->getAddress()->getCountry())) {
                                $address->setAddressCountry($leadData->getContactPerson()->getLeadContact()->getAddress()->getCountry());
                            } elseif (false !== \stripos($leadData->getContactPerson()->getLeadContact()->getAddress()->getCountry(), 'singapore')) {
                                $address->setAddressCountry('SG');
                            }
                        }

                        if (!empty($leadData->getContactPerson()->getLeadContact()->getAddress()->getCity()) && null !== $leadData->getContactPerson()->getLeadContact()->getAddress()->getCity()) {
                            $address->setAddressLocality($leadData->getContactPerson()->getLeadContact()->getAddress()->getCity());
                        }

                        if (!empty($leadData->getContactPerson()->getLeadContact()->getAddress()->getBuildingName()) && null !== $leadData->getContactPerson()->getLeadContact()->getAddress()->getBuildingName()) {
                            $address->setBuildingName($leadData->getContactPerson()->getLeadContact()->getAddress()->getBuildingName());
                        }

                        if (!empty($leadData->getContactPerson()->getLeadContact()->getAddress()->getFloor()) && null !== $leadData->getContactPerson()->getLeadContact()->getAddress()->getFloor()) {
                            $address->setFloor($leadData->getContactPerson()->getLeadContact()->getAddress()->getFloor());
                        }

                        if (!empty($leadData->getContactPerson()->getLeadContact()->getAddress()->getHouseNumber()) && null !== $leadData->getContactPerson()->getLeadContact()->getAddress()->getHouseNumber()) {
                            $address->setHouseNumber($leadData->getContactPerson()->getLeadContact()->getAddress()->getHouseNumber());
                        }

                        if (!empty($leadData->getContactPerson()->getLeadContact()->getAddress()->getPostCode()) && null !== $leadData->getContactPerson()->getLeadContact()->getAddress()->getPostCode()) {
                            $address->setPostalCode((string) $leadData->getContactPerson()->getLeadContact()->getAddress()->getPostCode());
                        }

                        if (!empty($leadData->getContactPerson()->getLeadContact()->getAddress()->getRegion()) && null !== $leadData->getContactPerson()->getLeadContact()->getAddress()->getRegion()) {
                            $address->setAddressRegion($leadData->getContactPerson()->getLeadContact()->getAddress()->getRegion());
                        }

                        if (!empty($leadData->getContactPerson()->getLeadContact()->getAddress()->getStreet()) && null !== $leadData->getContactPerson()->getLeadContact()->getAddress()->getStreet()) {
                            $address->setStreetAddress($leadData->getContactPerson()->getLeadContact()->getAddress()->getStreet());
                        }

                        if (!empty($leadData->getContactPerson()->getLeadContact()->getAddress()->getUnitNumber()) && null !== $leadData->getContactPerson()->getLeadContact()->getAddress()->getUnitNumber()) {
                            $address->setUnitNumber($leadData->getContactPerson()->getLeadContact()->getAddress()->getUnitNumber());
                        }

                        $lead->addAddress($address);
                    }

                    $person = $lead->getPersonDetails();

                    if (null === $person) {
                        $person = new Person();
                        $lead->setPersonDetails($person);
                    }

                    if (!empty($leadData->getContactPerson()->getAdditionalName())) {
                        $person->setAdditionalName($leadData->getContactPerson()->getAdditionalName());
                    }

                    if (null !== $leadData->getContactPerson()->getDesignation()) {
                        $person->setJobTitle($leadData->getContactPerson()->getDesignation());
                    }

                    if (!empty($leadData->getContactPerson()->getFamilyName())) {
                        $person->setFamilyName($leadData->getContactPerson()->getFamilyName());
                    }

                    if (!empty($leadData->getContactPerson()->getGivenName())) {
                        $person->setGivenName($leadData->getContactPerson()->getGivenName());
                    }

                    if (!empty($leadData->getContactPerson()->getHonorificPrefix())) {
                        $person->setHonorificPrefix($leadData->getContactPerson()->getHonorificPrefix());
                    }

                    if (!empty($leadData->getContactPerson()->getName())) {
                        $person->setName($leadData->getContactPerson()->getName());
                    }

                    if (null !== $leadData->getContactPerson()->getLeadContact()->getSocialMediaAccount() && \count($leadData->getContactPerson()->getLeadContact()->getSocialMediaAccount()) > 0) {
                        foreach ($leadData->getContactPerson()->getLeadContact()->getSocialMediaAccount() as $socialMediaAccount) {
                            if (null !== $socialMediaAccount['social_url']) {
                                $person->addSameAsUrl($socialMediaAccount['social_url']);
                            }
                        }
                    }

                    if (null !== $leadData->getContactPerson()->getLeadContact()->getWebsite()) {
                        $person->addSameAsUrl($leadData->getContactPerson()->getLeadContact()->getWebsite());
                    }

                    $personContactPoint = new ContactPoint();

                    if (null !== $leadData->getContactPerson()->getLeadContact()->getEmail()) {
                        $personContactPoint->addEmail($leadData->getContactPerson()->getLeadContact()->getEmail());
                    }

                    if (null !== $leadData->getContactPerson()->getLeadContact()->getFaxNumber()) {
                        if (null !== $leadData->getContactPerson()->getLeadContact()->getFaxNumber()->getCountryCode() && null !== $leadData->getContactPerson()->getLeadContact()->getFaxNumber()->getNumber()) {
                            $faxNumber = $leadData->getContactPerson()->getLeadContact()->getFaxNumber()->getCountryCode().$leadData->getContactPerson()->getLeadContact()->getFaxNumber()->getNumber();
                            try {
                                $phoneNumberObject = $this->phoneNumberUtil->parse($faxNumber);
                                $personContactPoint->addFaxNumber($phoneNumberObject);
                            } catch (\Exception $e) {
                                $this->logger->info('Error while migrating fax number of Individual lead : '.$e);
                                $this->logger->info(\json_encode($leadData, JSON_PRETTY_PRINT));
                            }
                        }
                    }

                    if (null !== $leadData->getContactPerson()->getLeadContact()->getMobileNumber()) {
                        if (null !== $leadData->getContactPerson()->getLeadContact()->getMobileNumber()->getCountryCode() && null !== $leadData->getContactPerson()->getLeadContact()->getMobileNumber()->getNumber()) {
                            $mobileNumber = $leadData->getContactPerson()->getLeadContact()->getMobileNumber()->getCountryCode().$leadData->getContactPerson()->getLeadContact()->getMobileNumber()->getNumber();
                            try {
                                $phoneNumberObject = $this->phoneNumberUtil->parse($mobileNumber);
                                $personContactPoint->addMobilePhoneNumber($phoneNumberObject);
                            } catch (\Exception $e) {
                                $this->logger->info('Error while migrating mobile number of Individual lead : '.$e);
                                $this->logger->info(\json_encode($leadData, JSON_PRETTY_PRINT));
                            }
                        }
                    }

                    if (null !== $leadData->getContactPerson()->getLeadContact()->getPhoneNumber()) {
                        if (null !== $leadData->getContactPerson()->getLeadContact()->getPhoneNumber()->getCountryCode() && null !== $leadData->getContactPerson()->getLeadContact()->getPhoneNumber()->getNumber()) {
                            $phoneNumber = $leadData->getContactPerson()->getLeadContact()->getPhoneNumber()->getCountryCode().$leadData->getContactPerson()->getLeadContact()->getPhoneNumber()->getNumber();
                            try {
                                $phoneNumberObject = $this->phoneNumberUtil->parse($phoneNumber);
                                $personContactPoint->addTelephoneNumber($phoneNumberObject);
                            } catch (\Exception $e) {
                                $this->logger->info('Error while migrating phone number of Individual lead : '.$e);
                                $this->logger->info(\json_encode($leadData, JSON_PRETTY_PRINT));
                            }
                        }
                    }

                    $person->addContactPoint($personContactPoint);

                    if (null !== $leadData->getContactPerson()->getIdentification()) {
                        if (!empty($leadData->getContactPerson()->getIdentification()->getNricFin())) {
                            $identifier = new Identification();

                            if (!empty($leadData->getContactPerson()->getIdentification()->getDescription())) {
                                $identifier->setDescription($leadData->getContactPerson()->getIdentification()->getDescription());
                            }

                            if (null !== $leadData->getContactPerson()->getIdentification()->getNricFin()) {
                                $identifier->setName($this->mapIdentificationName('NATIONAL_REGISTRATION_IDENTITY_CARD'));
                                $identifier->setValue($leadData->getContactPerson()->getIdentification()->getNricFin());
                            }

                            if (!empty($leadData->getContactPerson()->getIdentification()->getValidFrom())) {
                                $identifier->setValidFrom($leadData->getContactPerson()->getIdentification()->getValidFrom());
                            }

                            if (!empty($leadData->getContactPerson()->getIdentification()->getValidThrough())) {
                                $identifier->setValidThrough($leadData->getContactPerson()->getIdentification()->getValidThrough());
                            }

                            if (null !== $lead->getCreator()) {
                                $identifier->setCreator($lead->getCreator());
                            }

                            if (null !== $lead->getAgent()) {
                                $identifier->setAgent($lead->getAgent());
                            }

                            if (!empty($leadData->getCreatedAt())) {
                                $identifier->setDateCreated($leadData->getCreatedAt());
                            }

                            if (!empty($leadData->getUpdatedAt())) {
                                $identifier->setDateModified($leadData->getUpdatedAt());
                            }

                            $person->addIdentifier($identifier);
                        }
                    }

                    if (false !== \stripos($leadData->getCategory(), 'CORPORATE')) {
                        $corporation = $lead->getCorporationDetails();

                        if (null === $corporation) {
                            $corporation = new Corporation();
                            $lead->setCorporationDetails($corporation);
                        }

                        if (!empty($leadData->getContactPerson()->getCompanyName())) {
                            $corporation->setName($leadData->getContactPerson()->getCompanyName());
                        }

                        if (null !== $leadData->getContactPerson()->getIndustry()) {
                            $industry = $this->mapCorporateIndustry($leadData->getContactPerson()->getIndustry());
                            $corporation->setIndustry($industry);

                            if (null !== $industry) {
                                $lead->setContractSubtype($industry->getValue());
                            }
                        }

                        if (null !== $leadData->getContactPerson()->getIdentification()) {
                            if (!empty($leadData->getContactPerson()->getIdentification()->getUen())) {
                                $identifier = new Identification();

                                if (!empty($leadData->getContactPerson()->getIdentification()->getDescription())) {
                                    $identifier->setDescription($leadData->getContactPerson()->getIdentification()->getDescription());
                                }

                                if (null !== $leadData->getContactPerson()->getIdentification()->getUen()) {
                                    $identifier->setName($this->mapIdentificationName('UNIQUE_ENTITY_NUMBER'));
                                    $identifier->setValue($leadData->getContactPerson()->getIdentification()->getUen());
                                }

                                if (!empty($leadData->getContactPerson()->getIdentification()->getValidFrom())) {
                                    $identifier->setValidFrom($leadData->getContactPerson()->getIdentification()->getValidFrom());
                                }

                                if (!empty($leadData->getContactPerson()->getIdentification()->getValidThrough())) {
                                    $identifier->setValidThrough($leadData->getContactPerson()->getIdentification()->getValidThrough());
                                }

                                if (null !== $lead->getCreator()) {
                                    $identifier->setCreator($lead->getCreator());
                                }

                                if (null !== $lead->getAgent()) {
                                    $identifier->setAgent($lead->getAgent());
                                }

                                if (!empty($leadData->getCreatedAt())) {
                                    $identifier->setDateCreated($leadData->getCreatedAt());
                                }

                                if (!empty($leadData->getUpdatedAt())) {
                                    $identifier->setDateModified($leadData->getUpdatedAt());
                                }

                                $corporation->addIdentifier($identifier);
                            }
                        }
                    }
                }
            }

            if (!empty($leadData->getActivity()) && \count($leadData->getActivity()) > 0) {
                foreach ($leadData->getActivity() as $activityData) {
                    if (!empty($activityData['data']['type'])) {
                        if (false !== \stripos($activityData['data']['type'], 'PHONE')) {
                            $phoneActivity = new PhoneContactActivity();

                            $phoneActivity->setDirection($this->mapActivityCallDirection($activityData['data']['direction']));

                            if (!empty($activityData['data']['phone_number']['country_number']) && !empty($activityData['data']['phone_number']['number'])) {
                                if (null !== $activityData['data']['phone_number']['country_number'] && null !== $activityData['data']['phone_number']['number']) {
                                    $phoneNumber = $activityData['data']['phone_number']['country_number'].$activityData['data']['phone_number']['number'];

                                    try {
                                        $phoneNumberObject = $this->phoneNumberUtil->parse($phoneNumber);
                                        $phoneActivity->setRecipientTelephoneNumber($phoneNumberObject);
                                    } catch (\Exception $e) {
                                        $this->logger->info('Error while migrating phone number of lead Activity : '.$e);
                                        $this->logger->info(\json_encode($leadData, JSON_PRETTY_PRINT));
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
                            $lead->addActivity($phoneActivity);
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
                                $recipients = \explode(';', $activityData['data']['to']);
                                foreach ($recipients as $recipient) {
                                    $emailActivity->addToRecipient(\trim($recipient));
                                }
                            }

                            if (!empty($activityData['data']['message'])) {
                                $emailActivity->setText($activityData['data']['message']);
                            }

                            if (!empty($activityData['data']['attachments'])) {
                                foreach ($activityData['data']['attachments'] as $attachment) {
                                    $messageAttached = new DigitalDocument();

                                    $appendUri = \sprintf('%s/%s', 'quotation', $attachment);

                                    $modifier = new AppendSegment($appendUri);
                                    $messageUri = $modifier->process($this->baseUri);

                                    $messageAttached->setName($attachment);
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

                            $lead->addActivity($emailActivity);
                        }
                    }
                }
            }

            $this->entityManager->persist($lead);
            if (0 === ($key % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function mapActivityCallDirection(string $direction)
    {
        $typesMap = [
            'INBOUND' => new CallDirection(CallDirection::INBOUND),
            'OUTBOUND' => new CallDirection(CallDirection::OUTBOUND),
        ];

        return $typesMap[$direction];
    }

    private function mapContractType(string $contract)
    {
        $typesMap = [
            'COMMERCIAL' => new ContractType(ContractType::COMMERCIAL),
            'RESIDENTIAL' => new ContractType(ContractType::RESIDENTIAL),
        ];

        return $typesMap[$contract];
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

        if (isset($typesMap[$industry])) {
            return $typesMap[$industry];
        }

        return null;
    }

    private function mapDwellingType(string $dwellingType)
    {
        $typesMap = [
            'ROOM1' => DwellingType::ONE_ROOM_FLAT_HDB,
            'ROOM2' => DwellingType::TWO_ROOM_FLAT_HDB,
            'ROOM3' => DwellingType::THREE_ROOM_FLAT_HDB,
            'ROOM4' => DwellingType::FOUR_ROOM_FLAT_HDB,
            'ROOM5' => DwellingType::FIVE_ROOM_FLAT_HDB,
            'CONDO' => DwellingType::CONDOMINIUM,
            'LANDED' => DwellingType::LANDED,
        ];

        if (isset($typesMap[$dwellingType])) {
            return $typesMap[$dwellingType];
        }

        return null;
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

    private function mapLeadAddressType(string $address)
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

    private function mapLeadContactMethod(string $method)
    {
        $typesMap = [
            'EMAIL' => new ContactMethod(ContactMethod::EMAIL),
            'BOTH' => new ContactMethod(ContactMethod::EMAIL_AND_PHONE),
            'PHONE' => new ContactMethod(ContactMethod::PHONE),
        ];

        return $typesMap[$method];
    }

    private function mapLeadScore(string $score)
    {
        $typesMap = [
            'COLD' => new LeadScore(LeadScore::COLD),
            'HOT' => new LeadScore(LeadScore::HOT),
            'WARM' => new LeadScore(LeadScore::WARM),
        ];

        return $typesMap[$score];
    }

    private function mapLeadStatus(string $status)
    {
        $typesMap = [
            'ASSIGNED' => new LeadStatus(LeadStatus::ASSIGNED),
            'CANCELLED' => new LeadStatus(LeadStatus::CANCELLED),
            'CONTACTED' => new LeadStatus(LeadStatus::CONTACTED),
            'CONVERTED' => new LeadStatus(LeadStatus::CONVERTED),
            'DISQUALIFIED' => new LeadStatus(LeadStatus::DISQUALIFIED),
            'DRAFT' => new LeadStatus(LeadStatus::DRAFT),
            'NEW' => new LeadStatus(LeadStatus::NEW),
            'PARTNER_DRAFT' => new LeadStatus(LeadStatus::PARTNER_DRAFT),
            'PENDING' => new LeadStatus(LeadStatus::PENDING),
            'QUOTATION_SUBMITTED' => new LeadStatus(LeadStatus::QUOTATION_SUBMITTED),
            'REJECTED' => new LeadStatus(LeadStatus::REJECTED),
        ];

        return $typesMap[$status];
    }

    private function mapLeadType(string $type)
    {
        $typesMap = [
            'CORPORATE' => new AccountType(AccountType::CORPORATE),
            'INDIVIDUAL' => new AccountType(AccountType::INDIVIDUAL),
        ];

        return $typesMap[$type];
    }

    private function mapMeterType(string $meter)
    {
        $typesMap = [
            'AMI' => new MeterType(MeterType::AMI),
            'SRLP' => new MeterType(MeterType::SRLP),
        ];

        return $typesMap[$meter];
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
            'REJECTION' => new NoteType(NoteType::REJECT_REASON),
            'REJECT_REASON' => new NoteType(NoteType::REJECT_REASON),
            'RESOLUTION_NOTE' => new NoteType(NoteType::RESOLUTION_NOTE),
            'TASK' => new NoteType(NoteType::TASK),
        ];

        return $typesMap[$type];
    }

    private function mapReferralSource(string $source)
    {
        $typesMap = [
            'ANOTHER_WEBSITE' => new ReferralSource(ReferralSource::ANOTHER_WEBSITE),
            'EMAIL_NEWSLETTER' => new ReferralSource(ReferralSource::EMAIL_NEWSLETTER),
            'EVENT_ROADSHOW' => new ReferralSource(ReferralSource::EVENT_ROADSHOW),
            'MRT_BUS' => new ReferralSource(ReferralSource::MRT_BUS),
            'NEWSPAPER_MAGAZINE' => new ReferralSource(ReferralSource::NEWSPAPER_MAGAZINE),
            'NEWSPAPER_MEGAZINE' => new ReferralSource(ReferralSource::NEWSPAPER_MAGAZINE),
            'ONLINE_ADVERTISEMENT' => new ReferralSource(ReferralSource::ONLINE_ADVERTISEMENT),
            'ONLINE_ADVERTISING' => new ReferralSource(ReferralSource::ONLINE_ADVERTISEMENT),
            'OTHERS' => new ReferralSource(ReferralSource::OTHERS),
            'POSTCARD_LETTER' => new ReferralSource(ReferralSource::POSTCARD_LETTER),
            'RADIO_TV' => new ReferralSource(ReferralSource::RADIO_TV),
            'REFERRAL' => new ReferralSource(ReferralSource::REFERRAL),
            'SOCIAL_MEDIA' => new ReferralSource(ReferralSource::SOCIAL_MEDIA),
        ];

        return $typesMap[$source];
    }

    private function mapSource(string $source)
    {
        $typesMap = [
            'HOMEPAGE' => Source::CLIENT_HOMEPAGE,
            'LEAD' => Source::LEAD,
            'PARTNER' => Source::PARTNERSHIP_PORTAL,
        ];

        return $typesMap[$source];
    }
}
