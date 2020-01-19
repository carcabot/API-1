<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\Entity\Note;
use App\Entity\QuantitativeValue;
use App\Entity\TariffRate;
use App\Enum\ContractType;
use App\Enum\ModuleType;
use App\Enum\NoteType;
use App\Enum\TariffRateStatus;
use Doctrine\ORM\EntityManagerInterface;
use iter;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class TariffRateController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $timezone
     */
    public function __construct(EntityManagerInterface $entityManager, string $timezone)
    {
        $this->entityManager = $entityManager;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function createAction(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);

        $requiredFields = [
            'available_from',
            'contract_types',
            'min_contract_term',
            'promotion_code',
            'promotion_name',
            'promotion_start_date',
        ];

        $params['promotion_status'] = 'NEW';

        $errors = [];
        foreach ($requiredFields as $requiredField) {
            if (empty($params[$requiredField])) {
                $errors[$requiredField] = 'This value is required.';
            }
        }

        if (\count($errors) > 0) {
            return new JsonResponse([
                'message' => 'Error while creating promotion code!',
                'data' => $errors,
            ], 400);
        }

        $existingTariffRate = $this->entityManager->getRepository(TariffRate::class)->findOneBy([
            'tariffRateNumber' => $params['promotion_code'],
            'isBasedOn' => null,
        ]);

        if (null !== $existingTariffRate) {
            return new JsonResponse([
                'message' => 'Error while creating promotion code!',
                'data' => [
                    'promotion_code' => 'Promotion code has already been used.',
                ],
            ], 400);
        }

        $tariffRate = new TariffRate();
        $tariffRate->setStatus(new TariffRateStatus(TariffRateStatus::NEW));

        $validationErrors = $this->updateTariffRate($tariffRate, $params);

        if (\count($validationErrors) > 0) {
            return new JsonResponse([
                'message' => 'Error while creating promotion code!',
                'data' => $validationErrors,
            ], 400);
        }

        $this->entityManager->persist($tariffRate);
        $this->entityManager->flush();

        if (null !== $tariffRate->getDateCreated()) {
            $params['created_at'] = $tariffRate->getDateCreated()->format(\DateTime::ISO8601);
        }

        return new JsonResponse([
            'message' => 'Promotion created successfully.',
            'data' => $params,
        ], 200);
    }

    /**
     * @param ServerRequestInterface $request
     * @param string                 $tariffRateNumber
     *
     * @return HttpResponseInterface
     */
    public function updateAction(ServerRequestInterface $request, string $tariffRateNumber): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);

        $tariffRate = $this->entityManager->getRepository(TariffRate::class)->findOneBy([
            'tariffRateNumber' => $tariffRateNumber,
            'isBasedOn' => null,
        ]);

        if (null === $tariffRate) {
            return new JsonResponse([
                'message' => \sprintf('Promotion code %s not found', $tariffRateNumber),
            ], 404);
        }

        $validationErrors = $this->updateTariffRate($tariffRate, $params);

        if (\count($validationErrors) > 0) {
            return new JsonResponse([
                'message' => 'Error while updating promotion code!',
                'data' => $validationErrors,
            ], 400);
        }

        $this->entityManager->persist($tariffRate);
        $this->entityManager->flush();

        if (null !== $tariffRate->getDateModified()) {
            $params['updated_at'] = $tariffRate->getDateModified()->format(\DateTime::ISO8601);
        }

        return new JsonResponse([
            'message' => 'Promotion updated successfully.',
            'data' => $params,
        ], 200);
    }

    private function updateTariffRate(TariffRate $tariffRate, array $data)
    {
        $errors = [];

        if (!empty($data['charge_description'])) {
            $tariffRate->setChargeDescription($data['charge_description']);
        }

        if (!empty($data['contract_types']) && \count($data['contract_types']) > 0) {
            $tariffRate->clearContractTypes();

            foreach ($data['contract_types'] as $contractType) {
                $tariffRate->addContractType($this->mapContractType($contractType)->getValue());
            }
        }

        if (isset($data['promotion_customized'])) {
            $tariffRate->setCustomizable((bool) $data['promotion_customized']);
        }

        if (!empty($data['promotion_desc'])) {
            $tariffRate->setDescription($data['promotion_desc']);
        }

        $usedInList = [
            ModuleType::PARTNERSHIP_PORTAL,
            ModuleType::QUOTATION_CONTRACT,
        ];

        if (isset($data['promotion_internal_only'])) {
            $tariffRate->setInternalUseOnly((bool) $data['promotion_internal_only']);
        }

        if (true !== $tariffRate->isInternalUseOnly()) {
            $usedInList[] = ModuleType::CAMPAIGN;
            $usedInList[] = ModuleType::CLIENT_HOMEPAGE;
        }

        if (!empty($data['promotion_limit']) && true === \is_numeric($data['promotion_limit'])) {
            $tariffRate->setInventoryLevel(new QuantitativeValue(null, null, (string) $data['promotion_limit']));
        }

        if (!empty($data['min_contract_term']) && true === \is_numeric($data['min_contract_term'])) {
            $tariffRate->setMinContractTerm(new QuantitativeValue((string) $data['min_contract_term'], null, null, 'MON'));
        }

        if (!empty($data['promotion_name'])) {
            $tariffRate->setName($data['promotion_name']);
        }

        /*if (!empty($data['promotion_rejection_note'])) {
            $note = iter\search(function ($note) {
                if (NoteType::REJECT_REASON === $note->getType()->getValue()) {
                    return $note;
                }
            }, $tariffRate->getNotes());

            if (null === $note) {
                $note = new Note();
                $note->setType(new NoteType(NoteType::REJECT_REASON));
            }

            // probably too much, but idgaf
            if ($data['promotion_rejection_note'] !== $note->getText()) {
                $note->setText($data['promotion_rejection_note']);

                $this->entityManager->persist($note);
                $tariffRate->addNote($note);
            }
        }*/

        if (!empty($data['available_from'])) {
            try {
                $date = new \DateTime($data['available_from'], $this->timezone);
                $date->setTimezone(new \DateTimeZone('UTC'));

                $tariffRate->setStartDate($date);
            } catch (\Exception $e) {
                $errors['available_from'] = 'Invalid date format.';
            }
        }

        /*if (!empty($data['promotion_status'])) {
            $tariffRate->setStatus($this->getStatus($data['promotion_status']));
        }*/

        if (!empty($data['promotion_code'])) {
            $tariffRate->setTariffRateNumber($data['promotion_code']);
        }

        if (!empty($data['promotion_start_date'])) {
            try {
                $date = new \DateTime($data['promotion_start_date'], $this->timezone);
                $date->setTimezone(new \DateTimeZone('UTC'));

                $tariffRate->setValidFrom($date);
            } catch (\Exception $e) {
                $errors['promotion_start_date'] = 'Invalid date format.';
            }
        }

        if (!empty($data['promotion_end_date'])) {
            try {
                $date = new \DateTime($data['promotion_end_date'], $this->timezone);
                $date->setTimezone(new \DateTimeZone('UTC'));

                $tariffRate->setValidThrough($date);
            } catch (\Exception $e) {
                $errors['promotion_end_date'] = 'Invalid date format.';
            }
        }

        if (!empty($data['promotion_remark'])) {
            $tariffRate->setRemark($data['promotion_remark']);
        }

        if (null === $tariffRate->getId()) {
            foreach ($usedInList as $usedIn) {
                $tariffRate->addUsedIn($usedIn);
            }
        }

        if (!empty($data['application_for_use']) && \count($data['application_for_use']) > 0) {
            $tariffRate->clearUsedIn();

            foreach ($data['application_for_use'] as $applicationForUse) {
                if (!empty($applicationForUse)) {
                    $tariffRate->addUsedIn($this->mapUsedIn($applicationForUse)->getValue());
                }
            }
        }

        return $errors;
    }

    private function getStatus(string $status)
    {
        $statusMap = [
            'ACTIVE' => new TariffRateStatus(TariffRateStatus::ACTIVE),
            'DELETED' => new TariffRateStatus(TariffRateStatus::DELETED),
            'ENDED' => new TariffRateStatus(TariffRateStatus::ENDED),
            'IN_PROGRESS' => new TariffRateStatus(TariffRateStatus::IN_PROGRESS),
            'NEW' => new TariffRateStatus(TariffRateStatus::NEW),
        ];

        return $statusMap[$status];
    }

    private function mapContractType(string $contractType)
    {
        $typesMap = [
            'RESIDENTIAL' => new ContractType(ContractType::RESIDENTIAL),
            'COMMERCIAL' => new ContractType(ContractType::COMMERCIAL),
        ];

        return $typesMap[$contractType];
    }

    private function mapUsedIn(string $applicationForUse)
    {
        if (false !== \stripos($applicationForUse, 'campaigns')) {
            return new ModuleType(ModuleType::CAMPAIGN);
        } elseif (false !== \stripos($applicationForUse, 'homepage')) {
            return new ModuleType(ModuleType::CLIENT_HOMEPAGE);
        } elseif (false !== \stripos($applicationForUse, 'partnership')) {
            return new ModuleType(ModuleType::PARTNERSHIP_PORTAL);
        } elseif (false !== \stripos($applicationForUse, 'quotation')) {
            return new ModuleType(ModuleType::QUOTATION_CONTRACT);
        }

        return '';
    }
}
