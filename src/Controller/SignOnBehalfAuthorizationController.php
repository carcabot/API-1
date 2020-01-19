<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ApplicationRequest;
use App\Enum\ApplicationRequestStatus;
use App\Model\AuthorizationLetterFileGenerator;
use App\Service\UserCreationHelper;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SignOnBehalfAuthorizationController
{
    /**
     * @var UserCreationHelper
     */
    private $userCreationHelper;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AuthorizationLetterFileGenerator
     */
    private $authorizationLetterFileGenerator;

    /**
     * @param UserCreationHelper               $userCreationHelper
     * @param EntityManagerInterface           $entityManager
     * @param AuthorizationLetterFileGenerator $authorizationLetterFileGenerator
     */
    public function __construct(UserCreationHelper $userCreationHelper, EntityManagerInterface $entityManager, AuthorizationLetterFileGenerator $authorizationLetterFileGenerator)
    {
        $this->userCreationHelper = $userCreationHelper;
        $this->entityManager = $entityManager;
        $this->authorizationLetterFileGenerator = $authorizationLetterFileGenerator;
    }

    /**
     * @param ServerRequestInterface $request
     */
    public function __invoke(ServerRequestInterface $request)
    {
        $params = \json_decode($request->getBody()->getContents(), true);

        $urlToken = \array_key_exists('urlToken', $params) ? $params['urlToken'] : null;
        $status = \array_key_exists('status', $params) ? $params['status'] : null;

        if (null === $urlToken || null === $status) {
            throw new BadRequestHttpException('UrlToken and status are required');
        }

        if (ApplicationRequestStatus::REJECTED_BY_OWNER !== $status && ApplicationRequestStatus::IN_PROGRESS !== $status) {
            throw new BadRequestHttpException('Status is not valid');
        }

        $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('appl');
        $expr = $qb->expr();

        $this->entityManager->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            /**
             * @var ApplicationRequest[]
             */
            $applicationRequests = $qb->leftJoin('appl.urlToken', 'tok')
                ->where($expr->eq('tok.token', ':urlToken'))
                ->andWhere($expr->lte('tok.validFrom', ':now'))
                ->andWhere($expr->gte('tok.validThrough', ':now'))
                ->setParameter('urlToken', $urlToken)
                ->setParameter('now', new \DateTime())
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getResult();

            if (\count($applicationRequests) > 0) {
                /**
                 * @var ApplicationRequest
                 */
                $applicationRequest = $applicationRequests[0];

                if (ApplicationRequestStatus::PENDING !== $applicationRequest->getStatus()->getValue()) {
                    if (\in_array($applicationRequest->getStatus()->getValue(), [
                        ApplicationRequestStatus::AUTHORIZATION_URL_EXPIRED,
                        ApplicationRequestStatus::CANCELLED,
                        ApplicationRequestStatus::REJECTED,
                        ApplicationRequestStatus::REJECTED_BY_OWNER,
                        ApplicationRequestStatus::VOIDED,
                    ], true)) {
                        throw new BadRequestHttpException('This application is no longer valid!');
                    }

                    return $applicationRequest;
                }

                $applicationRequest->setStatus(new ApplicationRequestStatus($status));
                $applicationRequest->setUrlToken(null);

                // @todo we should move this logic to event listener right?
                $authorizationLetterFilePath = $this->authorizationLetterFileGenerator->generatePdf($applicationRequest);
                $authorizationLetter = $this->authorizationLetterFileGenerator->convertFileToDigitalDocument($authorizationLetterFilePath);

                $applicationRequest->addSupplementaryFile($authorizationLetter);

                $this->entityManager->persist($applicationRequest);
                $this->entityManager->flush();
                $this->entityManager->getConnection()->commit();

                return $applicationRequest;
            }
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }

        throw new BadRequestHttpException('UrlToken is not valid');
    }
}
