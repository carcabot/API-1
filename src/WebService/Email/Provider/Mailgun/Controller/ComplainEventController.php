<?php

declare(strict_types=1);

namespace App\WebService\Email\Provider\Mailgun\Controller;

use App\Entity\Campaign;
use App\Entity\EmailCampaignSourceListItem;
use App\Entity\MailgunComplainEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ComplainEventController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $authToken;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param string                 $authToken
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, string $authToken)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->authToken = $authToken;
    }

    public function __invoke(ServerRequestInterface $request): Response
    {
        $params = $request->getQueryParams() ?? [];

        if (empty($params['auth_token']) || $this->authToken !== $params['auth_token']) {
            return new JsonResponse('Token pls.', Response::HTTP_UNAUTHORIZED);
        }

        $response = new Response();
        $response->setStatusCode(200);
        $response->setContent('OK');

        try {
            $this->logger->info('Mailgun Complained event Received');

            $req = \json_decode($request->getBody()->getContents(), true);
            $this->logger->info(\json_encode($req, JSON_PRETTY_PRINT));

            if (!empty($req['event-data'])) {
                $req = $req['event-data'];
            } else {
                return new JsonResponse('Invalid.', Response::HTTP_BAD_REQUEST);
            }

            $mailgunComplainEvent = new MailgunComplainEvent();

            $recipient = $this->entityManager->getRepository(EmailCampaignSourceListItem::class)->findOneBy(['id' => $req['user-variables']['emailCampaignSourceListItemId']]);
            if (null !== $recipient) {
                $mailgunComplainEvent->setRecipient($recipient);
            }

            $campaign = $this->entityManager->getRepository(Campaign::class)->findOneBy(['campaignNumber' => $req['user-variables']['campaignNumber']]);
            if (null !== $campaign) {
                $mailgunComplainEvent->setCampaign($campaign);
            }

            if (!empty($req['id'])) {
                $mailgunComplainEvent->setMailgunEventId($req['id']);
            }

            if (!empty($req['timestamp'])) {
                $mailgunComplainEvent->setDateComplained((int) $req['timestamp']);
            }

            $this->entityManager->persist($mailgunComplainEvent);
            $this->entityManager->flush();

            $this->logger->info(\sprintf('Mailgun Complained event for recipient %s saved', $mailgunComplainEvent->getRecipient()->getEmailAddress()));
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
        }

        return $response;
    }
}
