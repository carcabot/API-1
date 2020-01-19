<?php

declare(strict_types=1);

namespace App\WebService\Email\Provider\Mailgun\Controller;

use App\Entity\Campaign;
use App\Entity\EmailCampaignSourceListItem;
use App\Entity\MailgunOpenEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OpenEventController
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
            $this->logger->info('Mailgun Opened event Received');

            $req = \json_decode($request->getBody()->getContents(), true);
            $this->logger->info(\json_encode($req, JSON_PRETTY_PRINT));

            if (!empty($req['event-data'])) {
                $req = $req['event-data'];
            } else {
                return new JsonResponse('Invalid.', Response::HTTP_BAD_REQUEST);
            }

            $qb = $this->entityManager->createQueryBuilder();

            $temp = $qb->select('mailgunEvent')
                ->from(MailgunOpenEvent::class, 'mailgunEvent')
                ->join('mailgunEvent.campaign', 'campaign')
                ->join('mailgunEvent.recipient', 'recipient')
                ->where($qb->expr()->eq('recipient.id', ':recipientId'))
                ->andWhere($qb->expr()->eq('campaign.campaignNumber', ':campaignNumber'))
                ->setParameters([
                    'campaignNumber' => $req['user-variables']['campaignNumber'],
                    'recipientId' => $req['user-variables']['emailCampaignSourceListItemId'],
                ])
                ->getQuery()
                ->getResult();

            if (!empty($temp[0])) {
                $temp[0]->addDateOpened((int) $req['timestamp']);

                $this->entityManager->persist($temp[0]);
                $this->entityManager->flush();

                $this->logger->info(\sprintf('Mailgun Opened event for recipient %s saved', $temp[0]->getRecipient()->getEmailAddress()));
            } else {
                $mailgunOpenEvent = new MailgunOpenEvent();

                $recipient = $this->entityManager->getRepository(EmailCampaignSourceListItem::class)->findOneBy(['id' => $req['user-variables']['emailCampaignSourceListItemId']]);

                if (null !== $recipient) {
                    $mailgunOpenEvent->setRecipient($recipient);
                }

                $campaign = $this->entityManager->getRepository(Campaign::class)->findOneBy(['campaignNumber' => $req['user-variables']['campaignNumber']]);

                if (null !== $campaign) {
                    $mailgunOpenEvent->setCampaign($campaign);
                }

                if (!empty($req['id'])) {
                    $mailgunOpenEvent->setMailgunEventId($req['id']);
                }

                if (!empty($req['timestamp'])) {
                    $mailgunOpenEvent->addDateOpened((int) $req['timestamp']);
                }

                $this->entityManager->persist($mailgunOpenEvent);
                $this->entityManager->flush();

                $this->logger->info(\sprintf('Mailgun Opened event for recipient %s saved', $mailgunOpenEvent->getRecipient()->getEmailAddress()));
            }
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
        }

        return $response;
    }
}
