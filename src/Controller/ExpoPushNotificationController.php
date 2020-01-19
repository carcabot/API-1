<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\AuthenticationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Solvecrew\ExpoNotificationsBundle\Manager\NotificationManager;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class ExpoPushNotificationController
{
    /**
     * @var AuthenticationHelper
     */
    private $authenticationHelper;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var NotificationManager
     */
    private $notificationManager;

    /**
     * @param AuthenticationHelper   $authenticationHelper
     * @param EntityManagerInterface $entityManager
     * @param NotificationManager    $notificationManager
     */
    public function __construct(AuthenticationHelper $authenticationHelper, EntityManagerInterface $entityManager, NotificationManager $notificationManager)
    {
        $this->authenticationHelper = $authenticationHelper;
        $this->entityManager = $entityManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @Route("/expo_push_notification_token", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function setExpoPushNotificationTokens(ServerRequestInterface $request): HttpResponseInterface
    {
        $user = $this->authenticationHelper->getAuthenticatedUser();
        $params = \json_decode($request->getBody()->getContents(), true);

        if (null !== $user && $user instanceof User) {
            $tokens = $user->getExpoPushNotificationTokens();
            if (!\in_array($params['token'], $tokens, true)) {
                $user->addExpoPushNotificationToken($params['token']);
                $this->entityManager->flush();

                return new EmptyResponse(200);
            }
            throw new BadRequestHttpException();
        }
        throw new AccessDeniedException('/expo_push_notification_token');
    }

    /**
     * @Route("/expo_push_notification_token", methods={"DELETE"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function deleteExpoPushNotificationTokens(ServerRequestInterface $request): HttpResponseInterface
    {
        $user = $this->authenticationHelper->getAuthenticatedUser();
        $params = \json_decode($request->getBody()->getContents(), true);

        if (null !== $user && $user instanceof User) {
            $tokens = $user->getExpoPushNotificationTokens();
            if (!\in_array($params['token'], $tokens, true)) {
                $user->removeExpoPushNotificationToken($params['token']);
                $this->entityManager->flush();

                return new EmptyResponse(200);
            }
            throw new BadRequestHttpException();
        }
        throw new AccessDeniedException('/expo_push_notification_token');
    }

    /**
     * @Route("/push_expo_notification", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function pushExpoNotification(ServerRequestInterface $request): HttpResponseInterface
    {
        $user = $this->authenticationHelper->getAuthenticatedUser();
        $params = \json_decode($request->getBody()->getContents(), true);
        $hasPermission = false;

        if (null !== $user && $user instanceof User) {
            foreach ($user->getRoles() as $role) {
                if (\in_array($role, [
                    'ROLE_ADMIN',
                    'ROLE_API_USER',
                    'ROLE_SUPER_ADMIN',
                ], true)) {
                    $hasPermission = true;
                    break;
                }
            }

            $totalNotifications = 0;

            if (true === $hasPermission) {
                $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('user');
                $users = $qb->where($qb->expr()->gt(\sprintf(<<<'SQL'
                    jsonb_array_length(CAST(%s.%s AS jsonb))
SQL
            , 'user', 'expoPushNotificationTokens'), $qb->expr()->literal(0)))
                    ->getQuery()
                    ->getResult();

                $tokens = [];
                $title = null;
                $message = null;

                foreach ($users as $user) {
                    foreach ($user->getExpoPushNotificationTokens() as $token) {
                        if (1 === \preg_match('/^ExponentPushToken\[.*\]$/', $token)) {
                            $token = \preg_replace('/^ExponentPushToken\[/', '', $token);
                            $token = \rtrim($token, ']');
                            $tokens[] = $token;
                        }
                    }
                }

                $tokens = \array_unique($tokens);
                $tokens = \array_values($tokens);
                $totalNotifications = \count($tokens);

                if ($totalNotifications > 0) {
                    if (isset($params['title'])) {
                        $title = $params['title'];
                    }

                    if (isset($params['body'])) {
                        $message = $params['body'];
                    }

                    if (null !== $message) {
                        foreach ($tokens as $token) {
                            $this->notificationManager->sendNotification($message, $token, $title);
                        }
                    } else {
                        throw new BadRequestHttpException('No message to send out?');
                    }
                } else {
                    throw new BadRequestHttpException('No expo tokens found.');
                }
            }

            $response = [
                'message' => \sprintf('Notifications sent. Total of %d tokens found', $totalNotifications),
                'title' => $title ?? '',
                'body' => $message ?? '',
            ];

            return new JsonResponse($response);
        }
        throw new AccessDeniedException('/push_expo_notification');
    }
}
