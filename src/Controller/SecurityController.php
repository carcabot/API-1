<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Domain\Command\User\UpdatePassword;
use App\Entity\PasswordToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class SecurityController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param CommandBus                     $commandBus
     * @param AccessDecisionManagerInterface $decisionManager
     * @param EntityManagerInterface         $entityManager
     * @param IriConverterInterface          $iriConverter
     * @param UserPasswordEncoderInterface   $passwordEncoder
     * @param TokenStorageInterface          $tokenStorage
     */
    public function __construct(CommandBus $commandBus, AccessDecisionManagerInterface $decisionManager, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, UserPasswordEncoderInterface $passwordEncoder, TokenStorageInterface $tokenStorage)
    {
        $this->commandBus = $commandBus;
        $this->decisionManager = $decisionManager;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->passwordEncoder = $passwordEncoder;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Route("/authentication_token", methods={"POST"})
     *
     * @return HttpResponseInterface
     */
    public function authenticationTokenAction(): HttpResponseInterface
    {
        // The security layer will intercept this request
        return new EmptyResponse(401);
    }

    /**
     * @Route("/verify_token", methods={"POST"})
     *
     * @return HttpResponseInterface
     */
    public function verifyTokenAction(): HttpResponseInterface
    {
        // The security layer will intercept this request
        return new EmptyResponse(401);
    }

    /**
     * @Route("/change_password", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function changePasswordAction(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);

        try {
            $token = $this->tokenStorage->getToken();

            if (null === $token) {
                throw new BadRequestHttpException('No token found!');
            }

            $authenticatedUser = $token->getUser();
            if (isset($params['userIri'])) {
                $user = $this->iriConverter->getItemFromIri($params['userIri']);

                if ($params['userIri'] !== $this->iriConverter->getIriFromItem($user) && !$this->decisionManager->decide($token, ['ROLE_SUPER_ADMIN'])) {
                    throw new BadRequestHttpException('You can only change your own password!');
                }
            } else {
                $user = $authenticatedUser;
            }

            if ($user instanceof User && true === $this->passwordEncoder->isPasswordValid($user, $params['oldPassword'])) {
                $this->commandBus->handle(new UpdatePassword($user, $params['newPassword']));

                if (null === $user->getDateActivated()) {
                    $user->setDateActivated(new \DateTime());
                }
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return new JsonResponse(['message' => 'Password changed successfully.'], 200);
            }
            throw new BadRequestHttpException('The old password does not match!');
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return new EmptyResponse(400);
    }

    /**
     * @Route("/verify_password", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function verifyPasswordAction(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);

        try {
            $token = $this->tokenStorage->getToken();

            if (null === $token) {
                throw new BadRequestHttpException('No token found!');
            }

            $authenticatedUser = $token->getUser();

            if ($authenticatedUser instanceof User && true === $this->passwordEncoder->isPasswordValid($authenticatedUser, $params['password'])) {
                return new JsonResponse(['message' => 'Password is valid.'], 200);
            }

            throw new BadRequestHttpException('Invalid password!');
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return new EmptyResponse(400);
    }

    /**
     * @Route("/activate_user", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function activateUserAction(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);

        if (empty($params['token'])) {
            throw new BadRequestHttpException('No token.');
        }

        $passwordToken = $this->entityManager->getRepository(PasswordToken::class)->findOneBy(['token' => $params['token']]);

        if (null === $passwordToken) {
            throw new BadRequestHttpException('Invalid token.');
        }

        if (true === $passwordToken->isExpired()) {
            throw new BadRequestHttpException('Token has expired.');
        }

        $passwordToken->setExpiresAt(new \DateTime());
        $user = $passwordToken->getUser();

        $user->setDateActivated(new \DateTime());
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'User activated.']);
    }
}
