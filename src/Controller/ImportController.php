<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\LeadImporter;
use App\Model\SourceListImporter;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Diactoros\Response\JsonResponse;

class ImportController
{
    /**
     * @var LeadImporter
     */
    private $leadImporter;

    /**
     * @var SourceListImporter
     */
    private $sourceListImporter;

    /**
     * @param LeadImporter       $leadImporter
     * @param SourceListImporter $sourceListImporter
     */
    public function __construct(LeadImporter $leadImporter, SourceListImporter $sourceListImporter)
    {
        $this->leadImporter = $leadImporter;
        $this->sourceListImporter = $sourceListImporter;
    }

    /**
     * @Route("/import/leads", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function importLead(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = $request->getParsedBody();
        $file = $request->getUploadedFiles()['file'] ?? null;
        $testRun = true;

        if (!empty($params['testRun']) && 'false' === $params['testRun']) {
            $testRun = false;
        }

        $leads = [];

        if (null !== $file) {
            $leads = $this->leadImporter->importFromFile($file, $testRun);
        }

        return new JsonResponse($leads, 200);
    }

    /**
     * @Route("/import/source_list", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function importSourceList(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = $request->getParsedBody();
        $file = $request->getUploadedFiles()['file'] ?? null;
        $testRun = true;

        if (!empty($params['testRun']) && 'false' === $params['testRun']) {
            $testRun = false;
        }

        $leads = [];

        if (null !== $file) {
            $leads = $this->sourceListImporter->importFromFile($file, $testRun);
        }

        return new JsonResponse($leads, 200);
    }

    public function importOfferCatalog(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = $request->getParsedBody();
        $file = $request->getUploadedFiles()['file'] ?? null;
        $testRun = true;

        if (!empty($params['testRun']) && 'false' === $params['testRun']) {
            $testRun = false;
        }

        $offerListItems = [];

        if (null !== $file) {
            $$offerListItems = $this->leadImporter->importFromFile($file, $testRun);
        }

        return new JsonResponse($offerListItems, 200);
    }
}
