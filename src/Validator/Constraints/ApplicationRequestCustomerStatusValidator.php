<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\ApplicationRequest;
use App\Entity\CustomerBlacklistConfiguration;
use App\Enum\ApplicationRequestStatus;
use App\Enum\BlacklistConfigurationAction;
use App\WebService\Billing\Services\DataMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ApplicationRequestCustomerStatusValidator extends ConstraintValidator
{
    /**
     * @var DataMapper
     */
    private $dataMapper;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param DataMapper             $dataMapper
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(DataMapper $dataMapper, EntityManagerInterface $entityManager)
    {
        $this->dataMapper = $dataMapper;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($protocol, Constraint $constraint)
    {
        $entity = null;
        $object = $protocol;
        $addresses = null;
        $applicationRequest = null;

        if (!$constraint instanceof ApplicationRequestCustomerStatus) {
            throw new UnexpectedTypeException($constraint, ApplicationRequestCustomerStatus::class);
        }

        if ($object instanceof ApplicationRequest) {
            $entity = ApplicationRequest::class;
        }

        if (null !== $object && null !== $entity) {
            if (ApplicationRequestStatus::DRAFT !== $object->getStatus()->getValue()) {
                $customerBlacklistConfiguration = $this->entityManager->getRepository(CustomerBlacklistConfiguration::class)->findOneBy(['action' => new BlacklistConfigurationAction(BlacklistConfigurationAction::CUSTOMER_SUBMIT_APPLICATION)]);
                if (null !== $customerBlacklistConfiguration) {
                    if (true === $customerBlacklistConfiguration->isEnabled()) {
                        if (null !== $object->getCustomer()) {
                            if (null !== $object->getCustomer()->getDateBlacklisted()
                                && $object->getCustomer()->getDateBlacklisted() <= new \DateTime('now', new \DateTimeZone('UTC'))) {
                                $this->context->buildViolation($constraint->customerBlacklisted)
                                    ->atPath('customer')
                                    ->addViolation();
                            }
                        }
                    }
                }
                $contactPersonBlacklistConfiguration = $this->entityManager->getRepository(CustomerBlacklistConfiguration::class)->findOneBy(['action' => new BlacklistConfigurationAction(BlacklistConfigurationAction::CONTACT_PERSON_SUBMIT_APPLICATION)]);
                if (null !== $contactPersonBlacklistConfiguration) {
                    if (true === $contactPersonBlacklistConfiguration->isEnabled()) {
                        if (null !== $object->getContactPerson()) {
                            if (null !== $object->getContactPerson()->getDateBlacklisted()
                                && $object->getContactPerson()->getDateBlacklisted() <= new \DateTime('now', new \DateTimeZone('UTC'))) {
                                $this->context->buildViolation($constraint->contactPersonBlacklisted)
                                    ->atPath('contactPerson')
                                    ->addViolation();
                            }
                        }
                    }
                }
            }
        }
    }
}
