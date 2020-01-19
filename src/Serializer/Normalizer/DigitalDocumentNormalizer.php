<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\Entity\DigitalDocument;
use App\Enum\AuthorizationRole;
use App\Service\AuthenticationHelper;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use League\Flysystem\Util\MimeType as MimeTypeUtil;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory as UploaderMappingFactory;
use Vich\UploaderBundle\Storage\StorageInterface as UploaderStorageInterface;

class DigitalDocumentNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait {
        setSerializer as baseSetSerializer;
    }

    /**
     * @var AuthenticationHelper
     */
    private $authHelper;

    /**
     * @var MountManager
     */
    private $mountManager;

    /**
     * @var UploaderStorageInterface
     */
    private $uploaderStorage;

    /**
     * @var UploaderMappingFactory
     */
    private $uploaderMappingFactory;

    /**
     * @var NormalizerInterface
     */
    private $decorated;

    /**
     * @param AuthenticationHelper     $authHelper
     * @param MountManager             $mountManager
     * @param UploaderStorageInterface $uploaderStorage
     * @param UploaderMappingFactory   $uploaderMappingFactory
     * @param NormalizerInterface      $decorated
     */
    public function __construct(AuthenticationHelper $authHelper, MountManager $mountManager, UploaderStorageInterface $uploaderStorage, UploaderMappingFactory $uploaderMappingFactory, NormalizerInterface $decorated)
    {
        $this->authHelper = $authHelper;
        $this->mountManager = $mountManager;
        $this->uploaderStorage = $uploaderStorage;
        $this->uploaderMappingFactory = $uploaderMappingFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof DigitalDocument) {
            return $this->decorated->normalize($object, $format, $context);
        }

        /** @var DigitalDocument $digitalDocument */
        $digitalDocument = $object;

        $data = $this->decorated->normalize($object, $format, $context);

        if (!\is_array($data)) {
            return $data;
        }

        if (null === $data['url']) {
            $filesystem = $this->mountManager->getFilesystem('file_fs');
            if ($filesystem instanceof Filesystem && ($adapter = $filesystem->getAdapter()) instanceof AwsS3Adapter) {
                try {
                    $path = $this->uploaderStorage->resolvePath($digitalDocument, 'contentFile');

                    $options = [
                        'Bucket' => $adapter->getBucket(),
                        'Key' => $adapter->applyPathPrefix($path),
                        'ResponseContentDisposition' => 'attachment; filename="'.$data['name'].'"',
                        'ResponseContentType' => MimeTypeUtil::detectByFilename($data['name']),
                    ];

                    $s3Client = $adapter->getClient();
                    $command = $s3Client->getCommand('getObject', $options);
                    $request = $s3Client->createPresignedRequest($command, '+24 hours');
                    $data['url'] = (string) $request->getUri();
                } catch (\Exception $e) {
                    // @todo??
                }
            } elseif (null !== $digitalDocument->getContentPath()) {
                $data['url'] = $this->uploaderStorage->resolveUri($digitalDocument, 'contentFile');
            }
        }

        $homepageUser = null;
        if (null !== $this->authHelper->getImpersonatorUser() &&
            \in_array(AuthorizationRole::ROLE_HOMEPAGE, $this->authHelper->getImpersonatorUser()->getRoles(), true)
        ) {
            $homepageUser = $this->authHelper->getAuthenticatedUser();
        } elseif ($this->authHelper->hasRole(AuthorizationRole::ROLE_HOMEPAGE)) {
            $homepageUser = $this->authHelper->getAuthenticatedUser();
        }

        // ROLE_HOMEPAGE special case, when it is the creator, not allowed to see url.
        if (null !== $homepageUser && $homepageUser->getId() === $digitalDocument->getCreator()) {
            $data['url'] = null;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $this->decorated->supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (DigitalDocument::class !== $class) {
            return $this->decorated->denormalize($data, $class, $format, $context);
        }

        $contentFile = null;

        if (isset($data['contentFile'])) {
            $tempFile = new \SplFileObject($data['contentFile'], 'r');
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

            unset($data['contentFile']);
        }

        $object = $this->decorated->denormalize($data, $class, $format, $context);

        /** @var DigitalDocument $digitalDocument */
        $digitalDocument = $object;

        if (null !== $contentFile) {
            $digitalDocument->setContentFile($contentFile);
            $digitalDocument->setUrl(null);
        } elseif (isset($data['url'])) {
            $digitalDocument->setContentPath(null);

            $uploaderMapping = $this->uploaderMappingFactory->fromField($digitalDocument, 'contentFile');

            if (null === $uploaderMapping) {
                throw new \UnexpectedValueException('Uploader mapping not found');
            }

            $fullPrefix = $uploaderMapping->getUriPrefix();
            if (!empty($uploaderMapping->getUploadDir($digitalDocument))) {
                $fullPrefix .= '/'.$uploaderMapping->getUploadDir($digitalDocument);
            }
            $fullPrefix .= '/';

            $fullPrefixLength = \strlen($fullPrefix);

            if (\substr($data['url'], 0, $fullPrefixLength) === $fullPrefix) {
                $digitalDocument->setContentPath(\substr($data['url'], $fullPrefixLength));
                $digitalDocument->setUrl(null);
            }
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->baseSetSerializer($serializer);

        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }
}
