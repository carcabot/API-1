<?php

declare(strict_types=1);

namespace App\Domain\Command\ApplicationRequest;

use App\Entity\ContactPoint;
use App\Entity\EmailActivity;
use App\Enum\EmailType;
use Doctrine\ORM\EntityManagerInterface;

class UpdateEmailActivityHandler
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

    public function handle(UpdateEmailActivity $command): void
    {
        $applicationRequest = $command->getApplicationRequest();

        $emailActivity = new EmailActivity();
        $emailActivity->setType(new EmailType(EmailType::APPLICATION_REQUEST_AUTHORIZATION_NOTIFICATION));

        if (null !== $applicationRequest->getPersonDetails()) {
            /**
             * @var ContactPoint[]
             */
            $contactPoints = $applicationRequest->getPersonDetails()->getContactPoints();
            $isEmailSet = false;

            foreach ($contactPoints as $contactPoint) {
                foreach ($contactPoint->getEmails() as $email) {
                    $emailActivity->addToRecipient($email);
                    $isEmailSet = true;
                    break;
                }
                if ($isEmailSet) {
                    break;
                }
            }

            $this->entityManager->persist($emailActivity);
            $applicationRequest->addActivity($emailActivity);

            $this->entityManager->persist($applicationRequest);
        }
    }
}
