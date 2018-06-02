<?php

namespace Core\StorageProvider\AkeneoApi;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use League\Flysystem\ReadInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class AkeneoApiAdapter implements ReadInterface
{
    /**
     * @var AkeneoPimClientInterface
     */
    private $client;

    /**
     * @var PropertyAccessor
     */
    private $accessor;

    /**
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(
        AkeneoPimClientInterface $client
    ) {
        $this->client = $client;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    public function has($path)
    {
        $this->getMetadata($path);
    }

    public function read($path)
    {
        return array_merge(
            $this->getMetadata(),
            [
                'stream' => $stream = $this->client->getProductMediaFileApi()
                    ->download($path)
                    ->detach(),
                'contents' => stream_get_contents($stream),
            ]
        );
    }

    public function readStream($path)
    {
        return array_merge(
            $this->getMetadata(),
            [
                'stream' => $this->client->getProductMediaFileApi()
                    ->download($path)
                    ->detach(),
            ]
        );
    }

    public function listContents($directory = '', $recursive = false)
    {
        throw new \RuntimeException('Unimplemented feature');
    }

    public function getMetadata($path)
    {
        try {
            $meta = $this->client->getProductMediaFileApi()
                ->get($path);

            return [
                'type' => 'file',
                'path' => $path,
                'visibility' => 'public',
                'size' => $this->accessor->getValue($meta, '[size]'),
                'mimetype' => $this->accessor->getValue($meta, '[mime_type]'),
                'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('Etc/UTC')))->getTimestamp()
            ];
        } catch (HttpException $e) {
            return false;
        }
    }

    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }
}
