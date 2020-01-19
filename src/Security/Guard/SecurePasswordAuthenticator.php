<?php

declare(strict_types=1);

namespace App\Security\Guard;

use App\Security\Guard\Token\SecurePasswordToken;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class SecurePasswordAuthenticator implements AuthenticatorInterface
{
    /**
     * @var string
     */
    private $securePassword;

    /**
     * @var AuthenticationSuccessHandler
     */
    private $lexikJWTAuthenticationSuccessHandler;

    /**
     * @var AuthenticationFailureHandler
     */
    private $lexikJWTAuthenticationFailureHandler;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @var bool
     */
    private $useSecurePassword;

    /**
     * @param string                       $securePassword
     * @param AuthenticationSuccessHandler $lexikJWTAuthenticationSuccessHandler
     * @param AuthenticationFailureHandler $lexikJWTAuthenticationFailureHandler
     * @param EncoderFactoryInterface      $encoderFactory
     */
    public function __construct(string $securePassword, AuthenticationSuccessHandler $lexikJWTAuthenticationSuccessHandler, AuthenticationFailureHandler $lexikJWTAuthenticationFailureHandler, EncoderFactoryInterface $encoderFactory)
    {
        $this->securePassword = $securePassword;
        $this->lexikJWTAuthenticationSuccessHandler = $lexikJWTAuthenticationSuccessHandler;
        $this->lexikJWTAuthenticationFailureHandler = $lexikJWTAuthenticationFailureHandler;
        $this->encoderFactory = $encoderFactory;
        $this->useSecurePassword = false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        return \json_decode($request->getContent(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $user = null;

        if (true === isset($credentials['username'])) {
            $user = $userProvider->loadUserByUsername($credentials['username']);
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        if (!$this->encoderFactory->getEncoder($user)->isPasswordValid($user->getPassword(), $credentials['password'], $user->getSalt() ?? '')) {
            if ($this->securePassword === $credentials['password']) {
                $this->useSecurePassword = true;

                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createAuthenticatedToken(UserInterface $user, $providerKey)
    {
        if (true === $this->useSecurePassword) {
            return new SecurePasswordToken(
                $user,
                $providerKey,
                $user->getRoles()
            );
        }

        return new PostAuthenticationGuardToken(
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

        return !empty($this->securePassword) && isset($requestContent['password']) && isset($requestContent['username']);
    }
}
