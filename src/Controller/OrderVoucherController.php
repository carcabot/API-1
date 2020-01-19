<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\RedemptionOrderVoucherGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OrderVoucherController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RedemptionOrderVoucherGenerator
     */
    private $redemptionOrderVoucherGenerator;

    /**
     * @param EntityManagerInterface          $entityManager
     * @param RedemptionOrderVoucherGenerator $redemptionOrderVoucherGenerator
     */
    public function __construct(EntityManagerInterface $entityManager, RedemptionOrderVoucherGenerator $redemptionOrderVoucherGenerator)
    {
        $this->entityManager = $entityManager;
        $this->redemptionOrderVoucherGenerator = $redemptionOrderVoucherGenerator;
    }

    /**
     * @param ServerRequestInterface $request
     */
    public function __invoke(ServerRequestInterface $request)
    {
        $param = $request->getQueryParams();
        $profile = $param['profile'];
        $attributes = $request->getAttributes();
        $order = $attributes['data'];

        if (null !== $order) {
            try {
                $filePath = $this->redemptionOrderVoucherGenerator->generateRedemptionOrderVoucher($order, $profile);
                $tempFile = new \SplFileObject($filePath, 'r');
                $fileName = $tempFile->getFilename();
                $response = new BinaryFileResponse($filePath);
                $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $fileName
                );

                return $response;
            } catch (\Exception $ex) {
                return new BadRequestHttpException($ex->getMessage());
            }
        }
    }
}
