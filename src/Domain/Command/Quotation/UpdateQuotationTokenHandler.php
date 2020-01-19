<?php

declare(strict_types=1);

namespace App\Domain\Command\Quotation;

use App\Entity\UrlToken;
use Doctrine\ORM\EntityManagerInterface;

class UpdateQuotationTokenHandler
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

    public function handle(UpdateQuotationToken $command): void
    {
        $quotation = $command->getQuotation();

        $now = new \DateTimeImmutable();

        $token = \hash('md5', $quotation->getQuotationNumber().$now->getTimestamp());

        $urlToken = new UrlToken();
        $urlToken->setToken($token);
        $urlToken->setValidFrom($now);
        $urlToken->setValidThrough($now->add(new \DateInterval('P6D')));
        $this->entityManager->persist($urlToken);

        $quotation->setUrlToken($urlToken);
    }
}
