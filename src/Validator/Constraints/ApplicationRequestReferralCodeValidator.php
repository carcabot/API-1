<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\ApplicationRequest;
use App\Entity\CustomerAccount;
use App\Enum\ApplicationRequestType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ApplicationRequestReferralCodeValidator extends ConstraintValidator
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
     * {@inheritdoc}
     */
    public function validate($protocol, Constraint $constraint)
    {
        $object = $protocol;

        if (!$constraint instanceof ApplicationRequestReferralCode) {
            throw new UnexpectedTypeException($constraint, ApplicationRequestReferralCode::class);
        }

        if (!$object instanceof ApplicationRequest) {
            throw new UnexpectedTypeException($constraint, ApplicationRequest::class);
        }

        if (ApplicationRequestType::CONTRACT_APPLICATION === $object->getType()->getValue()) {
            if (null !== $object->getReferralCode() && !empty($object->getReferralCode())) {
                $referralCode = $object->getReferralCode();
                $customer = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['referralCode' => $referralCode]);
                if (null !== $customer && $customer === $object->getCustomer()) {
                    $this->context->buildViolation($constraint->applicationRequestReferralCodeNotAllowed)
                        ->atPath('referralCode')
                        ->addViolation();
                } elseif (null === $customer) {
                    $this->context->buildViolation($constraint->applicationRequestReferralCodeNotExists)
                        ->atPath('referralCode')
                        ->addViolation();
                }
            }
        }
    }
}
