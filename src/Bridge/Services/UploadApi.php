<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Entity\BridgeUser;
use App\Entity\DigitalDocument;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use League\Flysystem\MountManager;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;

final class UploadApi
{
    /**
     * @var string
     */
    private $bridgeApiUrl;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MountManager
     */
    private $mountManager;

    /**
     * @var string|null
     */
    private $authToken;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @param string                 $bridgeApiUrl
     * @param EntityManagerInterface $entityManager
     * @param MountManager           $mountManager
     */
    public function __construct(string $bridgeApiUrl, EntityManagerInterface $entityManager, MountManager $mountManager)
    {
        $this->bridgeApiUrl = $bridgeApiUrl;
        $this->entityManager = $entityManager;
        $this->mountManager = $mountManager;
        $this->client = new GuzzleClient();
        $this->baseUri = HttpUri::createFromString($this->bridgeApiUrl);

        $qb = $this->entityManager->getRepository(BridgeUser::class)->createQueryBuilder('bu');

        $bridgeUser = $qb->select('bu')
            ->where($qb->expr()->isNotNull('bu.authToken'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null !== $bridgeUser) {
            $this->authToken = $bridgeUser->getAuthToken();
        } else {
            $this->authToken = null;
        }
    }

    /**
     * Upload file to specified path.
     *
     * @param DigitalDocument $digitalDocument
     * @param string          $path
     *
     * @return array|null
     */
    public function uploadFile(DigitalDocument $digitalDocument, string $path)
    {
        $filesystem = $this->mountManager->getFilesystem('file_fs');
        $attachment = null;

        if (null !== $this->authToken && null !== $digitalDocument->getContentPath()) {
            $modifier = new AppendSegment($path);
            $uri = $modifier->process($this->baseUri);

            $content = $filesystem->read($digitalDocument->getContentPath());

            $multipartContent = [
                'headers' => [
                    'User-Agent' => 'U-Centric API',
                    'x-access-token' => $this->authToken,
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'filename' => $digitalDocument->getName(),
                        'contents' => $content,
                    ],
                ],
            ];

            if (false !== $content) {
                $uploadResponse = $this->client->request('POST', $uri, $multipartContent);
                $uploadResult = \json_decode((string) $uploadResponse->getBody(), true);

                if (200 === $uploadResult['status'] && 1 === $uploadResult['flag']) {
                    $attachment = [
                        'attached' => $uploadResult['data']['name'],
                        'desc' => \preg_replace('/\.[^\/.]+$/', '', $uploadResult['data']['name']),
                    ];
                }
            }
        }

        return $attachment;
    }
}
