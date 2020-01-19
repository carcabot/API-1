<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\ApplicationRequest;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use App\Enum\IdentificationName;
use App\Enum\PostalAddressType;
use App\Enum\RefundType;
use App\WebService\Billing\Services\DataMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ApplicationRequestTransferOutValidator extends ConstraintValidator
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

        if (!$constraint instanceof ApplicationRequestTransferOut) {
            throw new UnexpectedTypeException($constraint, ApplicationRequestTransferOut::class);
        }

        if ($object instanceof ApplicationRequest) {
            $entity = ApplicationRequest::class;
        }

        if (null !== $object && null !== $entity && ApplicationRequestStatus::DRAFT !== $object->getStatus()->getValue()) {
            if (ApplicationRequestType::TRANSFER_OUT === $object->getType()->getValue()) {
                if (null === $object->getContract()) {
                    $paths[] = 'contract';
                }

                $refundee = $object->getRefundee();

                if (null === $object->getPreferredEndDate()) {
                    $paths[] = 'preferredEndDate';
                }

                if (null === $object->getDepositRefundType()) {
                    $paths[] = 'depositRefundType';
                } elseif (RefundType::FULL_REFUND === $object->getDepositRefundType()->getValue()) {
                    $refundAddress = null;

                    foreach ($object->getAddresses() as $address) {
                        if (PostalAddressType::REFUND_ADDRESS === $address->getType()->getValue()) {
                            $refundAddress = $address;
                            break;
                        }
                    }

                    if (null !== $refundAddress) {
                        $errMessage = '';
                        if (null === $refundAddress->getAddressCountry()) {
                            $paths[] = 'addresses.addressCountry';
                        }

                        if (null === $refundAddress->getAddressLocality()) {
                            $paths[] = 'addresses.addressLocality';
                        }

                        if (null === $refundAddress->getPostalCode()) {
                            $paths[] = 'addresses.postalCode';
                        }

                        if (null === $refundAddress->getAddressCountry()) {
                            $paths[] = 'addresses.addressCountry';
                        }

                        if (null === $refundAddress->getAddressCountry()) {
                            $paths[] = 'addresses.addressCountry';
                        }
                        //to do, validate address Line 3
                    } else {
                        $paths[] = 'addresses';
                    }

                    if (null === $object->getTerminationReason()) {
                        $paths[] = 'terminationReason';
                    }

                    $personDetails = $object->getRefundeeDetails();

                    if (null !== $personDetails) {
                        $identity = $this->dataMapper->mapIdentifierByKey($personDetails->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
                        if (null === $identity) {
                            $paths[] = 'refundeeDetails.identifiers.value';
                        }

                        $name = $personDetails->getName();
                        if (null === $name) {
                            $paths[] = 'refundeeDetails.name';
                        }
                    } else {
                        $paths[] = 'refundeeDetails';
                    }
                }

                if (null !== $object->getContract() && null === $object->getId()) {
                    $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('applicationRequest');
                    $expr = $qb->expr();

                    $existingClosureRequest = $qb->leftJoin('applicationRequest.contract', 'contract')
                        ->where($expr->eq('contract.contractNumber', ':contractNumber'))
                        ->andWhere($expr->eq('applicationRequest.status', $expr->literal(ApplicationRequestStatus::IN_PROGRESS)))
                        ->setParameter('contractNumber', $object->getContract()->getContractNumber())
                        ->getQuery()
                        ->getResult();

                    if (null !== $existingClosureRequest) {
                        $this->context->buildViolation($constraint->applicationRequestTransferOutExistingField)
                            ->atPath('contract')
                            ->addViolation();
                    }
                }

                foreach ($paths as $path) {
                    $this->context->buildViolation($constraint->applicationRequestTransferOutRequiredField)
                        ->atPath($path)
                        ->addViolation();
                }
            }
        }
    }
}
