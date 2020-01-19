<?php

declare(strict_types=1);

namespace App\Domain\Command\ApplicationRequest;

use App\Entity\UrlToken;
use Doctrine\ORM\EntityManagerInterface;

class UpdateApplicationRequestTokenHandler
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

    public function handle(UpdateApplicationRequestToken $command): void
    {
        $applicationRequest = $command->getApplicationRequest();

        $now = new \DateTimeImmutable();

        $token = \hash('md5', $applicationRequest->getApplicationRequestNumber().$now->getTimestamp());

        $urlToken = new UrlToken();
        $urlToken->setToken($token);
        $urlToken->setValidFrom($now);
        $urlToken->setValidThrough($now->add(new \DateInterval('P6D')));
        $this->entityManager->persist($urlToken);

        $applicationRequest->setUrlToken($urlToken);
    }
}
