<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\ApplicationRequest;
use App\Entity\Promotion;
use App\Enum\ApplicationRequestType;
use App\Repository\PromotionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ApplicationRequestPromotionValidator extends ConstraintValidator
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

        if (!$constraint instanceof ApplicationRequestPromotion) {
            throw new UnexpectedTypeException($constraint, ApplicationRequestPromotion::class);
        }

        if (!$object instanceof ApplicationRequest) {
            throw new UnexpectedTypeException($constraint, ApplicationRequest::class);
        }

        if (ApplicationRequestType::CONTRACT_APPLICATION === $object->getType()->getValue()) {
            if (null !== $object->getPromotion()) {
                $promotion = $object->getPromotion();
                $promotionInventoryLevel = null;

                $promotionRepository = $this->entityManager->getRepository(Promotion::class);

                $existingPromotion = $promotionRepository->findOneBy(['promotionNumber' => $promotion->getPromotionNumber(), 'isBasedOn' => null]);

                if ($promotionRepository instanceof PromotionRepository) {
                    $promotionInventoryLevel = $promotionRepository->findCurrentInventoryLevel($existingPromotion);
                }

                $now = new \DateTime('now', new \DateTimeZone('UTC'));
                if (null === $existingPromotion) {
                    $this->context->buildViolation($constraint->applicationRequestPromotionNotExistingField)
                        ->atPath('promotion')
                        ->addViolation();
                } elseif (null !== $existingPromotion->getInventoryLevel()->getMaxValue() && (null !== $promotionInventoryLevel && $promotionInventoryLevel <= 0)) {
                    $this->context->buildViolation($constraint->applicationRequestPromotionFullyRedeemedField)
                        ->atPath('promotion')
                        ->addViolation();
                } elseif ($existingPromotion->getValidFrom() > $now || $existingPromotion->getValidThrough() < $now) {
                    $this->context->buildViolation($constraint->applicationRequestPromotionNotValidField)
                        ->atPath('promotion')
                        ->addViolation();
                } elseif (\count($existingPromotion->getCustomerTypes()) > 0 && (null !== $object->getCustomerType() && !\in_array($object->getCustomerType()->getValue(), $existingPromotion->getCustomerTypes(), true))) {
                    $this->context->buildViolation($constraint->applicationRequestPromotionCustomerTypeNotValidField)
                        ->atPath('promotion')
                        ->addViolation();
                } elseif (\count($existingPromotion->getContractTypes()) > 0 && (null !== $object->getContractType() && !\in_array($object->getContractType()->getValue(), $existingPromotion->getContractTypes(), true))) {
                    $this->context->buildViolation($constraint->applicationRequestPromotionContractTypeNotValidField)
                        ->atPath('promotion')
                        ->addViolation();
                }
            }
        }
    }
}
