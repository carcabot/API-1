<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\ApplicationRequest;
use App\Entity\DigitalDocument;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\SerializerInterface;

class AuthorizationLetterFileGenerator
{
    const FILENAME = 'Authorization_Letter';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     * @param string                 $documentConverterHost
     * @param string                 $timezone
     */
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, string $documentConverterHost, string $timezone)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->baseUri = HttpUri::createFromString($documentConverterHost);
        $this->client = new GuzzleClient();
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * Generates authorization letter pdf for the application request.
     *
     * @param ApplicationRequest $applicationRequest
     *
     * @return string
     */
    public function generatePdf(ApplicationRequest $applicationRequest)
    {
        $now = new \DateTime();
        $filePath = \sprintf('%s/%s_%s.%s', \sys_get_temp_dir(), self::FILENAME, $now->setTimezone($this->timezone)->format('YmdHis'), 'pdf');

        $applicationRequestSerializeOptions = [
            'groups' => [
                'application_request_read',
                'authorization_letter_generate',
                'person_read',
                'postal_address_read',
            ],
        ];

        $data = [
            'applicationRequest' => \json_decode($this->serializer->serialize($applicationRequest, 'jsonld', $applicationRequestSerializeOptions)),
        ];

        $modifier = new AppendSegment('pdf/authorization_letter');
        $uri = $modifier->process($this->baseUri);

        $headers = [
            'User-Agent' => 'U-Centric API',
            'Content-Type' => 'application/json',
        ];

        $request = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($data));
        $response = $this->client->send($request, ['save_to' => $filePath]);

        return $filePath;
    }

    /**
     * Converts authorization letter pdf to Digital Document.
     *
     * @param string $filePath
     */
    public function convertFileToDigitalDocument(string $filePath)
    {
        $tempFile = new \SplFileObject($filePath, 'r');
        $tempFilePath = $tempFile->getRealPath();
        $tempFileName = $tempFile->getFilename();

        $contentFile = new UploadedFile(
            $tempFilePath,
            $tempFileName,
            MimeTypeGuesser::getInstance()->guess($tempFilePath),
            null,
            true
        );

        // close the temporary file
        $tempFile = null;

        $authorizationLetter = new DigitalDocument();
        $authorizationLetter->setContentFile($contentFile);
        $authorizationLetter->setName($tempFileName);
        $this->entityManager->persist($authorizationLetter);
        $this->entityManager->flush();

        return $authorizationLetter;
    }
}
