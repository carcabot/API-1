<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Enum\TwoFactorAuthenticationType;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UserTwoFactorAuthenticationSetupValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PhoneNumberUtil
     */
    private $phoneNumberUtil;

    /**
     * @param EntityManagerInterface $entityManager
     * @param PhoneNumberUtil        $phoneNumberUtil
     */
    public function __construct(EntityManagerInterface $entityManager, PhoneNumberUtil $phoneNumberUtil)
    {
        $this->entityManager = $entityManager;
        $this->phoneNumberUtil = $phoneNumberUtil;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($protocol, Constraint $constraint)
    {
        if (!$constraint instanceof UserTwoFactorAuthenticationSetup) {
            throw new UnexpectedTypeException($constraint, UserTwoFactorAuthenticationSetup::class);
        }

        if (!$protocol instanceof User) {
            throw new UnexpectedTypeException($protocol, User::class);
        }

        /** @var User $user */
        $user = $protocol;

        if ($user->hasTwoFactorAuthentication()) {
            if (null !== $user->getTwoFactorAuthenticationType()) {
                if (null === $user->getTwoFactorAuthenticationRecipient()) {
                    $this->context->buildViolation($constraint->noRecipientSpecified)
                        ->addViolation();
                } else {
                    if (TwoFactorAuthenticationType::SMS === $user->getTwoFactorAuthenticationType()->getValue()) {
                        try {
                            $recipient = $this->phoneNumberUtil->parse($user->getTwoFactorAuthenticationRecipient());
                            if (!$this->phoneNumberUtil->isValidNumber($recipient)) {
                                $this->context->buildViolation($constraint->invalidNumber)
                                    ->addViolation();
                            }
                        } catch (\Exception $ex) {
                            $this->context->buildViolation($constraint->invalidNumber)
                                ->addViolation();
                        }
                    } elseif (TwoFactorAuthenticationType::EMAIL === $user->getTwoFactorAuthenticationType()->getValue()) {
                        if (!\filter_var($user->getTwoFactorAuthenticationRecipient(), FILTER_VALIDATE_EMAIL)) {
                            $this->context->buildViolation($constraint->invalidEmail)
                                ->addViolation();
                        }
                    }
                }
            }
        }
    }
}
