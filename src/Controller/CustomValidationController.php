<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ContractStatus;
use App\Enum\PostalAddressType;
use App\Service\IdentificationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

/**
 * @Route("/validation")
 */
class CustomValidationController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IdentificationHelper
     */
    private $identificationHelper;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @param EntityManagerInterface $entityManager
     * @param IdentificationHelper   $identificationHelper
     * @param IriConverterInterface  $iriConverter
     */
    public function __construct(EntityManagerInterface $entityManager, IdentificationHelper $identificationHelper, IriConverterInterface $iriConverter)
    {
        $this->entityManager = $entityManager;
        $this->identificationHelper = $identificationHelper;
        $this->iriConverter = $iriConverter;
    }

    /**
     * @Route("/contracts/ebs_account_number/{ebsAccountNumber}", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     * @param string                 $ebsAccountNumber
     *
     * @return HttpResponseInterface
     */
    public function validateEBSAccountNumber(ServerRequestInterface $request, string $ebsAccountNumber): HttpResponseInterface
    {
        if (!empty($ebsAccountNumber)) {
            $match = \preg_match('/^(?!93)[0-9]{9}[0-9-]{1}$/', $ebsAccountNumber);

            if (1 === $match) {
                $contracts = $this->entityManager->getRepository(Contract::class)->findBy([
                    'ebsAccountNumber' => $ebsAccountNumber,
                    'status' => new ContractStatus(ContractStatus::ACTIVE),
                ]);

                if (0 === \count($contracts)) {
                    return new EmptyResponse(204);
                }
            }
        }

        throw new BadRequestHttpException();
    }

    /**
     * @Route("/customer_accounts/blacklist_status", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function validateCustomerAccountBlacklistStatus(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = $request->getQueryParams() ?? [];

        $identificationName = \array_key_exists('identificationName', $params) ? $params['identificationName'] : null;
        $identificationValue = \array_key_exists('identificationValue', $params) ? $params['identificationValue'] : null;

        if (!empty($identificationName) && !empty($identificationValue)) {
            $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customerAccount');
            $expr = $qb->expr();
            $orExprs = [];
            $now = new \DateTime();

            $aliases = [
                'corporationIdentifier',
                'personIdentifier',
            ];

            foreach ($aliases as $alias) {
                $orExprs[$alias] = $expr->andX(
                    $expr->eq($alias.'.value', ':identificationValue'),
                    $expr->eq($alias.'.name', ':identificationName')
                );
            }

            $customerAccounts = $qb->leftJoin('customerAccount.personDetails', 'person')
                ->leftJoin('person.identifiers', 'personIdentifier')
                ->leftJoin('customerAccount.corporationDetails', 'corporation')
                ->leftJoin('corporation.identifiers', 'corporationIdentifier')
                ->where($expr->orX(
                    $orExprs['corporationIdentifier'],
                    $orExprs['personIdentifier']
                ))
                ->setParameter('identificationName', $identificationName)
                ->setParameter('identificationValue', $identificationValue)
                ->getQuery()
                ->getResult();

            if (0 === \count($customerAccounts)) {
                return new JsonResponse(['message' => 'No customer found.'], 200);
            }

            foreach ($customerAccounts as $customerAccount) {
                if (
                    null !== $customerAccount->getPersonDetails() &&
                    $this->identificationHelper->matchIdentifier($customerAccount->getPersonDetails()->getIdentifiers(), $identificationValue, $identificationName) &&
                    null !== $customerAccount->getDateBlacklisted() &&
                    $customerAccount->getDateBlacklisted() <= new \DateTime()
                ) {
                    return new JsonResponse(['message' => 'Customer has been blacklisted.'], 400);
                }

                if (
                    null !== $customerAccount->getCorporationDetails() &&
                    $this->identificationHelper->matchIdentifier($customerAccount->getCorporationDetails()->getIdentifiers(), $identificationValue, $identificationName) &&
                    null !== $customerAccount->getDateBlacklisted() &&
                    $customerAccount->getDateBlacklisted() <= new \DateTime()
                ) {
                    return new JsonResponse(['message' => 'Customer has been blacklisted.'], 400);
                }
            }

            return new JsonResponse(['message' => 'Customer is not blacklisted.'], 200);
        }

        return new JsonResponse(['message' => '???'], 400);
    }

    /**
     * @Route("/customer_accounts/identification", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function validateCustomerAccountIdentity(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = $request->getQueryParams() ?? [];

        $customerName = \array_key_exists('customerName', $params) ? $params['customerName'] : null;
        $name = \array_key_exists('name', $params) ? $params['name'] : null;
        $value = \array_key_exists('value', $params) ? $params['value'] : null;

        if (!empty($customerName) && !empty($name) && !empty($value)) {
            $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customerAccount');
            $expr = $qb->expr();
            $orExprs = [];
            $now = new \DateTime();

            $aliases = [
                'corporationIdentifier',
                'personIdentifier',
            ];

            foreach ($aliases as $alias) {
                $orExprs[$alias] = $expr->andX(
                    $expr->eq($alias.'.value', ':value'),
                    $expr->eq($alias.'.name', ':name')
                );
            }

            $customerAccounts = $qb->leftJoin('customerAccount.personDetails', 'person')
                ->leftJoin('person.identifiers', 'personIdentifier')
                ->leftJoin('customerAccount.corporationDetails', 'corporation')
                ->leftJoin('corporation.identifiers', 'corporationIdentifier')
                ->where($expr->orX(
                    $orExprs['corporationIdentifier'],
                    $orExprs['personIdentifier']
                ))
                ->setParameter('name', $name)
                ->setParameter('value', $value)
                ->getQuery()
                ->getResult();

            if (0 === \count($customerAccounts)) {
                return new EmptyResponse(204);
            }

            foreach ($customerAccounts as $customerAccount) {
                if (null !== $customerAccount->getPersonDetails()) {
                    if (true === $this->identificationHelper->matchIdentifier($customerAccount->getPersonDetails()->getIdentifiers(), $value, $name) &&
                        $customerName === $customerAccount->getPersonDetails()->getName()
                    ) {
                        return new JsonResponse([
                            'customerAccount' => $this->iriConverter->getIriFromItem($customerAccount),
                        ], 200);
                    }
                } elseif (null !== $customerAccount->getCorporationDetails()) {
                    if (true === $this->identificationHelper->matchIdentifier($customerAccount->getCorporationDetails()->getIdentifiers(), $value, $name) &&
                        $customerName === $customerAccount->getCorporationDetails()->getName()
                    ) {
                        return new JsonResponse([
                            'customerAccount' => $this->iriConverter->getIriFromItem($customerAccount),
                        ], 200);
                    }
                }
            }
        }

        return new JsonResponse(['message' => 'Name and identification does not match.'], 400);
    }

    /**
     * @Route("/contracts/mssl_account_number/{msslAccountNumber}", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     * @param string                 $msslAccountNumber
     *
     * @return HttpResponseInterface
     */
    public function validateMSSLAccountNumber(ServerRequestInterface $request, string $msslAccountNumber): HttpResponseInterface
    {
        if (!empty($msslAccountNumber)) {
            $match = \preg_match('/^(93)[0-9]{8}$/', $msslAccountNumber);

            if (1 === $match) {
                $contracts = $this->entityManager->getRepository(Contract::class)->findBy([
                    'msslAccountNumber' => $msslAccountNumber,
                    'status' => new ContractStatus(ContractStatus::ACTIVE),
                ]);

                if (0 === \count($contracts)) {
                    return new EmptyResponse(204);
                }
            }
        }

        throw new BadRequestHttpException();
    }

    /**
     * @Route("/application_requests/address", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function validateApplicationRequestAddress(ServerRequestInterface $request): HttpResponseInterface
    {
        $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('applicationRequest');
        $params = $request->getQueryParams() ?? [];
        $type = new PostalAddressType(PostalAddressType::PREMISE_ADDRESS);
        $keys = \array_keys($params);
        $alias = 'applicationRequestAddress';
        $search = [];

        foreach ($keys as $key) {
            $search[] = \sprintf('%s.%s = \'%s\'', $alias, $key, $params[$key]);
        }

        $finalSearch = \implode(' AND ', $search);

        $applicationRequests = $qb->leftJoin('applicationRequest.addresses', $alias)
            ->where($finalSearch)
            ->andWhere($qb->expr()->eq($alias.'.type', ':type'))
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();

        foreach ($applicationRequests as $applicationRequest) {
            if (\in_array($applicationRequest->getStatus()->getValue(), [
                ApplicationRequestStatus::COMPLETED,
                ApplicationRequestStatus::IN_PROGRESS,
                ApplicationRequestStatus::PENDING,
                ApplicationRequestStatus::PENDING_BILLING_STATUS,
            ], true)) {
                throw new BadRequestHttpException();
            }
        }

        return new EmptyResponse(204);
    }

    /**
     * @Route("/customer_accounts/email/{email}", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     * @param string                 $email
     *
     * @return HttpResponseInterface
     */
    public function validateCustomerAccountEmail(ServerRequestInterface $request, string $email): HttpResponseInterface
    {
        if (!empty($email)) {
            $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customerAccount');
            $expr = $qb->expr();

            $customers = $qb->leftJoin('customerAccount.personDetails', 'person')
                ->leftJoin('person.contactPoints', 'contactPoint')
                ->where($expr->andX(
                    $expr->eq(\sprintf(<<<'SQL'
                        jsonb_contains(CAST(lower(CAST(%s.%s AS text)) AS jsonb), :%s)
SQL
                    , 'contactPoint', 'emails', 'email'),
                    $expr->literal(true))
                ))
                ->setParameter('email', \json_encode(\strtolower($email)))
                ->getQuery()
                ->getResult();

            if (0 === \count($customers)) {
                return new EmptyResponse(204);
            }
        }

        throw new BadRequestHttpException();
    }

    /**
     * @Route("/customer_accounts/account_number/{accountNumber}", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     * @param string                 $accountNumber
     *
     * @return HttpResponseInterface
     */
    public function validateCustomerAccountNumber(ServerRequestInterface $request, string $accountNumber): HttpResponseInterface
    {
        if (!empty($accountNumber)) {
            $customers = $this->entityManager->getRepository(CustomerAccount::class)->findBy([
                'accountNumber' => $accountNumber,
            ]);

            if (0 === \count($customers)) {
                return new EmptyResponse(204);
            }
        }

        throw new BadRequestHttpException();
    }

    /**
     * @Route("/application_requests/sp_account_number", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function validateSPAccountNumber(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = $request->getQueryParams() ?? [];

        $spAccountNumber = \array_key_exists('spAccountNumber', $params) ? $params['spAccountNumber'] : null;
        $idName = \array_key_exists('idName', $params) ? $params['idName'] : null;
        $idValue = \array_key_exists('idValue', $params) ? $params['idValue'] : null;

        if (!empty($spAccountNumber) && !empty($idName) && !empty($idValue)) {
            $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('app');
            $expr = $qb->expr();
            $orExprs = [];
            $now = new \DateTime();

            $aliases = [
                'corporationIdentifier',
                'personIdentifier',
            ];

            foreach ($aliases as $alias) {
                $orExprs[$alias] = $expr->andX(
                    $expr->eq($alias.'.value', ':idValue'),
                    $expr->eq($alias.'.name', ':idName')
                );
            }

            $applicationRequests = $qb->leftJoin('app.personDetails', 'person')
                ->leftJoin('person.identifiers', 'personIdentifier')
                ->leftJoin('app.corporationDetails', 'corporation')
                ->leftJoin('corporation.identifiers', 'corporationIdentifier')
                ->where($expr->orX(
                    $orExprs['corporationIdentifier'],
                    $orExprs['personIdentifier']
                ))
                ->andWhere($expr->orX(
                    $expr->eq('app.ebsAccountNumber', ':spAccountNumber'),
                    $expr->eq('app.msslAccountNumber', ':spAccountNumber')
                ))
                ->andWhere($expr->eq('app.status', ':status'))
                ->setParameter('idValue', $idValue)
                ->setParameter('idName', $idName)
                ->setParameter('spAccountNumber', $spAccountNumber)
                ->setParameter('status', ApplicationRequestStatus::IN_PROGRESS)
                ->getQuery()
                ->getResult();

            if (0 === \count($applicationRequests)) {
                return new EmptyResponse(204);
            }

            return new JsonResponse(['message' => 'Error. NRIC and SP Account Number already has an existing application request.'], 400);
        }

        return new JsonResponse(['message' => 'Error. Incomplete information.'], 400);
    }
}
