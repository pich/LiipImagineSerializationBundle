<?php declare(strict_types = 1);
/*
 * This file is part of the Bukashk0zzzLiipImagineSerializationBundle
 *
 * (c) Denis Golubovskiy <bukashk0zzz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bukashk0zzz\LiipImagineSerializationBundle\EventListener;

use Bukashk0zzz\LiipImagineSerializationBundle\Annotation\LiipImagineSerializableField;
use Bukashk0zzz\LiipImagineSerializationBundle\Event\UrlNormalizerEvent;
use Bukashk0zzz\LiipImagineSerializationBundle\Normalizer\UrlNormalizerInterface;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Persistence\Proxy;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * JmsSerializeListenerAbstract
 */
class JmsSerializeListenerAbstract
{
    /**
     * @var RequestContext Request context
     */
    protected $requestContext;

    /**
     * @var CachedReader Cached annotation reader
     */
    protected $annotationReader;

    /**
     * @var CacheManager LiipImagineBundle Cache Manager
     */
    protected $cacheManager;

    /**
     * @var StorageInterface Vich storage
     */
    protected $vichStorage;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var mixed[] Bundle config
     */
    protected $config;

    /**
     * JmsSerializeListenerAbstract constructor.
     *
     * @param mixed[] $config
     */
    public function __construct(
        RequestContext $requestContext,
        CachedReader $annotationReader,
        CacheManager $cacheManager,
        StorageInterface $vichStorage,
        EventDispatcherInterface $eventDispatcher,
        array $config
    ) {
        $this->requestContext = $requestContext;
        $this->annotationReader = $annotationReader;
        $this->cacheManager = $cacheManager;
        $this->vichStorage = $vichStorage;
        $this->eventDispatcher = $eventDispatcher;
        $this->config = $config;
    }

    /**
     * @param ObjectEvent $event Event
     *
     * @return mixed
     */
    protected function getObject(ObjectEvent $event)
    {
        $object = $event->getObject();

        if ($object instanceof Proxy
            && !$object->__isInitialized()
        ) {
            $object->__load();
        }

        return $object;
    }

    /**
     * @param mixed  $object Serialized object
     * @param string $value  Value of field
     *
     * @return mixed[]|string
     */
    protected function serializeValue(LiipImagineSerializableField $liipAnnotation, $object, $value)
    {
        $vichField = $liipAnnotation->getVichUploaderField();
        if ($vichField !== null) {
            $value = $this->vichStorage->resolveUri($object, $vichField);
        }

        $result = [];
        $value = $this->normalizeUrl($value, UrlNormalizerInterface::TYPE_ORIGIN);
        if (\array_key_exists('includeOriginal', $this->config) && $this->config['includeOriginal']) {
            $result['original'] = (\array_key_exists('includeHostForOriginal', $this->config) && $this->config['includeHostForOriginal'] && $liipAnnotation->getVichUploaderField())
                ? $this->getHostUrl().$value
                : $value;
        }

        $filters = $liipAnnotation->getFilter();
        if (\is_array($filters)) {
            /** @var array $filters */
            foreach ($filters as $filter) {
                $result[$filter] = $this->prepareFilteredUrl($this->cacheManager->getBrowserPath($value, $filter));
            }

            return $result;
        }

        $filtered = $this->cacheManager->getBrowserPath($value, $filters);
        if (\count($result) !== 0) {
            $result[$filters] = $this->prepareFilteredUrl($filtered);

            return $result;
        }

        return $filtered;
    }

    /**
     * Get host url (scheme, host, port)
     *
     * @return string Host url
     */
    protected function getHostUrl(): string
    {
        $url = $this->requestContext->getScheme().'://'.$this->requestContext->getHost();

        if ($this->requestContext->getScheme() === 'http' && $this->requestContext->getHttpPort() && $this->requestContext->getHttpPort() !== 80) {
            $url .= ':'.$this->requestContext->getHttpPort();
        } elseif ($this->requestContext->getScheme() === 'https' && $this->requestContext->getHttpsPort() && $this->requestContext->getHttpsPort() !== 443) {
            $url .= ':'.$this->requestContext->getHttpsPort();
        }

        return $url;
    }

    /**
     * Normalize url if needed
     */
    protected function normalizeUrl(string $url, string $normalizer): string
    {
        $url = $this->addPreNormalizeUrlEvent($normalizer, $url);

        if (\array_key_exists($normalizer, $this->config) && $this->config[$normalizer]) {
            $normalizerClassName = $this->config[$normalizer];
            $normalizer = new $normalizerClassName();
            if ($normalizer instanceof UrlNormalizerInterface) {
                $url = $normalizer->normalize($url);
            }
        }

        return $url;
    }

    /**
     * If config demands, it will remove host and scheme (protocol) from passed url
     */
    private function prepareFilteredUrl(string $url): string
    {
        if (\array_key_exists('includeHost', $this->config) && !$this->config['includeHost']) {
            $url = $this->stripHostFromUrl($url);
        }

        return $this->normalizeUrl($url, UrlNormalizerInterface::TYPE_FILTERED);
    }

    /**
     * Removes host and scheme (protocol) from passed url
     */
    private function stripHostFromUrl(string $url): string
    {
        $parts = \parse_url($url);
        if ($parts !== false && \array_key_exists('path', $parts)) {
            return \array_key_exists('query', $parts) ? $parts['path'].'?'.$parts['query'] : $parts['path'];
        }

        throw new \InvalidArgumentException('Can\'t strip host from url, because can\'t parse url.');
    }

    private function addPreNormalizeUrlEvent(string $type, string $url): string
    {
        /** @var UrlNormalizerEvent $event */
        $event = $this->eventDispatcher->dispatch(
            $type === UrlNormalizerInterface::TYPE_ORIGIN
                ? UrlNormalizerEvent::ORIGIN
                : UrlNormalizerEvent::FILTERED,
            new UrlNormalizerEvent($url)
        );

        return $event->getUrl();
    }
}
