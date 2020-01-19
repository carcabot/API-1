<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\DigitalDocument;
use App\Entity\Quotation;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\SerializerInterface;

class QuotationFileGenerator
{
    const FILENAME = 'Quotation_attachment';

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
     * @var string
     */
    private $profile;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     * @param string                 $documentConverterHost
     * @param string                 $timezone
     * @param string                 $profile
     */
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, string $documentConverterHost, string $timezone, string $profile)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->baseUri = HttpUri::createFromString($documentConverterHost);
        $this->client = new GuzzleClient();
        $this->timezone = new \DateTimeZone($timezone);
        $this->profile = $profile;
    }

    /**
     * Generates authorization letter pdf for the application request.
     *
     * @param Quotation $quotation
     *
     * @return string
     */
    public function generatePdf(Quotation $quotation)
    {
        $now = new \DateTime();
        $filePath = \sprintf('%s/%s_%s.%s', \sys_get_temp_dir(), self::FILENAME, $now->setTimezone($this->timezone)->format('YmdHis'), 'pdf');

        $quotationSerializeOptions = [
            'groups' => [
                'corporation_read',
                'person_read',
                'quotation_file_generate',
                'quotation_read',
            ],
        ];

        $data = [
            'quotation' => \json_decode($this->serializer->serialize($quotation, 'jsonld', $quotationSerializeOptions)),
            'profile' => $this->profile,
        ];

        $modifier = new AppendSegment('pdf/quotation');
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

        $quotationFile = new DigitalDocument();
        $quotationFile->setContentFile($contentFile);
        $quotationFile->setName($tempFileName);
        $this->entityManager->persist($quotationFile);
        $this->entityManager->flush();

        return $quotationFile;
    }
}
