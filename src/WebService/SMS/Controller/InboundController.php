<?php

declare(strict_types=1);

namespace App\WebService\SMS\Controller;

use App\Entity\ActivitySmsHistory;
use App\Entity\SmsHistory;
use App\Enum\SMSDirection;
use App\Enum\SMSWebServicePartner;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class InboundController
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
     * @var PhoneNumberUtil
     */
    private $phoneNumberUtil;

    /**
     * @var DenormalizerInterface
     */
    private $serializer;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param PhoneNumberUtil        $phoneNumberUtil
     * @param DenormalizerInterface  $serializer
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, PhoneNumberUtil $phoneNumberUtil, DenormalizerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->serializer = $serializer;
        $this->timezone = new \DateTimeZone('Asia/Singapore');
    }

    public function __invoke(ServerRequestInterface $request): Response
    {
        $params = $request->getQueryParams();
        $data = [];
        $error = '';

        // very necessary
        $sender = null;
        $recipient = null;
        $message = null;

        // not necessary
        $dateSent = null;
        $dateReceived = null;

        $string = \key($params);
        $data = \explode('|', $string);
        $this->logger->info('Inbound SMS Received: '.$string);

        try {
            if (!empty($data[0])) {
                $sender = $data[0];
            } else {
                $error .= "Sender is empty.\n";
            }

            if (!empty($data[1])) {
                $recipient = $data[1];
            } else {
                $error .= "Recipient is empty.\n";
            }

            if (!empty($data[2])) {
                $message = $data[2];
            } else {
                $error .= "Message is empty.\n";
            }

            if ('' !== $error) {
                $response = new Response();
                $response->setStatusCode(400);
                $response->setContent($error);

                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }

            // Because FortDigital has no standards. FFS
            $possibleDateFormats = [
                'j/m/Y H:i:s',
                'j-m-Y H:i:s',
                'd/m/Y H:i:s',
                'd-m-Y H:i:s',
            ];

            if (!empty($data[3]) && !empty($data[4])) {
                $timeSent = new \DateTime($data[4], $this->timezone);

                foreach ($possibleDateFormats as $dateFormat) {
                    $dateSent = \DateTime::createFromFormat($dateFormat, \sprintf('%s %s', $data[3], $timeSent->format('H:i:s')), $this->timezone);

                    if (false !== $dateSent) {
                        $dateSent->setTimezone(new \DateTimeZone('UTC'));
                        break;
                    }
                }
            }

            if (!empty($data[5]) && !empty($data[6])) {
                $timeReceived = new \DateTime($data[6], $this->timezone);

                foreach ($possibleDateFormats as $dateFormat) {
                    $dateReceived = \DateTime::createFromFormat($dateFormat, \sprintf('%s %s', $data[5], $timeReceived->format('H:i:s')), $this->timezone);

                    if (false !== $dateReceived) {
                        $dateReceived->setTimezone(new \DateTimeZone('UTC'));
                        break;
                    }
                }
            }

            if (0 !== \strpos('+', $sender)) {
                $sender = '+'.$sender;
            }

            if (0 !== \strpos('+', $recipient)) {
                $recipient = '+'.$recipient;
            }

            $decodedMessage = $message;
            $recipientMobileNumber = $this->serializer->denormalize($recipient, PhoneNumber::class);
            $senderMobileNumber = $this->serializer->denormalize($sender, PhoneNumber::class);

            if (!$recipientMobileNumber instanceof PhoneNumber || !$senderMobileNumber instanceof PhoneNumber) {
                throw new BadRequestHttpException('Invalid phone number');
            }

            $qb = $this->entityManager->getRepository(ActivitySmsHistory::class)->createQueryBuilder('a');
            $expr = $qb->expr();
            $outboundActivitySMSHistories = $qb->join('a.outboundSMS', 'sms')
                ->where($expr->eq('sms.direction', $expr->literal(SMSDirection::OUTBOUND)))
                ->andWhere($expr->eq('sms.provider', $expr->literal(SMSWebServicePartner::DUMMY)))
                ->andWhere($expr->eq('sms.recipientMobileNumber', $expr->literal($this->phoneNumberUtil->format($senderMobileNumber, PhoneNumberFormat::E164))))
                ->orderBy('a.dateCreated', 'DESC')
                ->getQuery()
                ->getResult();

            $latestActivitySMSHistory = null;

            foreach ($outboundActivitySMSHistories as $activitySMSHistory) {
                if (null === $activitySMSHistory->getInboundSMS()) {
                    $latestActivitySMSHistory = $activitySMSHistory;
                    break;
                }
            }

            // @todo unsub
            $smsHistory = new SmsHistory();
            $smsHistory->setDateReceived($dateReceived ?? null);
            $smsHistory->setDateSent($dateSent ?? null);
            $smsHistory->setDirection(new SMSDirection(SMSDirection::INBOUND));
            $smsHistory->setMessage($decodedMessage);
            $smsHistory->setProvider(new SMSWebServicePartner(SMSWebServicePartner::DUMMY));
            $smsHistory->setRawMessage($message);
            $smsHistory->setRecipientMobileNumber($recipientMobileNumber);
            $smsHistory->setSenderMobileNumber($senderMobileNumber);

            $this->entityManager->persist($smsHistory);

            if (null !== $latestActivitySMSHistory) {
                $latestActivitySMSHistory->setInboundSMS($smsHistory);
            }

            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $response = new Response();
        $response->setStatusCode(200);
        $response->setContent('OK. Received: '.$string);

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
