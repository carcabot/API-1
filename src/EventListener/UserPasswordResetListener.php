<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class UserPasswordResetListener implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 64],
            ],
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (false !== \stripos($request->getPathInfo(), 'reset_password')) {
            $params = \json_decode($request->getContent(), true);

            if (isset($params['email'])) {
                $userRepository = $this->entityManager->getRepository(User::class);

                if ($userRepository instanceof UserLoaderInterface) {
                    $user = $userRepository->loadUserByUsername($params['email']);
                    if (null === $user) {
                        throw new NotFoundHttpException('Your email address is not registered. Please register first.');
                    }
                }
            }
        }
    }
}
