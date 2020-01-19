<?php

declare(strict_types=1);

namespace App\Model;

use App\Disque\JobType;
use App\Entity\AffiliateProgram;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountAffiliateProgramUrl;
use App\Enum\AffiliateWebServicePartner;
use App\Enum\URLStatus;
use App\WebService\Affiliate\ClientFactory;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;

class AffiliateProgramURLGenerator
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var DisqueQueue
     */
    private $disqueQueue;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param ClientFactory          $clientFactory
     * @param DisqueQueue            $disqueQueue
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ClientFactory $clientFactory, DisqueQueue $disqueQueue, EntityManagerInterface $entityManager)
    {
        $this->clientFactory = $clientFactory;
        $this->disqueQueue = $disqueQueue;
        $this->entityManager = $entityManager;
    }

    public function generate(AffiliateProgram $affiliateProgram, CustomerAccount $customer)
    {
        $trackingUrl = '';
        $now = new \DateTime();
        $validFrom = $affiliateProgram->getValidFrom();
        $validThrough = $affiliateProgram->getValidThrough();

        if (null !== $validFrom && $validFrom > $now) {
            return $trackingUrl;
        }

        if (null !== $validThrough && $validThrough < $now) {
            return $trackingUrl;
        }

        if (AffiliateWebServicePartner::LAZADA_HASOFFERS === $affiliateProgram->getProvider()->getValue()) {
            $customerAffiliateProgramUrl = $this->entityManager->getRepository(CustomerAccountAffiliateProgramUrl::class)->findOneBy([
                'affiliateProgram' => $affiliateProgram->getId(),
                'customer' => $customer->getId(),
            ]);

            if (null !== $customerAffiliateProgramUrl) {
                if (URLStatus::ACTIVE === $customerAffiliateProgramUrl->getStatus()->getValue()) {
                    $trackingUrl = $customerAffiliateProgramUrl->getUrl();
                }
            } else {
                $job = new DisqueJob([
                    'data' => [
                        'affiliateProgram' => $affiliateProgram->getId(),
                        'customer' => $customer->getId(),
                    ],
                    'type' => JobType::AFFILIATE_PROGRAM_GENERATE_URL,
                ]);

                $this->disqueQueue->push($job);
            }
        } elseif (null !== $affiliateProgram->getBaseTrackingUrl()) {
            $trackingUrl = $this->clientFactory->getClient($affiliateProgram->getProvider()->getValue())->generateTrackingUrl($affiliateProgram->getBaseTrackingUrl(), ['customerAccountNumber' => $customer->getAccountNumber()]);
        }

        return $trackingUrl;
    }
}
