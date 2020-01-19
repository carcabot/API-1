<?php

declare(strict_types=1);

namespace App\Bridge\Security\Guard;

use App\Bridge\Exception\BridgeApiTokenAuthenticationException;
use App\Bridge\Security\Guard\Token\BridgeApiToken;
use App\Bridge\Services\UserApi;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AuthenticatorInterface;

class BridgeApiTokenAuthenticator implements AuthenticatorInterface
{
    /**
     * @var string
     */
    private $bridgeApiUrl;

    /**
     * @var UserApi
     */
    private $bridgeUserApi;

    /**
     * @var AuthenticationSuccessHandler
     */
    private $lexikJWTAuthenticationSuccessHandler;

    /**
     * @var AuthenticationFailureHandler
     */
    private $lexikJWTAuthenticationFailureHandler;

    /**
     * @param string                       $bridgeApiUrl
     * @param UserApi                      $bridgeUserApi
     * @param AuthenticationSuccessHandler $lexikJWTAuthenticationSuccessHandler
     * @param AuthenticationFailureHandler $lexikJWTAuthenticationFailureHandler
     */
    public function __construct(string $bridgeApiUrl, UserApi $bridgeUserApi, AuthenticationSuccessHandler $lexikJWTAuthenticationSuccessHandler, AuthenticationFailureHandler $lexikJWTAuthenticationFailureHandler)
    {
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->bridgeUserApi = $bridgeUserApi;
        $this->lexikJWTAuthenticationSuccessHandler = $lexikJWTAuthenticationSuccessHandler;
        $this->lexikJWTAuthenticationFailureHandler = $lexikJWTAuthenticationFailureHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        $requestContent = \json_decode($request->getContent(), true);

        if (true === isset($requestContent['auth_token'])) {
            $authToken = $requestContent['auth_token'];

            $userId = $this->bridgeUserApi->verifyAuthToken($authToken);

            if (null === $userId) {
                throw new BridgeApiTokenAuthenticationException();
            }

            return [
                'auth_token' => $authToken,
                'user_id' => $userId,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $user = null;

        if (true === isset($credentials['user_id']) && null !== $credentials['user_id']) {
            try {
                // if loadUserByUsername returns null, exception will be thrown
                $user = $userProvider->loadUserByUsername($credentials['user_id']);
            } catch (UsernameNotFoundException $e) {
                // user not found, needs to be created
                $this->bridgeUserApi->createBridgeUser($credentials, true);
            }

            if (null !== $user) {
                $this->bridgeUserApi->updateBridgeUserToken($credentials);
            }
        }

        if (null === $user) {
            $user = $userProvider->loadUserByUsername($credentials['user_id']);
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createAuthenticatedToken(UserInterface $user, $providerKey)
    {
        return new BridgeApiToken(
            $user,
            $providerKey,
            $user->getRoles()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return $this->lexikJWTAuthenticationSuccessHandler->onAuthenticationSuccess($request, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($exception instanceof BridgeApiTokenAuthenticationException) {
            $data = [
                'message' => \strtr($exception->getMessageKey(), $exception->getMessageData()),
            ];

            return new JsonResponse($data, 403);
        }

        return $this->lexikJWTAuthenticationFailureHandler->onAuthenticationFailure($request, $exception);
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            // you might translate this message
            'message' => 'Authentication Required',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        $requestContent = \json_decode($request->getContent(), true);

        return isset($requestContent['auth_token']);
    }
}
