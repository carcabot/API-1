<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\Entity\Promotion;
use App\Entity\PromotionCategory;
use App\Entity\QuantitativeValue;
use App\Enum\PromotionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class PromotionController
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
        $data = \json_decode($request->getBody()->getContents(), true);

        $requiredFields = [
            'discount_code',
            'discount_name',
            'discount_type',
            'discount_value',
            'discount_start_date',
            'discount_end_date',
        ];

        $errors = [];

        foreach ($requiredFields as $requiredField) {
            if (empty($data[$requiredField])) {
                $errors[$requiredField] = 'This value is required.';
            }
        }

        if (\count($errors) > 0) {
            return new JsonResponse([
                'message' => 'Error while creating discount!',
                'data' => $errors,
            ], 400);
        }

        $existingPromotion = $this->entityManager->getRepository(Promotion::class)->findOneBy([
            'promotionNumber' => $data['discount_code'],
            'isBasedOn' => null,
        ]);

        if (null !== $existingPromotion) {
            return new JsonResponse([
                'message' => 'Error while creating discount!',
                'data' => [
                    'discount_code' => 'Discount code has already been used.',
                ],
            ], 400);
        }

        $promotion = new Promotion();
        $promotion->setStatus(new PromotionStatus(PromotionStatus::NEW));

        if (!empty($data['discount_code'])) {
            $promotion->setPromotionNumber($data['discount_code']);
        }

        $promotionCategory = null;
        $category = $this->entityManager->getRepository(PromotionCategory::class)->findOneBy(['name' => $data['discount_type']]);
        if (null === $category) {
            $promotionCategory = new PromotionCategory();
            $promotionCategory->setName('Bill Rebate');
        } else {
            $promotionCategory = $category;
        }
        $promotion->setCategory($promotionCategory);

        $validationErrors = $this->updatePromotion($promotion, $data);

        if (\count($validationErrors) > 0) {
            return new JsonResponse([
                'message' => 'Error while creating discount code!',
                'data' => $validationErrors,
            ], 400);
        }

        $this->entityManager->persist($promotion);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Promotion created successfully.',
            'data' => $data,
        ], 200);
    }

    /**
     * @param ServerRequestInterface $request
     * @param string                 $promotionNumber
     *
     * @return HttpResponseInterface
     */
    public function updateAction(ServerRequestInterface $request, string $promotionNumber): HttpResponseInterface
    {
        $data = \json_decode($request->getBody()->getContents(), true);

        $promotion = $this->entityManager->getRepository(Promotion::class)->findOneBy(['promotionNumber' => $promotionNumber, 'isBasedOn' => null]);

        if (null === $promotion) {
            return new JsonResponse([
                'message' => \sprintf('Discount code %s not found.', $promotionNumber),
                ], 404);
        }
        $validationErrors = $this->updatePromotion($promotion, $data);

        if (\count($validationErrors) > 0) {
            return new JsonResponse([
                'message' => 'Error while creating discount code!',
                'data' => $validationErrors,
            ], 400);
        }

        $this->entityManager->persist($promotion);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Discount updated successfully.',
            'data' => $data,
        ], 200);
    }

    public function updatePromotion(Promotion $promotion, array $data)
    {
        $errors = [];

        if (!empty($data['discount_name'])) {
            $promotion->setName($data['discount_name']);
        }

        if (!empty($data['discount_type']) && 'Percentage' === $data['discount_type']) {
            if (\array_key_exists('max_discount_value', $data)) {
                if (!empty($data['discount_value']) && !empty($data['max_discount_value'])) {
                    $promotion->setAmount(new QuantitativeValue((string) $data['discount_value'], null, (string) $data['max_discount_value'], '%'));
                }
            } elseif (!empty($data['discount_value'])) {
                $promotion->setAmount(new QuantitativeValue((string) $data['discount_value'], null, null, '%'));
            }
        } elseif (!empty($data['discount_type']) && 'Fixed Amount' === $data['discount_type']) {
            if (!empty($data['discount_value'])) {
                $promotion->setCurrency('SGD');
                $promotion->setAmount(new QuantitativeValue((string) $data['discount_value'], null, null, null));
            }
        }

        if (!empty($data['discount_start_date'])) {
            try {
                $date = new \DateTime($data['discount_start_date'], $this->timezone);
                $date->setTimezone(new \DateTimeZone('UTC'));
                $date->format('YYYY-MM-DD');

                $promotion->setValidFrom($date);
            } catch (\Exception $e) {
                $errors['discount_start_date'] = 'Invalid date format.';
            }
        }

        if (!empty($data['discount_end_date'])) {
            try {
                $date = new \DateTime($data['discount_end_date'], $this->timezone);
                $date->setTimezone(new \DateTimeZone('UTC'));
                $date->format('YYYY-MM-DD');

                $promotion->setValidThrough($date);
            } catch (\Exception $e) {
                $errors['discount_end_date'] = 'Invalid date format.';
            }
        }

        if (!empty($data['number_of_discount'])) {
            $promotion->setRecurringDuration(new QuantitativeValue((string) $data['number_of_discount'], null, null, 'MON'));
        }

        return $errors;
    }
}
