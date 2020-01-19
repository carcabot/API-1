<?php

declare(strict_types=1);

namespace App\Controller;

use App\Disque\JobType;
use App\Entity\InternalDocument;
use App\Entity\User;
use App\Enum\DocumentType;
use App\Model\ReportGenerator;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zend\Diactoros\Response\EmptyResponse;

class UserReportController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ReportGenerator
     */
    private $reportGenerator;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var DisqueQueue
     */
    private $reportsQueue;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ReportGenerator        $reportGenerator
     * @param NormalizerInterface    $normalizer
     * @param DisqueQueue            $reportsQueue
     * @param TokenStorageInterface  $tokenStorage
     */
    public function __construct(EntityManagerInterface $entityManager, ReportGenerator $reportGenerator, NormalizerInterface $normalizer, DisqueQueue $reportsQueue, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->reportGenerator = $reportGenerator;
        $this->normalizer = $normalizer;
        $this->reportsQueue = $reportsQueue;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param ServerRequestInterface $request
     */
    public function __invoke(ServerRequestInterface $request)
    {
        $params = \json_decode($request->getBody()->getContents(), true);
        $userEmail = null;

        if (null === $params) {
            $params = [];
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new BadRequestHttpException('No token found!');
        }

        $authenticatedUser = $token->getUser();

        if ($authenticatedUser instanceof User) {
            $userEmail = $authenticatedUser->getEmail();
        }

        if (null === $userEmail) {
            throw new BadRequestHttpException('User has no email!');
        }

        $document = new InternalDocument();
        $document->setType(new DocumentType(DocumentType::USER_REPORT));
        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $job = new DisqueJob([
            'data' => [
                'documentId' => $document->getId(),
                'params' => $params,
                'recipient' => $userEmail,
            ],
            'type' => JobType::USER_REPORT_GENERATE,
        ]);

        $this->reportsQueue->push($job);

        return new EmptyResponse(200);
    }
}
