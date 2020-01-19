<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\DigitalDocument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class DigitalDocumentContentListener
{
    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string[]
     */
    private $tempFilePaths = [];

    /**
     * @param string     $tempDir
     * @param Filesystem $filesystem
     */
    public function __construct(string $tempDir, Filesystem $filesystem)
    {
        $this->tempDir = \realpath($tempDir);
        $this->filesystem = $filesystem;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true) || false === \strpos($request->getContentType() ?? '', 'json')) {
            return;
        }

        $resourceClass = $request->attributes->get('_api_resource_class');

        if (DigitalDocument::class !== $resourceClass) {
            return;
        }

        $data = \json_decode($request->getContent(), true);

        if (!isset($data['content'])) {
            return;
        }

        $tempFileName = \uniqid();
        $tempFilePath = $this->tempDir.'/'.$tempFileName;
        $tempFile = new \SplFileObject('php://filter/convert.base64-decode/resource='.$tempFilePath, 'w');
        $tempFile->fwrite($data['content']);

        $data['contentFile'] = $tempFilePath;

        // close the temporary file
        $tempFile = null;

        // mark the temporary file for cleanup
        $this->tempFilePaths[] = $tempFilePath;

        unset($data['content']);

        // forcefully replace the request content
        $requestContentReflectionProperty = new \ReflectionProperty(Request::class, 'content');
        $requestContentReflectionProperty->setAccessible(true);
        $requestContentReflectionProperty->setValue($request, \json_encode($data));
    }

    /**
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        if (empty($this->tempFilePaths)) {
            return;
        }

        $this->filesystem->remove($this->tempFilePaths);
    }
}
