<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Entity\CustomerAccount;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Diactoros\Response\JsonResponse;

class UserResetController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     */
    public function __construct(EntityManagerInterface $entityManager, IriConverterInterface $iriConverter)
    {
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
    }

    /**
     * @Route("/reset/user", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function reset(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);

        $userIri = \array_key_exists('user', $params) ? $params['user'] : null;
        $oldCustomerIri = \array_key_exists('oldCustomer', $params) ? $params['oldCustomer'] : null;
        $newCustomerIri = \array_key_exists('newCustomer', $params) ? $params['newCustomer'] : null;

        if (null !== $userIri && null !== $oldCustomerIri && null !== $newCustomerIri) {
            try {
                $user = $this->iriConverter->getItemFromIri($userIri);
                $oldCustomer = $this->iriConverter->getItemFromIri($oldCustomerIri);
                $newCustomer = $this->iriConverter->getItemFromIri($newCustomerIri);
            } catch (\Exception $ex) {
                throw new BadRequestHttpException('Wrong iri provided.');
            }
            if ($user instanceof User && $oldCustomer instanceof CustomerAccount) {
                if ($user->getCustomerAccount()->getAccountNumber() === $oldCustomer->getAccountNumber()) {
                    if ($newCustomer instanceof CustomerAccount) {
                        if (null !== $newCustomer->getUser()) {
                            throw new BadRequestHttpException('New customer already have existing user account, this action not allowed');
                        }

                        $user->setCustomerAccount($newCustomer);
                        $oldCustomer->setUser(null);

                        $this->entityManager->persist($user);
                        $this->entityManager->persist($oldCustomer);

                        $this->entityManager->flush();

                        return new JsonResponse([
                            'message' => 'User reset successfully.',
                        ], 200);
                    }
                    throw new BadRequestHttpException('No customer found for new customer iri.');
                }
                throw new BadRequestHttpException('The old customer account provided and user not related.');
            }
        }
        throw new BadRequestHttpException('Invalid details');
    }
}
