<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\ApplicationRequest;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use App\WebService\Billing\Services\DataMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ApplicationRequestRenewalValidator extends ConstraintValidator
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
        $paths = [];

        if (!$constraint instanceof ApplicationRequestRenewal) {
            throw new UnexpectedTypeException($constraint, ApplicationRequestRenewal::class);
        }

        if ($object instanceof ApplicationRequest) {
            $entity = ApplicationRequest::class;
        }

        if (null !== $object && null !== $entity && ApplicationRequestStatus::DRAFT !== $object->getStatus()->getValue()) {
            if (ApplicationRequestType::CONTRACT_RENEWAL === $object->getType()->getValue()) {
                if (null === $object->getContract()) {
                    $paths[] = 'contract';
                }

                if (null !== $object->getContract() && null === $object->getId()) {
                    $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('applicationRequest');
                    $expr = $qb->expr();

                    $existingRenewalRequest = $qb->leftJoin('applicationRequest.contract', 'contract')
                        ->where($expr->eq('contract.contractNumber', ':contractNumber'))
                        ->andWhere($expr->eq('applicationRequest.status', $expr->literal(ApplicationRequestStatus::IN_PROGRESS)))
                        ->setParameter('contractNumber', $object->getContract()->getContractNumber())
                        ->getQuery()
                        ->getResult();

                    if (null !== $existingRenewalRequest && \count($existingRenewalRequest) > 0) {
                        $this->context->buildViolation($constraint->applicationRequestRenewalExistingField)
                            ->atPath('contract')
                            ->addViolation();
                    }
                }

                foreach ($paths as $path) {
                    $this->context->buildViolation($constraint->applicationRequestRenewalRequiredField)
                        ->atPath($path)
                        ->addViolation();
                }
            }
        }
    }
}
