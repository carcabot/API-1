<?php

declare(strict_types=1);

namespace App\WebService\SMS\Provider\FortDigital;

use App\Enum\SMSDirection;
use App\Enum\SMSWebServicePartner;
use App\WebService\SMS\ClientInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;

class Client implements ClientInterface
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $originNumber;

    /**
     * @var string|null
     */
    private $testNumber;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

    public function __construct(string $url, string $username, string $password, string $originNumber, ?string $testNumber, LoggerInterface $logger)
    {
        $this->username = $username;
        $this->password = $password;
        $this->originNumber = $originNumber;
        $this->testNumber = $testNumber;
        $this->logger = $logger;
        $this->baseUri = HttpUri::createFromString($url);
        $this->client = new GuzzleClient();
    }

    public function send(string $recipient, string $message, ?string $sender = null)
    {
        $senderMobileNumber = null;

        if (empty($sender)) {
            $sender = $this->originNumber;
        }

        if (!empty($this->testNumber)) {
            $recipient = $this->testNumber;
        }

        $pathModifier = new AppendSegment('http/send-message');
        $uri = $pathModifier->process($this->baseUri);
        $queryString = (\GuzzleHttp\Psr7\build_query([
            'username' => $this->username,
            'password' => $this->password,
            'from' => $sender,
            'to' => $recipient,
            'message' => $message,
        ]));

        $url = \sprintf('%s?%s', $uri, $queryString);
        $this->logger->info('Sending GET to '.$url);

        $getRequest = new GuzzlePsr7Request('GET', $url);
        $getResponse = $this->client->send($getRequest);
        $result = $getResponse->getBody();

        $this->logger->info('Result from GET to '.$url);
        $this->logger->info($result);

        if (!empty($sender) && \is_numeric($sender)) {
            if (0 !== \strpos('+', $sender)) {
                $senderMobileNumber = '+'.$sender;
            }
        }

        if (0 !== \strpos('+', $recipient)) {
            $recipient = '+'.$recipient;
        }

        if (null === $senderMobileNumber) {
            $senderMobileNumber = '+'.$this->originNumber;
        }

        return [
            'dateSent' => (new \DateTime())->format('c'),
            'direction' => SMSDirection::OUTBOUND,
            'message' => $message,
            'provider' => SMSWebServicePartner::FORT_DIGITAL,
            'rawMessage' => $message,
            'recipientMobileNumber' => $recipient,
            'sender' => $sender,
            'senderMobileNumber' => $senderMobileNumber,
        ];
    }
}
