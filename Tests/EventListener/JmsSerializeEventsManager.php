<?php declare(strict_types = 1);
/*
 * This file is part of the Bukashk0zzzLiipImagineSerializationBundle
 *
 * (c) Denis Golubovskiy <bukashk0zzz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bukashk0zzz\LiipImagineSerializationBundle\Tests\EventListener;

use Bukashk0zzz\LiipImagineSerializationBundle\EventListener\JmsPostSerializeListener;
use Bukashk0zzz\LiipImagineSerializationBundle\EventListener\JmsPreSerializeListener;
use Bukashk0zzz\LiipImagineSerializationBundle\Tests\EventSubscriber\Bukashk0zzzSerializationEventSubscriber;
use Bukashk0zzz\LiipImagineSerializationBundle\Tests\Fixtures\User;
use Bukashk0zzz\LiipImagineSerializationBundle\Tests\Fixtures\UserPictures;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\EventDispatcher\Events as JmsEvents;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;
use Symfony\Component\Routing\RequestContext;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * JmsSerializeEventsManager
 */
class JmsSerializeEventsManager
{
    /**
     * @var EventDispatcher Dispatcher
     */
    private $dispatcher;

    /**
     * @var CachedReader Cached annotation reader
     */
    private $annotationReader;

    /**
     * @var SymfonyEventDispatcher
     */
    private $symfonyEventDispatcher;

    /**
     * JmsSerializeEventsManager constructor.
     */
    public function __construct()
    {
        $this->annotationReader = new CachedReader(new AnnotationReader(), new ArrayCache());
        $this->symfonyEventDispatcher = new SymfonyEventDispatcher();
    }

    /**
     * Add post & pre serialize event listeners
     */
    public function addEventListeners(RequestContext $requestContext, CacheManager $cacheManager, StorageInterface $vichStorage): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->addEvents($this->dispatcher, $requestContext, $cacheManager, $vichStorage);
    }

    /**
     * @param User|UserPictures $user
     */
    public function dispatchEvents(DeserializationContext $context, $user): ObjectEvent
    {
        /** @noinspection PhpParamsInspection */
        $event = new ObjectEvent($context, $user, []);
        $this->dispatcher->dispatch(JmsEvents::PRE_SERIALIZE, User::class, $context->getFormat(), $event);
        $this->dispatcher->dispatch(JmsEvents::POST_SERIALIZE, User::class, $context->getFormat(), $event);

        return $event;
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * Add post & pre serialize event to dispatcher
     *
     * @param mixed[] $config JMS serializer listner config
     */
    public function addEvents(EventDispatcher $dispatcher, RequestContext $requestContext, CacheManager $cacheManager, StorageInterface $vichStorage, array $config = []): void
    {
        if (\count($config) === 0) {
            $config = [
                'includeHost' => true,
                'vichUploaderSerialize' => true,
                'includeOriginal' => false,
            ];
        }

        $preListener = new JmsPreSerializeListener($requestContext, $this->annotationReader, $cacheManager, $vichStorage, $this->symfonyEventDispatcher, $config);
        $postListener = new JmsPostSerializeListener($requestContext, $this->annotationReader, $cacheManager, $vichStorage, $this->symfonyEventDispatcher, $config);

        $dispatcher->addListener(JmsEvents::PRE_SERIALIZE, [$preListener, 'onPreSerialize']);
        $dispatcher->addListener(JmsEvents::POST_SERIALIZE, [$postListener, 'onPostSerialize']);
    }

    /**
     * Add normalizer subscriber
     */
    public function addNormalizerSubscriber(): void
    {
        $this->symfonyEventDispatcher->addSubscriber(new Bukashk0zzzSerializationEventSubscriber());
    }
}
