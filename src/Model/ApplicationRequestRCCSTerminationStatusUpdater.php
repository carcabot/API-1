<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Enum\ApplicationRequestStatus;
use App\Enum\PaymentMode;
use Doctrine\ORM\EntityManagerInterface;

class ApplicationRequestRCCSTerminationStatusUpdater
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

    public function processArrayData(array $data)
    {
        foreach ($data as $datum) {
            $this->updateStatus($datum['applicationRequest']);
        }
        $this->entityManager->flush();
    }

    private function updateStatus(array $data)
    {
        $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy([
            'applicationRequestNumber' => $data['applicationRequestNumber'],
        ]);

        if (null !== $applicationRequest) {
            $applicationRequest->setStatus(new ApplicationRequestStatus($data['status']));
            $this->entityManager->persist($applicationRequest);

            if (null !== $applicationRequest->getContract()) {
                $this->updateContractStatus($applicationRequest->getContract(), $applicationRequest->getStatus());
            }
        }
    }

    private function updateContractStatus(Contract $contract, ApplicationRequestStatus $status)
    {
        if (ApplicationRequestStatus::COMPLETED === $status->getValue()) {
            $contract->setPaymentMode(new PaymentMode(PaymentMode::MANUAL));
            $this->entityManager->persist($contract);
        }
    }
}
