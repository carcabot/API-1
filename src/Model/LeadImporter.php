<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Domain\Command\Lead\UpdateLeadNumber;
use App\Entity\Lead;
use App\Enum\LeadStatus;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use League\Tactician\CommandBus;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LeadImporter
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
        $leadData = [];
        $leads = [];

        if ($file->getSize() > 0) {
            $client = new GuzzleClient();
            $baseDocumentConverterUri = HttpUri::createFromString($this->documentConverterHost);
            $modifier = new AppendSegment('csv');
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
            $leadData = \json_decode((string) $uploadResponse->getBody(), true);

            if (200 === $uploadResponse->getStatusCode()) {
                foreach ($leadData as $leadDatum) {
                    if (isset($leadDatum['errors'])) {
                        $leads[] = $leadDatum;
                        continue;
                    }

                    $leadDatum = $this->removeEmptyValues($leadDatum, function ($value) { return empty($value); });
                    $lead = $this->denormalizer->denormalize($leadDatum, Lead::class, 'jsonld', ['lead_import_write']);

                    if (!$lead instanceof Lead) {
                        $leads[] = 'Unknown type.';
                        continue;
                    }

                    $lead->setStatus(new LeadStatus(LeadStatus::DRAFT));
                    $errors = $this->validator->validate($lead);

                    if (\count($errors) > 0) {
                        $validationErrors = [];

                        foreach ($errors as $error) {
                            $validationErrors[$error->getPropertyPath()] = [$error->getMessage()];
                        }

                        $leads[] = ['errors' => $validationErrors];
                    } else {
                        if (false === $testRun) {
                            $this->entityManager->getConnection()->beginTransaction();
                            $this->entityManager->getConnection()->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;');
                            $this->commandBus->handle(new UpdateLeadNumber($lead));
                            $this->entityManager->persist($lead);
                            $this->entityManager->flush();
                            $this->entityManager->getConnection()->commit();

                            $leads[] = [
                                '@id' => $this->iriConverter->getIriFromItem($lead),
                                'leadNumber' => $lead->getLeadNumber(),
                            ];
                        } else {
                            $leads[] = 'No errors';
                        }
                    }
                }
            }
        }

        return $leads;
    }

    private function removeEmptyValues(array $array, callable $callback)
    {
        foreach ($array as $k => $v) {
            if (\is_array($v)) {
                $array[$k] = $this->removeEmptyValues($v, $callback);
            } else {
                if ($callback($v)) {
                    unset($array[$k]);
                }
            }
        }

        return $array;
    }
}
