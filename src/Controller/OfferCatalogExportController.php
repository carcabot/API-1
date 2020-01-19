<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\DocumentType;
use App\Model\ReportGenerator;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OfferCatalogExportController
{
    /**
     * @var ReportGenerator
     */
    private $reportGenerator;

    /**
     * @param ReportGenerator $reportGenerator
     */
    public function __construct(ReportGenerator $reportGenerator)
    {
        $this->reportGenerator = $reportGenerator;
    }

    public function __invoke(ServerRequestInterface $request)
    {
        try {
            $report = null;
            $id = $request->getAttribute('id');

            if (null !== $id) {
                $exportData = $this->reportGenerator->createOfferCatalogReport((int) $id);

                if (null !== $exportData && !empty($exportData)) {
                    $report = $this->reportGenerator->convertDataToInternalDocument([$exportData], DocumentType::OFFER_CATALOG);
                }
            } else {
                return new BadRequestHttpException('Not Valid Id');
            }

            return $report;
        } catch (\Exception $ex) {
            return new BadRequestHttpException($ex->getMessage());
        }
    }
}
