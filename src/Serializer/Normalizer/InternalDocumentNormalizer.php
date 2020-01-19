<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\Entity\InternalDocument;
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

class InternalDocumentNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait {
        setSerializer as baseSetSerializer;
    }

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
     * @param MountManager             $mountManager
     * @param UploaderStorageInterface $uploaderStorage
     * @param UploaderMappingFactory   $uploaderMappingFactory
     * @param NormalizerInterface      $decorated
     */
    public function __construct(MountManager $mountManager, UploaderStorageInterface $uploaderStorage, UploaderMappingFactory $uploaderMappingFactory, NormalizerInterface $decorated)
    {
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
        if (!$object instanceof InternalDocument) {
            return $this->decorated->normalize($object, $format, $context);
        }

        /** @var InternalDocument $internalDocument */
        $internalDocument = $object;

        $data = $this->decorated->normalize($object, $format, $context);

        if (!\is_array($data)) {
            return $data;
        }

        if (null === $data['url']) {
            $filesystem = $this->mountManager->getFilesystem('internal_file_fs');
            if ($filesystem instanceof Filesystem && ($adapter = $filesystem->getAdapter()) instanceof AwsS3Adapter) {
                try {
                    $path = $this->uploaderStorage->resolvePath($internalDocument, 'contentFile');

                    $options = [
                        'Bucket' => $adapter->getBucket(),
                        'Key' => $adapter->applyPathPrefix($path),
                        'ResponseContentDisposition' => 'attachment; filename="'.$data['name'].'"',
                        'ResponseContentType' => MimeTypeUtil::detectByFilename($data['name']),
                    ];

                    $s3Client = $adapter->getClient();
                    $command = $s3Client->getCommand('getObject', $options);
                    $request = $s3Client->createPresignedRequest($command, '+1 hour');
                    $data['url'] = (string) $request->getUri();
                } catch (\Exception $e) {
                    // @todo??
                }
            } else {
                $data['url'] = $this->uploaderStorage->resolveUri($internalDocument, 'contentFile');
            }
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
        if (InternalDocument::class !== $class) {
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

        /** @var InternalDocument $internalDocument */
        $internalDocument = $object;

        if (null !== $contentFile) {
            $internalDocument->setContentFile($contentFile);
            $internalDocument->setUrl(null);
        } elseif (isset($data['url'])) {
            $internalDocument->setContentPath(null);

            $uploaderMapping = $this->uploaderMappingFactory->fromField($internalDocument, 'contentFile');

            if (null === $uploaderMapping) {
                throw new \UnexpectedValueException('Uploader mapping not found');
            }

            $fullPrefix = $uploaderMapping->getUriPrefix();
            if (!empty($uploaderMapping->getUploadDir($internalDocument))) {
                $fullPrefix .= '/'.$uploaderMapping->getUploadDir($internalDocument);
            }
            $fullPrefix .= '/';

            $fullPrefixLength = \strlen($fullPrefix);

            if (\substr($data['url'], 0, $fullPrefixLength) === $fullPrefix) {
                $internalDocument->setContentPath(\substr($data['url'], $fullPrefixLength));
                $internalDocument->setUrl(null);
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
