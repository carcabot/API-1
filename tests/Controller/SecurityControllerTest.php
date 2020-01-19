<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 6/5/19
 * Time: 10:48 AM.
 */

namespace App\Tests\Controller;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Controller\SecurityController;
use App\Domain\Command\User\UpdatePassword;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class SecurityControllerTest extends TestCase
{
    public function testVerifyPasswordActionWithDefaultValues()
    {
        $data = [
            'password' => 'testPassword',
        ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $user = $userProphecy->reveal();

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getUser()->willReturn($user);
        $token = $tokenProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $passwordEncoderProphecy = $this->prophesize(UserPasswordEncoderInterface::class);
        $passwordEncoderProphecy->isPasswordValid($user, 'testPassword')->willReturn(true);
        $passwordEncoder = $passwordEncoderProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Password is valid.'], 200);

        $securityController = new SecurityController($commandBus, $decisionManager, $entityManager, $iriConverter, $passwordEncoder, $tokenStorage);
        $actualData = $securityController->verifyPasswordAction($request);

        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
    }

    public function testVerifyPasswordActionWithIncorrectPassword()
    {
        $data = [
            'password' => 'testPassword',
        ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $user = $userProphecy->reveal();

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getUser()->willReturn($user);
        $token = $tokenProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $passwordEncoderProphecy = $this->prophesize(UserPasswordEncoderInterface::class);
        $passwordEncoderProphecy->isPasswordValid($user, 'testPassword')->willReturn(false);
        $passwordEncoder = $passwordEncoderProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Invalid password!'], 400);

        $securityController = new SecurityController($commandBus, $decisionManager, $entityManager, $iriConverter, $passwordEncoder, $tokenStorage);
        $actualData = $securityController->verifyPasswordAction($request);

        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
    }

    public function testVerifyPasswordActionWithoutToken()
    {
        $data = [];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn(null);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $passwordEncoderProphecy = $this->prophesize(UserPasswordEncoderInterface::class);
        $passwordEncoder = $passwordEncoderProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'No token found!'], 400);

        $securityController = new SecurityController($commandBus, $decisionManager, $entityManager, $iriConverter, $passwordEncoder, $tokenStorage);
        $actualData = $securityController->verifyPasswordAction($request);

        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
    }

    public function testChangePasswordActionWithUserIri()
    {
        $data = [
            'userIri' => 'testIri',
            'oldPassword' => 'testOldPassword',
            'newPassword' => 'testNewPassword',
        ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getDateActivated()->willReturn(new \DateTime('2019-02-05'));
        $user = $userProphecy->reveal();

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getUser()->willReturn($user);
        $token = $tokenProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdatePassword($user, 'testNewPassword'))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManagerProphecy->decide($token, ['ROLE_SUPER_ADMIN'])->willReturn(true);
        $decisionManager = $decisionManagerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($user)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('testIri')->willReturn($user);
        $iriConverterProphecy->getIriFromItem($user)->willReturn('testIri');
        $iriConverter = $iriConverterProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $passwordEncoderProphecy = $this->prophesize(UserPasswordEncoderInterface::class);
        $passwordEncoderProphecy->isPasswordValid($user, 'testOldPassword')->willReturn(true);
        $passwordEncoder = $passwordEncoderProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'Password changed successfully.'], 200);

        $securityController = new SecurityController($commandBus, $decisionManager, $entityManager, $iriConverter, $passwordEncoder, $tokenStorage);
        $actualData = $securityController->changePasswordAction($request);

        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
    }

    public function testChangePasswordActionWithUserIriAndAccessNotGranted()
    {
        $data = [
            'userIri' => 'testIri',
            'oldPassword' => 'testOldPassword',
            'newPassword' => 'testNewPassword',
        ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getDateActivated()->willReturn(new \DateTime('2019-02-05'));
        $user = $userProphecy->reveal();

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getUser()->willReturn($user);
        $token = $tokenProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManagerProphecy->decide($token, ['ROLE_SUPER_ADMIN'])->willReturn(false);
        $decisionManager = $decisionManagerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('testIri')->willReturn($user);
        $iriConverterProphecy->getIriFromItem($user)->willReturn('testIr');
        $iriConverter = $iriConverterProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $passwordEncoderProphecy = $this->prophesize(UserPasswordEncoderInterface::class);

        $passwordEncoder = $passwordEncoderProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'You can only change your own password!'], 400);

        $securityController = new SecurityController($commandBus, $decisionManager, $entityManager, $iriConverter, $passwordEncoder, $tokenStorage);
        $actualData = $securityController->changePasswordAction($request);

        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
    }

    public function testChangePasswordActionWithoutUserIriAndPasswordNotMatched()
    {
        $data = [
           'oldPassword' => 'testOldPassword',
           'newPassword' => 'testNewPassword',
       ];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $user = $userProphecy->reveal();

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getUser()->willReturn($user);
        $token = $tokenProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $passwordEncoderProphecy = $this->prophesize(UserPasswordEncoderInterface::class);
        $passwordEncoderProphecy->isPasswordValid($user, 'testOldPassword')->willReturn(false);
        $passwordEncoder = $passwordEncoderProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'The old password does not match!'], 400);

        $securityController = new SecurityController($commandBus, $decisionManager, $entityManager, $iriConverter, $passwordEncoder, $tokenStorage);
        $actualData = $securityController->changePasswordAction($request);

        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
    }

    public function testChangePasswordActionWithoutToken()
    {
        $data = [];

        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()->willReturn(\json_encode($data));
        $body = $bodyProphecy->reveal();

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getBody()->willReturn($body);
        $request = $requestProphecy->reveal();

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $token = $tokenProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn(null);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $passwordEncoderProphecy = $this->prophesize(UserPasswordEncoderInterface::class);
        $passwordEncoder = $passwordEncoderProphecy->reveal();

        $expectedData = new JsonResponse(['message' => 'No token found!'], 400);

        $securityController = new SecurityController($commandBus, $decisionManager, $entityManager, $iriConverter, $passwordEncoder, $tokenStorage);
        $actualData = $securityController->changePasswordAction($request);

        $this->assertEquals($expectedData->getBody()->getContents(), $actualData->getBody()->getContents());
        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
    }

    public function authenticationTokenAction()
    {
        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $passwordEncoderProphecy = $this->prophesize(UserPasswordEncoderInterface::class);
        $passwordEncoder = $passwordEncoderProphecy->reveal();

        $expectedData = new EmptyResponse(401);

        $securityController = new SecurityController($commandBus, $decisionManager, $entityManager, $iriConverter, $passwordEncoder, $tokenStorage);
        $actualData = $securityController->authenticationTokenAction();

        $this->assertEquals($expectedData->getStatusCode(), $actualData->getStatusCode());
    }
}
