<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Quotation;
use App\Entity\QuotationPriceConfiguration;
use App\Enum\QuotationStatus;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WonQuotationController
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

    /**
     * @param ServerRequestInterface $request
     */
    public function __invoke(ServerRequestInterface $request)
    {
        $params = \json_decode($request->getBody()->getContents(), true);

        $urlToken = \array_key_exists('urlToken', $params) ? $params['urlToken'] : null;
        $status = \array_key_exists('status', $params) ? $params['status'] : null;
        $acceptedOfferId = \array_key_exists('acceptedOfferId', $params) ? $params['acceptedOfferId'] : null;

        if (null === $urlToken || null === $status) {
            throw new BadRequestHttpException('UrlToken is required');
        }

        $qb = $this->entityManager->getRepository(Quotation::class)->createQueryBuilder('quotation');
        $expr = $qb->expr();

        $this->entityManager->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            /**
             * @var Quotation
             */
            $quotation = $qb->leftJoin('quotation.urlToken', 'tok')
                ->where($expr->eq('tok.token', ':urlToken'))
                ->andWhere($expr->lte('tok.validFrom', ':now'))
                ->andWhere($expr->gte('tok.validThrough', ':now'))
                ->setParameter('urlToken', $urlToken)
                ->setParameter('now', new \DateTime())
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getSingleScalarResult();

            if (!empty($quotation)) {
                if (null !== $status &&
                    \in_array($status, [QuotationStatus::WON, QuotationStatus::LOST], true)) {
                    $quotation = $this->updateQuotation($quotation, $status, $acceptedOfferId);
                }

                return $quotation;
            }
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }

        throw new BadRequestHttpException('UrlToken is not valid');
    }

    private function updateQuotation(Quotation $quotation, $status, $acceptedOfferId = null)
    {
        try {
            if (QuotationStatus::SENT !== $quotation->getStatus()->getValue()) {
                throw new BadRequestHttpException('This quotation is no longer valid!');
            }

            $quotation->setStatus(new QuotationStatus($status));
            $quotation->setUrlToken(null);

            if (null !== $acceptedOfferId) {
                $offer = $this->entityManager->getRepository(QuotationPriceConfiguration::class)->find($acceptedOfferId);

                $quotation->setAcceptedOffer($offer);
            }

            $this->entityManager->persist($quotation);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            return $quotation;
        } catch (\Exception $ex) {
            $this->entityManager->getConnection()->rollBack();
            throw $ex;
        }
    }
}
