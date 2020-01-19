<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AwsS3FileUploadHelper;
use App\Service\SGLocateApi;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

// @todo must be an authenticated user!

/**
 * @Route("/services")
 */
class ServiceController
{
    /**
     * @var SGLocateApi
     */
    private $sgLocateApi;

    /**
     * @var AwsS3FileUploadHelper
     */
    private $s3FileUploadHelper;

    /**
     * @param SGLocateApi           $sgLocateApi
     * @param AwsS3FileUploadHelper $s3FileUploadHelper
     */
    public function __construct(SGLocateApi $sgLocateApi, AwsS3FileUploadHelper $s3FileUploadHelper)
    {
        $this->sgLocateApi = $sgLocateApi;
        $this->s3FileUploadHelper = $s3FileUploadHelper;
    }

    /**
     * @Route("/postal_addresses", methods={"GET"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function postalAddressAction(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = $request->getQueryParams() ?? [];

        try {
            if (isset($params['postalCode'])) {
                return $this->sgLocateApi->searchByPostalCode($params['postalCode']);
            }
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return new EmptyResponse(400);
    }

    /**
     * @Route("/upload_file", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function uploadFile(ServerRequestInterface $request): HttpResponseInterface
    {
        try {
            $file = $request->getUploadedFiles()['image'] ?? null;

            $randomFileName = \bin2hex(\random_bytes(22));
            $fileExt = \explode('/', $file->getClientMediaType())[1];
            $newFilename = "$randomFileName.$fileExt";

            $res = $this->s3FileUploadHelper->upload($newFilename, $file->getStream(), ['contentType' => $file->getClientMediaType()]);

            return new JsonResponse(['url' => $res]);
        } catch (\Exception $ex) {
            return new JsonResponse(['error' => $ex->getMessage()], 500);
        }
    }
}
