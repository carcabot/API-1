<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\MailgunBounceEvent;
use App\Entity\MailgunClickEvent;
use App\Entity\MailgunComplainEvent;
use App\Entity\MailgunDeliverEvent;
use App\Entity\MailgunOpenEvent;
use App\Entity\Report;
use App\Enum\DocumentType;
use App\Model\ReportGenerator;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CampaignReportController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ReportGenerator
     */
    private $reportGenerator;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var DateTimeZone
     */
    private $timezone;

    /**
     * @var array|MailgunBounceEvent[]
     */
    private $bouncedEvents;

    /**
     * @var array|MailgunClickEvent[]
     */
    private $clickedEvents;

    /**
     * @var array|MailgunDeliverEvent[]
     */
    private $deliveredEvents;

    /**
     * @var array|MailgunOpenEvent[]
     */
    private $openedEvents;

    /**
     * @var array|MailgunComplainEvent[]
     */
    private $complainEvents;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ReportGenerator        $reportGenerator
     * @param NormalizerInterface    $normalizer
     * @param string                 $timezone
     */
    public function __construct(EntityManagerInterface $entityManager, ReportGenerator $reportGenerator, NormalizerInterface $normalizer, $timezone)
    {
        $this->entityManager = $entityManager;
        $this->reportGenerator = $reportGenerator;
        $this->normalizer = $normalizer;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * @param ServerRequestInterface $request
     */
    public function __invoke(ServerRequestInterface $request)
    {
        try {
            $params = \json_decode($request->getBody()->getContents(), true);
            $normalizedReports = null;
            $reports = [];
            $totalRecipients = 0;
            $fileName = 'Campaign';
            if (!empty($params['campaignNumber'])) {
                $fileName = $params['campaignNumber'];
            } else {
                throw new \Exception('Please, supply campaign ID');
            }
            $reportData = [
                'data' => [],
                'filename' => \sprintf('%s_%s.xlsx', $fileName, 'Summary Report'),
                'customReport' => true,
            ];

            $summaryData = [
                'data' => [],
                'column-format' => [],
                'headers' => [],
            ];

            $campaignDetails = $this->entityManager->getRepository(Campaign::class)->findOneBy([
                'campaignNumber' => $params['campaignNumber'],
            ]);

            if (null !== $campaignDetails) {
                $qb = $this->entityManager->getRepository(Campaign::class)->createQueryBuilder('campaign');
                $expr = $qb->expr();

                $recipientsEmails = $qb->select('emails.value')
                    ->leftJoin('campaign.recipientLists', 'recipients')
                    ->leftJoin('recipients.itemListElement', 'emails')
                    ->where($expr->eq('campaign.campaignNumber', ':campaignNumber'))
                    ->setParameter('campaignNumber', $campaignDetails->getCampaignNumber())
                    ->getQuery()
                    ->getResult();

                $totalRecipients = \count($recipientsEmails);
                $summaryData['data'] = [
                    ['Summary', 'Campaign Name', $campaignDetails->getName()],
                    ['', 'Total Recipient', $totalRecipients],
                    ['', 'Executed Date', $campaignDetails->getStartDate()],
                ];

                $this->bouncedEvents = $this->entityManager->getRepository(MailgunBounceEvent::class)->findBy([
                    'campaign' => $campaignDetails->getId(),
                ]);
                $this->clickedEvents = $this->entityManager->getRepository(MailgunClickEvent::class)->findBy([
                    'campaign' => $campaignDetails->getId(),
                ]);
                $this->deliveredEvents = $this->entityManager->getRepository(MailgunDeliverEvent::class)->findBy([
                    'campaign' => $campaignDetails->getId(),
                ]);
                $this->openedEvents = $this->entityManager->getRepository(MailgunOpenEvent::class)->findBy([
                    'campaign' => $campaignDetails->getId(),
                ]);
                $this->complainEvents = $this->entityManager->getRepository(MailgunComplainEvent::class)->findBy([
                    'campaign' => $campaignDetails->getId(),
                ]);

                $eventsData = [
                    'data' => [
                        [
                            '' => 'Bounced',
                            'Total Number' => \count($this->bouncedEvents),
                            'Percentage' => $this->calculateEventPercentage(\count($this->bouncedEvents), $totalRecipients),
                        ],
                        [
                            '' => 'Clicked',
                            'Total Number' => \count($this->clickedEvents),
                            'Percentage' => $this->calculateEventPercentage(\count($this->clickedEvents), $totalRecipients),
                        ],
                        [
                            '' => 'Delivered',
                            'Total Number' => \count($this->deliveredEvents),
                            'Percentage' => $this->calculateEventPercentage(\count($this->deliveredEvents), $totalRecipients),
                        ],
                        [
                            '' => 'Opened',
                            'Total Number' => \count($this->openedEvents),
                            'Percentage' => $this->calculateEventPercentage(\count($this->openedEvents), $totalRecipients),
                        ],
                        [
                            '' => 'Complained',
                            'Total Number' => \count($this->complainEvents),
                            'Percentage' => $this->calculateEventPercentage(\count($this->complainEvents), $totalRecipients),
                        ],
                    ],

                    'column-format' => [],
                    'headers' => ['', 'Total Number', 'Percentage'],
                ];

                $recipientsData = [
                    'data' => [],
                    'column-format' => [],
                    'headers' => [
                        'Recipient',
                        'Event Date',
                        'Status',
                        'Opened',
                        'Links Clicked',
                        'Complained',
                    ],
                    'newSheet' => true,
                    'sheetName' => 'Recipients Data',
                ];

                foreach ($recipientsEmails as $recipient) {
                    $event = $this->getRecipientEventStatus($recipient['value']);

                    $recipientsData['data'][] = [
                        'Recipient' => $recipient['value'],
                        'Event Date' => $this->getEventDate($recipient['value'], $event),
                        'Opened' => 'Opened' === $event ? 'Y' : 'N',
                        'Clicked' => 'Clicked' === $event ? 'Y' : 'N',
                        'Complained' => 'Complained' === $event ? 'Y' : 'N',
                        'Delivered' => 'Delivered' === $event ? 'Y' : 'N',
                        'Bounced' => 'Bounced' === $event ? 'Y' : 'N',
                    ];
                }

                $reportData['data'] = ['summary' => $summaryData, 'events' => $eventsData, 'recipientsInfo' => $recipientsData];

                if (!empty($reportData)) {
                    $reports = $this->reportGenerator->convertDataToInternalDocument([$reportData], DocumentType::CAMPAIGN_REPORT);
                }

                return new Report($reports);
            }
            throw new \Exception('Campaign not found');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function calculateEventPercentage(int $eventCount, int $totalRecipients)
    {
        return (string) \round(($eventCount / $totalRecipients) * 100, 2).'%';
    }

    private function getRecipientEventStatus(string $email)
    {
        $events = \array_filter($this->complainEvents, function ($e) use ($email) {
            return $email === $e->getRecipient()->getEmailAddress();
        });
        if (\count($events) > 0) {
            return 'Complained';
        }

        $events = \array_filter($this->clickedEvents, function ($e) use ($email) {
            return $email === $e->getRecipient()->getEmailAddress();
        });
        if (\count($events) > 0) {
            return 'Clicked';
        }

        $events = \array_filter($this->openedEvents, function ($e) use ($email) {
            return $email === $e->getRecipient()->getEmailAddress();
        });
        if (\count($events) > 0) {
            return 'Opened';
        }

        $events = \array_filter($this->deliveredEvents, function ($e) use ($email) {
            return $email === $e->getRecipient()->getEmailAddress();
        });
        if (\count($events) > 0) {
            return 'Delivered';
        }

        $events = \array_filter($this->bouncedEvents, function ($e) use ($email) {
            return $email === $e->getRecipient()->getEmailAddress();
        });
        if (\count($events) > 0) {
            return 'Bounced';
        }

        return '';
    }

    private function getEventDate(string $email, string $eventType)
    {
        switch ($eventType) {
            case 'Opened':
            case 'Clicked':
            case 'Complained':
                $userEvents = \array_filter($this->openedEvents, function ($e) use ($email) {
                    return $email === $e->getRecipient()->getEmailAddress();
                });
                if (\count($userEvents) > 0) {
                    return (new \DateTime(\array_values($userEvents)[0]->getDatesOpened()[0]['date']))->setTimezone($this->timezone)->format('d/m/Y');
                }

                return null;
            case 'Delivered':
                $userEvents = \array_filter($this->deliveredEvents, function ($e) use ($email) {
                    return $email === $e->getRecipient()->getEmailAddress();
                });
                if (\count($userEvents) > 0) {
                    return \array_values($userEvents)[0]->getDateDelivered()->setTimezone($this->timezone)->format('d/m/Y');
                }

                return null;
            case 'Bounced':
                $userEvents = \array_filter($this->bouncedEvents, function ($e) use ($email) {
                    return $email === $e->getRecipient()->getEmailAddress();
                });
                if (\count($userEvents) > 0) {
                    return \array_values($userEvents)[0]->getDateBounced()->setTimezone($this->timezone)->format('d/m/Y');
                }

                return '';
            default:
                return '';
        }
    }
}
