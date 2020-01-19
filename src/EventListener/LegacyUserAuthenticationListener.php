<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\User\UpdatePassword;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Tactician\CommandBus;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LegacyUserAuthenticationListener implements EventSubscriberInterface
{
    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var string
     */
    private $bridgeAuthApiUrl;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string                 $bridgeAuthApiUrl
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(string $bridgeAuthApiUrl, CommandBus $commandBus, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->bridgeAuthApiUrl = $bridgeAuthApiUrl;
        $this->client = new GuzzleClient();
        $this->baseUri = HttpUri::createFromString($this->bridgeAuthApiUrl);
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 32],
            ],
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ('/authentication_token' === $request->getPathInfo()) {
            $params = \json_decode($request->getContent(), true);

            if (isset($params['username']) && isset($params['password'])) {
                $userRepository = $this->entityManager->getRepository(User::class);

                if ($userRepository instanceof UserLoaderInterface) {
                    $user = $userRepository->loadUserByUsername($params['username']);
                    if (null !== $user) {
                        if (empty($user->getPassword()) && !empty($this->bridgeAuthApiUrl)) {
                            $modifier = new AppendSegment('user/login');
                            $uri = $modifier->process($this->baseUri);
                            $headers = [
                                'User-Agent' => 'U-Centric API',
                                'Content-Type' => 'application/json',
                            ];

                            $loginData = [
                                'email' => $params['username'],
                                'password' => $params['password'],
                            ];
                            try {
                                $this->logger->info('Sending POST to '.$uri);
                                $this->logger->info(\json_encode($params, JSON_PRETTY_PRINT));

                                $createAuthenticationRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($loginData));
                                $createAuthenticationResponse = $this->client->send($createAuthenticationRequest);
                                $createAuthenticationResult = \json_decode((string) $createAuthenticationResponse->getBody(), true);

                                $this->logger->info('Result from POST to '.$uri);
                                $this->logger->info(\json_encode($createAuthenticationResult, JSON_PRETTY_PRINT));

                                if (200 === $createAuthenticationResult['status']) {
                                    if ($user instanceof User) {
                                        $this->commandBus->handle(new UpdatePassword($user, $params['password']));
                                    }
                                } else {
                                    $modifier = new AppendSegment('user/customer/login');
                                    $uri = $modifier->process($this->baseUri);
                                    $headers = [
                                        'User-Agent' => 'U-Centric API',
                                        'Content-Type' => 'application/json',
                                    ];

                                    $loginData = [
                                        'email' => $params['username'],
                                        'password' => \hash('sha256', $params['password']),
                                    ];
                                    try {
                                        $this->logger->info('Sending POST to '.$uri);
                                        $this->logger->info(\json_encode($params, JSON_PRETTY_PRINT));

                                        $createAuthenticationRequest = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($loginData));
                                        $createAuthenticationResponse = $this->client->send($createAuthenticationRequest);
                                        $createAuthenticationResult = \json_decode((string) $createAuthenticationResponse->getBody(), true);

                                        $this->logger->info('Result from POST to '.$uri);
                                        $this->logger->info(\json_encode($createAuthenticationResult, JSON_PRETTY_PRINT));

                                        if (200 === $createAuthenticationResult['status']) {
                                            if ($user instanceof User) {
                                                $this->commandBus->handle(new UpdatePassword($user, $params['password']));
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        if ($e instanceof ClientException) {
                                            throw $e;
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                $this->logger->info($e->getMessage());

                                if ($e instanceof ClientException) {
                                    throw $e;
                                }
                            }
                        } else {
                            if ($user instanceof User && true === \password_needs_rehash($user->getPassword(), PASSWORD_ARGON2I)) {
                                $this->commandBus->handle(new UpdatePassword($user, $params['password']));
                            }
                        }
                    }
                }
            }
        }
    }
}
