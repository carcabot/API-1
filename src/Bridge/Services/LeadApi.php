<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Entity\BridgeUser;
use App\Entity\Lead;
use App\Enum\AccountType;
use App\Enum\IdentificationName;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class LeadApi
{
    /**
     * @var string
     */
    private $bridgeApiUrl;

    /**
     * @var BridgeDataMapper
     */
    private $bridgeDataMapper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SettingsApi
     */
    private $settingsApi;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @param string           $bridgeApiUrl
     * @param BridgeDataMapper $bridgeDataMapper
     * @param LoggerInterface  $logger
     * @param SettingsApi      $settingsApi
     */
    public function __construct(string $bridgeApiUrl, BridgeDataMapper $bridgeDataMapper, LoggerInterface $logger, SettingsApi $settingsApi)
    {
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->bridgeDataMapper = $bridgeDataMapper;
        $this->logger = $logger;
        $this->settingsApi = $settingsApi;
        $this->baseUri = HttpUri::createFromString($this->bridgeApiUrl);
        $this->client = new GuzzleClient();
    }

    /**
     * Creates a lead in the old version.
     *
     * @param Lead       $lead
     * @param BridgeUser $creator
     *
     * @return string
     */
    public function createLead(Lead $lead, BridgeUser $creator)
    {
        $modifier = new AppendSegment('lead');
        $uri = $modifier->process($this->baseUri);

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'x-access-token' => $creator->getAuthToken(),
        ];

        // map lead data to old version
        $leadData = $this->getLeadData($lead);

        $this->logger->info('Sending POST to '.$uri);
        $this->logger->info(\json_encode($leadData, JSON_PRETTY_PRINT));

        $createLeadRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($leadData));
        $createLeadResponse = $this->client->send($createLeadRequest);
        $createLeadResult = \json_decode((string) $createLeadResponse->getBody(), true);

        $this->logger->info('Result from POST to '.$uri);
        $this->logger->info(\json_encode($createLeadResult, JSON_PRETTY_PRINT));

        if (200 === $createLeadResult['status'] && 1 === $createLeadResult['flag']) {
            $leadId = $createLeadResult['data']['_leadId'];
            $lead->setBridgeId($createLeadResult['data']['_id']);
            $lead->setLeadNumber($leadId);
        } else {
            throw new BadRequestHttpException(ErrorResolver::getErrorMessage($createLeadResult));
        }

        return $leadId;
    }

    /**
     * Updates a lead in the old version.
     *
     * @param Lead       $lead
     * @param BridgeUser $creator
     *
     * @return string
     */
    public function updateLead(Lead $lead, BridgeUser $creator)
    {
        $modifier = new AppendSegment('lead/'.$lead->getBridgeId());
        $uri = $modifier->process($this->baseUri);

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
            'x-access-token' => $creator->getAuthToken(),
        ];

        // map lead data to old version
        $leadData = $this->getLeadData($lead);

        $this->logger->info('Sending PUT to '.$uri);
        $this->logger->info(\json_encode($leadData, JSON_PRETTY_PRINT));

        $updateLeadRequest = new GuzzlePsr7Request('PUT', $uri, $headers, \json_encode($leadData));
        $updateLeadResponse = $this->client->send($updateLeadRequest);
        $updateLeadResult = \json_decode((string) $updateLeadResponse->getBody(), true);

        $this->logger->info('Result from PUT to '.$uri);
        $this->logger->info(\json_encode($updateLeadResult, JSON_PRETTY_PRINT));

        if (!(200 === $updateLeadResult['status'] && 1 === $updateLeadResult['flag'])) {
            throw new BadRequestHttpException(ErrorResolver::getErrorMessage($updateLeadResult));
        }
    }

    /**
     * Map lead data for old version.
     *
     * @param Lead $lead
     *
     * @return array
     */
    private function getLeadData(Lead $lead)
    {
        $now = new \DateTime();

        $leadData = [
            'category' => $lead->getType()->getValue(),
            'contract_type' => null !== $lead->getContractType() ? $lead->getContractType()->getValue() : null,
            'rate' => null !== $lead->getScore() ? $lead->getScore()->getValue() : null,
            'purchase_time_frame' => $lead->getPurchaseTimeFrame()->getValue(),
            'status' => $lead->getStatus()->getValue(),
            'meter_type' => null !== $lead->getMeterType() ? $lead->getMeterType()->getValue() : null,
            'is_existing_customer' => $lead->isExistingCustomer(),
        ];

        if (null !== $lead->getSource()) {
            $leadSource = $this->bridgeDataMapper->mapLeadSource($lead->getSource());

            if (null !== $leadSource) {
                $leadData['source'] = $leadSource;
            }
        }

        $leadData['consumption_amount'] = $lead->getAverageConsumption()->getValue();

        if (null !== $lead->getAverageConsumption()->getUnitCode()) {
            $consumptionUnit = $this->bridgeDataMapper->mapConsumptionUnit($lead->getAverageConsumption()->getUnitCode());

            if (null !== $consumptionUnit) {
                $leadData['average_consumption'] = $consumptionUnit;
            }
        }

        $contactData = [
            'do_not_contact' => $lead->isDoNotContact(),
        ];

        if (null !== $lead->getPreferredContactMethod()) {
            $contactData['prefer_contact_method'] = $lead->getPreferredContactMethod()->getValue();
        }

        $contactPersonData = [];
        $corporationDetails = $lead->getCorporationDetails();
        $personDetails = $lead->getPersonDetails();

        if (\count($lead->getAddresses()) > 0) {
            $contactData['address'] = $this->bridgeDataMapper->mapLeadAddress($lead->getAddresses()[0]);
        }

        if (null !== $personDetails) {
            $contactPersonData['first_name'] = $personDetails->getGivenName();
            $contactPersonData['middle_name'] = $personDetails->getAdditionalName();
            $contactPersonData['last_name'] = $personDetails->getFamilyName();
            $contactPersonData['full_name'] = $personDetails->getName();
            $contactPersonData['identity'] = [];

            $honorificPrefix = $this->bridgeDataMapper->mapHonorificPrefix($personDetails->getHonorificPrefix());

            if (null !== $honorificPrefix) {
                $contactPersonData['salutation'] = $honorificPrefix;
            }

            foreach ($personDetails->getIdentifiers() as $identifier) {
                if (IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD === $identifier->getName()->getValue()
                    && (null === $identifier->getValidThrough()
                        || $now < $identifier->getValidThrough())
                ) {
                    $contactPersonData['identity']['nric_fin'] = $identifier->getValue();
                    break;
                }
            }

            $contactPoints = $this->bridgeDataMapper->mapContactPoints($personDetails->getContactPoints());
            $contactData = \array_merge($contactData, $contactPoints);

            $socialMediaUrls = $this->bridgeDataMapper->denormalizeSocialMediaUrls($personDetails->getSameAsUrls());

            if (\count($socialMediaUrls) > 0) {
                $contactData['social_media_account'] = $socialMediaUrls;
            }
        }

        if (null !== $lead->getContractSubtype()) {
            if ('RESIDENTIAL' === $leadData['contract_type']) {
                $leadData['dwelling_type'] = $this->bridgeDataMapper->mapContractSubType($lead->getContractSubtype());
            } else {
                $contactPersonData['industry'] = $this->bridgeDataMapper->mapContractSubType($lead->getContractSubtype());
            }
        }

        if (AccountType::CORPORATE === $lead->getType()->getValue() && null !== $corporationDetails) {
            $contactPersonData['company_name'] = $corporationDetails->getName();

            if (null !== $corporationDetails->getUrl()) {
                $contactData['website'] = $corporationDetails->getUrl();
            }

            foreach ($corporationDetails->getIdentifiers() as $identifier) {
                if (IdentificationName::UNIQUE_ENTITY_NUMBER === $identifier->getName()->getValue()
                    && (null === $identifier->getValidThrough()
                        || $now < $identifier->getValidThrough())
                ) {
                    $contactPersonData['identity']['uen'] = $identifier->getValue();
                    break;
                }
            }
        }

        $leadData['contact_person'] = $contactPersonData;
        $leadData['contact_person']['contact'] = $contactData;

        if (\count($lead->getNotes()) > 0) {
            $leadData['note'] = [];
            foreach ($lead->getNotes() as $note) {
                $noteData = [
                    'desc' => $note->getText(),
                    'note_type' => $this->bridgeDataMapper->mapLeadNoteType($note->getType()->getValue()),
                ];

                if (\count($note->getFiles()) > 0) {
                    $noteData['note_attached'] = $this->bridgeDataMapper->mapAttachments($note->getFiles(), 'attached');
                }

                $leadData['note'][] = $noteData;
            }
        }

        return $leadData;
    }
}
