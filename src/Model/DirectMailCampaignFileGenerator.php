<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Campaign;
use App\Entity\DirectMailCampaignSourceListItem;
use App\Entity\InternalDocument;
use App\Enum\DocumentType;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\SerializerInterface;

class DirectMailCampaignFileGenerator
{
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
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     * @param string                 $documentConverterHost
     */
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, string $documentConverterHost)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->baseUri = HttpUri::createFromString($documentConverterHost);
        $this->client = new GuzzleClient();
    }

    /**
     * Generates pdf for the source list item.
     *
     * @param Campaign                         $campaign
     * @param DirectMailCampaignSourceListItem $sourceListItem
     *
     * @return string
     */
    public function generatePdf(Campaign $campaign, DirectMailCampaignSourceListItem $sourceListItem)
    {
        if (null !== $sourceListItem->getPosition()) {
            $filename = $sourceListItem->getPosition();
        } else {
            $filename = $sourceListItem->getId();
        }

        $filePath = \sprintf('%s/%s.%s', \sys_get_temp_dir(), $filename, 'pdf');

        $sourceListItemSerializeOptions = [
            'context' => [
                'customer_account_read',
                'direct_mail_campaign_source_list_read',
                'lead_read',
            ],
        ];

        $data = [
            'template' => \json_decode($this->serializer->serialize($campaign->getTemplate(), 'jsonld')),
            'sourceListItem' => \json_decode($this->serializer->serialize($sourceListItem, 'jsonld', $sourceListItemSerializeOptions)),
        ];

        $modifier = new AppendSegment('pdf/campaign');
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
     * Generates zip archive and stores as an InternalDocument.
     *
     * @param array    $files
     * @param Campaign $campaign
     *
     * @return InternalDocument|null
     */
    public function generateInternalDocumentZip(array $files, Campaign $campaign)
    {
        $internalDocument = null;

        if (!empty($files)) {
            $filename = \sprintf('%s.%s', $campaign->getCampaignNumber(), 'zip');
            $zipPath = \sprintf('%s/%s', \sys_get_temp_dir(), $filename);

            $zip = new \ZipArchive();
            $zip->open($zipPath, \ZipArchive::CREATE);

            foreach ($files as $file) {
                $zip->addFile($file);
            }
            $zip->close();

            $internalDocument = new InternalDocument();
            $internalDocument->setContentFile(new UploadedFile($zipPath, $filename, 'application/zip'));
            $internalDocument->setName($filename);
            $internalDocument->setType(new DocumentType(DocumentType::DIRECT_MAIL_CAMPAIGN));

            $this->entityManager->persist($internalDocument);
        }

        return $internalDocument;
    }
}
