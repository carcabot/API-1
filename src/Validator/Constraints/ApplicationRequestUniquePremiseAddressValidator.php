<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\ApplicationRequest;
use App\Enum\AccountType;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use App\Enum\ContractStatus;
use App\Enum\PostalAddressType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ApplicationRequestUniquePremiseAddressValidator extends ConstraintValidator
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
        $entity = null;
        $object = $protocol;
        $addresses = null;
        $applicationRequest = null;
        $identifierExpr = null;

        $qb = $this->entityManager->createQueryBuilder();
        $expr = $this->entityManager->getExpressionBuilder();

        if (!$constraint instanceof ApplicationRequestUniquePremiseAddress) {
            throw new UnexpectedTypeException($constraint, ApplicationRequestUniquePremiseAddress::class);
        }

        if ($object instanceof ApplicationRequest) {
            $entity = ApplicationRequest::class;
        }

        if (null !== $object && null !== $entity) {
            $premiseAddress = null;
            $premiseAddressKey = null;
            $existingApplicationRequests = [];

            foreach ($object->getAddresses() as $key => $address) {
                if (PostalAddressType::PREMISE_ADDRESS === $address->getType()->getValue()) {
                    $premiseAddress = $address;
                    $premiseAddressKey = $key;
                }
            }

            if (null !== $premiseAddress && null !== $premiseAddressKey) {
                $queryParameters = [
                    'addressCountry' => $premiseAddress->getAddressCountry(),
                    'addressLocality' => $premiseAddress->getAddressLocality(),
                    'addressRegion' => $premiseAddress->getAddressRegion(),
                    'buildingName' => $premiseAddress->getBuildingName(),
                    'floor' => $premiseAddress->getFloor(),
                    'houseNumber' => $premiseAddress->getHouseNumber(),
                    'postalCode' => $premiseAddress->getPostalCode(),
                    'statusActive' => ContractStatus::ACTIVE,
                    'statusCompleted' => ApplicationRequestStatus::COMPLETED,
                    'statusInProgress' => ApplicationRequestStatus::IN_PROGRESS,
                    'type' => $object->getType()->getValue(),
                ];

                $qb->select('applicationRequest')
                    ->from($entity, 'applicationRequest')
                    ->join('applicationRequest.addresses', 'address')
                    ->leftJoin('applicationRequest.corporationDetails', 'corporationDetails')
                    ->leftJoin('corporationDetails.identifiers', 'corporationIdentifiers')
                    ->leftJoin('applicationRequest.personDetails', 'personDetails')
                    ->leftJoin('personDetails.identifiers', 'personIdentifiers');

                if (ApplicationRequestType::CONTRACT_APPLICATION === $object->getType()->getValue()) {
                    if (null !== $object->getCustomer()) {
                        $customerAccount = $object->getCustomer();
                        if (AccountType::CORPORATE === $customerAccount->getType()->getValue() && null !== $object->getCorporationDetails()) {
                            foreach ($object->getCorporationDetails()->getIdentifiers() as $identifier) {
                                $queryParameters['identifierName'] = $identifier->getName();
                                $queryParameters['identifierValue'] = $identifier->getValue();
                            }
                        } elseif (AccountType::INDIVIDUAL === $customerAccount->getType()->getValue() && null !== $object->getPersonDetails()) {
                            foreach ($object->getPersonDetails()->getIdentifiers() as $identifier) {
                                $queryParameters['identifierName'] = $identifier->getName();
                                $queryParameters['identifierValue'] = $identifier->getValue();
                            }
                        }

                        if (isset($queryParameters['identifierName']) && isset($queryParameters['identifierValue'])) {
                            $identifierExpr = $expr->orX(
                                $expr->andX(
                                    $expr->eq('personIdentifiers.name', ':identifierName'),
                                    $expr->eq('personIdentifiers.value', ':identifierValue')
                                ),
                                $expr->andX(
                                    $expr->eq('corporationIdentifiers.name', ':identifierName'),
                                    $expr->eq('corporationIdentifiers.value', ':identifierValue')
                                )
                            );
                        }
                    }

                    $applicationRequestExpr = $expr->andX(
                        $expr->orX(
                            $expr->eq('applicationRequest.status', ':statusCompleted'),
                            $expr->eq('applicationRequest.status', ':statusInProgress')
                        ),
                        $expr->eq('address.addressCountry', ':addressCountry'),
                        $expr->eq('address.addressLocality', ':addressLocality'),
                        $expr->eq('address.addressRegion', ':addressRegion'),
                        $expr->eq('address.buildingName', ':buildingName'),
                        $expr->eq('address.floor', ':floor'),
                        $expr->eq('address.houseNumber', ':houseNumber'),
                        $expr->eq('address.postalCode', ':postalCode')
                    );

                    if (null !== $identifierExpr) {
                        $applicationRequestExpr->add($identifierExpr);
                    }

                    $qb->leftJoin('applicationRequest.contract', 'contract')
                        ->leftJoin('contract.addresses', 'contractAddress')
                        ->leftJoin('contractAddress.address', 'postalAddress')
                        ->where($expr->orX(
                            $expr->andX(
                                $expr->eq('contract.status', ':statusActive'),
                                $expr->eq('postalAddress.addressCountry', ':addressCountry'),
                                $expr->eq('postalAddress.addressLocality', ':addressLocality'),
                                $expr->eq('postalAddress.addressRegion', ':addressRegion'),
                                $expr->eq('postalAddress.buildingName', ':buildingName'),
                                $expr->eq('postalAddress.floor', ':floor'),
                                $expr->eq('postalAddress.houseNumber', ':houseNumber'),
                                $expr->eq('postalAddress.postalCode', ':postalCode')
                            ),
                            $applicationRequestExpr
                        ));
                } else {
                    unset($queryParameters['statusActive']);

                    $qb->where($expr->andX(
                        $expr->orX(
                            $expr->eq('applicationRequest.status', ':statusCompleted'),
                            $expr->eq('applicationRequest.status', ':statusInProgress')
                        ),
                        $expr->eq('address.addressCountry', ':addressCountry'),
                        $expr->eq('address.addressLocality', ':addressLocality'),
                        $expr->eq('address.addressRegion', ':addressRegion'),
                        $expr->eq('address.buildingName', ':buildingName'),
                        $expr->eq('address.floor', ':floor'),
                        $expr->eq('address.houseNumber', ':houseNumber'),
                        $expr->eq('address.postalCode', ':postalCode')
                    ));
                }

                $qb->andWhere($expr->eq('applicationRequest.type', ':type'));

                if (null !== $object->getId()) {
                    $qb->andWhere($expr->neq('applicationRequest.id', ':id'));
                    $queryParameters['id'] = $object->getId();
                }

                $qb->setParameters($queryParameters);
                $existingApplicationRequests = $qb->getQuery()->getResult();

                if (\count($existingApplicationRequests) > 0) {
                    $this->context->buildViolation($constraint->duplicateApplicationRequest)
                        ->setParameter('{{ value }}', $this->formatValue($premiseAddress))
                        ->atPath("addresses[{$premiseAddressKey}]")
                        ->addViolation();
                }
            }
        }
    }
}
