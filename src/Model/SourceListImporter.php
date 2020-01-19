<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use League\Tactician\CommandBus;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SourceListImporter
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var DenormalizerInterface
     */
    private $denormalizer;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var string
     */
    private $documentConverterHost;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     * @param DenormalizerInterface  $denormalizer
     * @param ValidatorInterface     $validator
     * @param string                 $documentConverterHost
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, DenormalizerInterface $denormalizer, ValidatorInterface $validator, string $documentConverterHost)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->denormalizer = $denormalizer;
        $this->validator = $validator;
        $this->documentConverterHost = $documentConverterHost;
    }

    /**
     * @param UploadedFileInterface $file
     * @param bool                  $testRun
     *
     * @return array
     */
    public function importFromFile(UploadedFileInterface $file, bool $testRun = true)
    {
        $SourceListData = [];
        $SourceLists = [];

        if ($file->getSize() > 0) {
            $client = new GuzzleClient();
            $baseDocumentConverterUri = HttpUri::createFromString($this->documentConverterHost);
            $modifier = new AppendSegment('spread_sheet');
            $documentConverterUri = $modifier->process($baseDocumentConverterUri);

            $multipartContent = [
                'headers' => [
                    'User-Agent' => 'U-Centric API',
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'filename' => \uniqid().'.xml',
                        'contents' => $file->getStream(),
                    ],
                ],
            ];
            $uploadResponse = $client->request('POST', $documentConverterUri, $multipartContent);
            $SourceListData = \json_decode((string) $uploadResponse->getBody(), true);

            if (200 === $uploadResponse->getStatusCode()) {
                return $SourceListData;
            }
        }
    }
}
