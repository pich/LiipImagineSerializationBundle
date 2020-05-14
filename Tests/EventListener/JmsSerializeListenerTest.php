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

use Bukashk0zzz\LiipImagineSerializationBundle\Tests\Fixtures\User;
use Bukashk0zzz\LiipImagineSerializationBundle\Tests\Fixtures\UserPhotos;
use Bukashk0zzz\LiipImagineSerializationBundle\Tests\Fixtures\UserPictures;
use Bukashk0zzz\LiipImagineSerializationBundle\Tests\Normalizer\FilteredUrlNormalizer;
use Bukashk0zzz\LiipImagineSerializationBundle\Tests\Normalizer\OriginUrlNormalizer;
use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializerBuilder;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use Liip\ImagineBundle\Imagine\Cache\Signer;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Vich\UploaderBundle\Storage\FileSystemStorage;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * JmsSerializeListenerTest
 */
class JmsSerializeListenerTest extends TestCase
{
    /**
     * @var RequestContext Request context
     */
    private $requestContext;

    /**
     * @var CacheManager LiipImagineBundle Cache Manager
     */
    private $cacheManager;

    /**
     * @var StorageInterface Vich storage
     */
    private $vichStorage;

    /**
     * @var JmsSerializeEventsManager JMS Serialize test event manager
     */
    private $eventManager;

    /**
     * @var DeserializationContext JMS context
     */
    private $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        AnnotationRegistry::registerLoader('class_exists');
        $this->generateVichStorage();
        $this->context = (new JmsSerializeContextGenerator())->generateContext();
        $this->eventManager = new JmsSerializeEventsManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->requestContext = null;
        $this->eventManager = null;
        $this->cacheManager = null;
        $this->vichStorage = null;
    }

    /**
     * Test virtualField serialization
     */
    public function testVirtualFieldSerialization(): void
    {
        $user = new User();
        $this->generateCacheManager();
        $this->generateRequestContext();
        $this->eventManager->addEventListeners($this->requestContext, $this->cacheManager, $this->vichStorage);
        $serializer = SerializerBuilder::create()->configureListeners(function (EventDispatcher $dispatcher): void {
            $this->eventManager->addEvents($dispatcher, $this->requestContext, $this->cacheManager, $this->vichStorage);
        })->build();
        $result = $serializer->serialize($user, 'json');

        static::assertJson($result);
        $data = \json_decode($result, true);
        static::assertEquals('http://example.com:8800/a/path/to/an/image3.png', $data['imageThumb']);
    }

    /**
     * Test serialization
     */
    public function testSerialization(): void
    {
        $user = new User();
        $this->generateCacheManager();
        $this->generateRequestContext();
        $this->eventManager->addEventListeners($this->requestContext, $this->cacheManager, $this->vichStorage);
        $this->eventManager->dispatchEvents($this->context, $user);
        static::assertEquals('http://example.com:8800/a/path/to/an/image1.png', $user->getCoverUrl());
        static::assertEquals('http://example.com:8800/a/path/to/an/image2.png', $user->getPhotoName());
    }

    /**
     * Test serialization twice same value
     */
    public function testSerializationMultipleTimeSameValue(): void
    {
        $user = new User();
        $userTwo = new User();
        $this->generateCacheManager();
        $this->generateRequestContext();
        $this->eventManager->addEventListeners($this->requestContext, $this->cacheManager, $this->vichStorage);
        $this->eventManager->dispatchEvents($this->context, $user);
        $this->eventManager->dispatchEvents($this->context, $userTwo);
        static::assertEquals('http://example.com:8800/a/path/to/an/image2.png', $user->getPhotoName());
        static::assertEquals('http://example.com:8800/a/path/to/an/image2.png', $userTwo->getPhotoName());
    }

    /**
     * Test serialization twice same value
     */
    public function testSerializationMultipleDifferentValue(): void
    {
        $user = new User('test_user_1.png');
        $userTwo = new User('test_user_2.png');
        $this->generateCacheManager();
        $this->generateRequestContext();
        $this->eventManager->addEventListeners($this->requestContext, $this->cacheManager, $this->vichStorage);
        $this->eventManager->dispatchEvents($this->context, $user);
        $this->eventManager->dispatchEvents($this->context, $userTwo);
        static::assertEquals('http://example.com:8800/a/path/to/an/image2.png', $user->getPhotoName());
        static::assertEquals('http://example.com:8800/a/path/to/an/image5.png', $userTwo->getPhotoName());
    }

    /**
     * Test serialization of proxy object and field with array of filters
     */
    public function testProxySerialization(): void
    {
        $userPictures = new UserPictures();
        $this->generateCacheManager();
        $this->generateRequestContext(false, true);
        $this->eventManager->addEventListeners($this->requestContext, $this->cacheManager, $this->vichStorage);
        $data = $this->serializeObject($userPictures);

        static::assertEquals('http://example.com:8800/a/path/to/an/image1.png', $data['cover']['big']);
        static::assertEquals('http://example.com:8800/a/path/to/an/image2.png', $data['cover']['small']);
        static::assertEquals('http://example.com:8000/uploads/photo.jpg', $data['photo']);
        static::assertEquals('http://example.com:8800/a/path/to/an/image3.png', $data['photoThumb']);
    }

    /**
     * Test serialization with origin normalizer
     */
    public function testSerializationWithOriginNormalizer(): void
    {
        $userPictures = new UserPictures();
        $this->generateCacheManager();
        $this->generateRequestContext();
        $data = $this->serializeObject($userPictures, [
            'includeHost' => false,
            'vichUploaderSerialize' => true,
            'includeOriginal' => true,
            'originUrlNormalizer' => OriginUrlNormalizer::class,
        ]);

        static::assertEquals('/uploads/newPhoto.jpg', $data['photoThumb']['original']);
        static::assertEquals('/uploads/newPhoto.jpg', $data['photo']);
    }

    /**
     * Test serialization with filtered normalizer
     */
    public function testSerializationWithFilteredNormalizer(): void
    {
        $userPictures = new UserPictures();
        $this->generateCacheManager();
        $this->generateRequestContext();
        $data = $this->serializeObject($userPictures, [
            'includeHost' => true,
            'vichUploaderSerialize' => true,
            'includeOriginal' => true,
            'filteredUrlNormalizer' => FilteredUrlNormalizer::class,
        ]);

        static::assertEquals('http://img.example.com:8800/a/path/to/an/image3.png', $data['photoThumb']['thumb_filter']);
        static::assertEquals('http://img.example.com:8800/a/path/to/an/image1.png', $data['cover']['big']);
    }

    /**
     * Test serialization with event subscriber
     */
    public function testSerializationWithEventSubscriber(): void
    {
        $userPictures = new UserPictures();
        $this->generateCacheManager();
        $this->generateRequestContext();
        $this->eventManager->addNormalizerSubscriber();
        $data = $this->serializeObject($userPictures, [
            'includeHost' => true,
            'vichUploaderSerialize' => true,
            'includeOriginal' => true,
        ]);

        static::assertEquals('http://img.example.com:8800/a/path/to/an/image3.png', $data['photoThumb']['thumb_filter']);
        static::assertEquals('http://img.example.com:8800/a/path/to/an/image1.png', $data['cover']['big']);
        static::assertEquals('/uploads/newPhoto.jpg', $data['photoThumb']['original']);
        static::assertEquals('http://example.com/uploads/newPhoto.jpg', $data['photo']);
    }

    /**
     * Test serialization with url parse exception
     *
     * @expectedException \InvalidArgumentException
     */
    public function testSerializationWithUrlParseException(): void
    {
        $userPictures = new UserPictures();
        $this->generateCacheManager('http://blah.com:abcdef');
        $this->generateRequestContext();
        $this->serializeObject($userPictures, [
            'includeHost' => false,
        ]);
    }

    /**
     * Test serialization with included http host and port in the URI and include original option "true"
     */
    public function testHttpsSerialization(): void
    {
        $userPictures = new UserPictures();
        $this->generateCacheManager();
        $this->generateRequestContext(true, true);
        $data = $this->serializeObject($userPictures, [
            'includeHost' => true,
            'vichUploaderSerialize' => true,
            'includeOriginal' => true,
        ]);

        static::assertEquals('https://example.com:8800/uploads/photo.jpg', $data['photo']);
        static::assertEquals('http://example.com:8800/a/path/to/an/image1.png', $data['cover']['big']);
        static::assertEquals('http://example.com:8800/a/path/to/an/image2.png', $data['cover']['small']);
        static::assertEquals('http://example.com:8800/a/path/to/an/image3.png', $data['photoThumb']['thumb_filter']);
        static::assertEquals('/uploads/photo.jpg', $data['photoThumb']['original']);
    }

    /**
     * Test serialization without host in url and one filter
     */
    public function testSerializationWithoutHost(): void
    {
        $userPictures = new User();
        $this->generateCacheManager('/');
        $this->generateRequestContext(true, true);
        $data = $this->serializeObject($userPictures, [
            'includeHost' => false,
            'vichUploaderSerialize' => true,
            'includeOriginal' => false,
        ]);

        static::assertEquals('/a/path/to/an/image1.png', $data['cover']);
        static::assertEquals('/a/path/to/an/image2.png', $data['photo']);
    }

    /**
     * Test serialization with host in url for original
     */
    public function testSerializationWithHostForOriginal(): void
    {
        $userPictures = new UserPictures();
        $this->generateCacheManager();
        $this->generateRequestContext(true, true);
        $data = $this->serializeObject($userPictures, [
            'includeHost' => false,
            'vichUploaderSerialize' => true,
            'includeOriginal' => true,
            'includeHostForOriginal' => true,
        ]);

        static::assertEquals('/uploads/photo.jpg', $data['photo']);
        static::assertFalse((bool) \mb_strpos($data['cover']['original'], 'https://example.com:8800'));
        static::assertEquals('https://example.com:8800/uploads/photo.jpg', $data['photoThumb']['original']);
    }

    /**
     * Test serialization with host in url and host in url for original
     */
    public function testSerializationWithHostAndHostForOriginal(): void
    {
        $userPictures = new UserPictures();
        $this->generateCacheManager();
        $this->generateRequestContext(true, true);
        $data = $this->serializeObject($userPictures, [
            'includeHost' => true,
            'vichUploaderSerialize' => true,
            'includeOriginal' => true,
            'includeHostForOriginal' => true,
        ]);

        static::assertEquals('https://example.com:8800/uploads/photo.jpg', $data['photo']);
        static::assertFalse((bool) \mb_strpos($data['cover']['original'], 'https://example.com:8800'));
        static::assertEquals('https://example.com:8800/uploads/photo.jpg', $data['photoThumb']['original']);
    }

    /**
     * Test serialization with host in url and host in url for original and non-stored (resolve path) images
     */
    public function testSerializationWithHostAndHostForOriginalAndNonStoredImages(): void
    {
        $userPhotos = new UserPhotos();
        $this->generateCacheManager('https://example.com:8800/', false);
        $this->generateRequestContext(true, true);
        $data = $this->serializeObject($userPhotos, [
            'includeHost' => true,
            'vichUploaderSerialize' => true,
            'includeOriginal' => true,
            'includeHostForOriginal' => true,
        ]);

        static::assertEquals('https://example.com:8800/a/path/to/an/resolve/image1.png', $data['cover']['big']);
        static::assertEquals('https://example.com:8800/a/path/to/an/resolve/image2.png', $data['cover']['small']);
        static::assertEquals('https://example.com:8800/uploads/photo.jpg', $data['photo']);
        static::assertFalse((bool) \mb_strpos($data['cover']['original'], 'https://example.com:8800'));
        static::assertEquals('https://example.com:8800/uploads/photo.jpg', $data['photoThumb']['original']);
    }

    /**
     * Test serialization with no host in url and no host in url for original and non-stored (resolve path) images
     */
    public function testSerializationWithNoHostAndNoHostForOriginalAndNonStoredImages(): void
    {
        $userPhotos = new UserPhotos();
        $this->generateCacheManager('https://example.com:8800/', false);
        $this->generateRequestContext(true, true);
        $data = $this->serializeObject($userPhotos, [
            'includeHost' => false,
            'vichUploaderSerialize' => true,
            'includeOriginal' => true,
            'includeHostForOriginal' => false,
        ]);

        static::assertEquals('/a/path/to/an/resolve/image1.png', $data['cover']['big']);
        static::assertEquals('/a/path/to/an/resolve/image2.png', $data['cover']['small']);
        static::assertEquals('/uploads/photo.jpg', $data['photo']);
        static::assertEquals('/uploads/photo.jpg', $data['photoThumb']['original']);
    }

    /**
     * Test serialization with no host in url and no host in url for original and ONE non-stored (resolve path) image
     */
    public function testSerializationWithNoHostAndNoHostForOriginalAndOneNonStoredImage(): void
    {
        $userPictures = new UserPictures();
        $this->generateCacheManager('https://example.com:8800/', false);
        $this->generateRequestContext(true, true);
        $data = $this->serializeObject($userPictures, [
            'includeHost' => false,
            'vichUploaderSerialize' => true,
            'includeOriginal' => true,
            'includeHostForOriginal' => false,
        ]);

        static::assertEquals('/uploads/photo.jpg', $data['photoThumb']['original']);
        static::assertEquals('/a/path/to/an/resolve/image3.png', $data['photoThumb']['thumb_filter']);
    }

    /**
     * Test serialization without host in url and array of filters
     */
    public function testSerializationWithoutHostManyFilters(): void
    {
        $userPhotos = new UserPhotos();
        $this->generateCacheManager('/');
        $this->generateRequestContext(true, true);
        $data = $this->serializeObject($userPhotos, [
            'includeHost' => false,
            'vichUploaderSerialize' => true,
            'includeOriginal' => false,
        ]);

        static::assertEquals('/a/path/to/an/image1.png', $data['cover']['big']);
        static::assertEquals('/a/path/to/an/image2.png', $data['cover']['small']);
        static::assertEquals('/uploads/photo.jpg', $data['photo']);
        static::assertEquals('/a/path/to/an/image3.png', $data['photoThumb']['thumb_big']);
        static::assertEquals('/a/path/to/an/image4.png', $data['photoThumb']['thumb_small']);
    }

    /**
     * @param User|UserPictures|UserPhotos $user
     * @param mixed[]                      $config JMS serializer listner config
     *
     * @return mixed[]
     */
    protected function serializeObject($user, array $config = []): array
    {
        $serializer = SerializerBuilder::create()->configureListeners(function (EventDispatcher $dispatcher) use ($config): void {
            $this->eventManager->addEvents($dispatcher, $this->requestContext, $this->cacheManager, $this->vichStorage, $config);
        })->build();
        $result = $serializer->serialize($user, 'json');

        static::assertJson($result);

        return \json_decode($result, true);
    }

    protected function generateRequestContext(bool $https = false, bool $port = false): void
    {
        $this->requestContext = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scheme = $https ? 'https' : 'http';

        $this->requestContext->expects(static::any())
            ->method('getScheme')
            ->willReturn($scheme);

        $this->requestContext->expects(static::any())
            ->method('getHost')
            ->willReturn('example.com');

        if ($port) {
            if ($https) {
                $this->requestContext->expects(static::any())
                    ->method('getHttpsPort')
                    ->willReturn(8800);

                return;
            }

            $this->requestContext->expects(static::any())
                ->method('getHttpPort')
                ->willReturn(8000);
        }
    }

    /**
     * Prepare mock of Liip cache manager
     */
    protected function generateCacheManager(string $urlPrefix = 'http://example.com:8800/', bool $isStored = true): void
    {
        $resolver = $this->getMockBuilder(ResolverInterface::class)->getMock();
        $resolver
            ->expects(static::any())
            ->method('isStored')
            ->willReturn($isStored)
        ;
        $resolver
            ->expects(static::any())
            ->method('resolve')
            ->will(static::onConsecutiveCalls(
                $urlPrefix.'a/path/to/an/image1.png',
                $urlPrefix.'a/path/to/an/image2.png',
                $urlPrefix.'a/path/to/an/image3.png',
                $urlPrefix.'a/path/to/an/image4.png',
                $urlPrefix.'a/path/to/an/image5.png',
                $urlPrefix.'a/path/to/an/image6.png',
                $urlPrefix.'a/path/to/an/image7.png',
                $urlPrefix.'a/path/to/an/image8.png'
            ))
        ;

        $config = $this->getMockBuilder(FilterConfiguration::class)->getMock();
        $config->expects(static::any())
            ->method('get')
            ->willReturn([
                'size' => [180, 180],
                'mode' => 'outbound',
                'cache' => null,
            ])
        ;

        $router = $this->getMockBuilder(RouterInterface::class)->getMock();
        $router->expects(static::any())
            ->method('generate')
            ->will(static::onConsecutiveCalls(
                $urlPrefix.'a/path/to/an/resolve/image1.png',
                $urlPrefix.'a/path/to/an/resolve/image2.png',
                $urlPrefix.'a/path/to/an/resolve/image3.png',
                $urlPrefix.'a/path/to/an/resole/image4.png',
                $urlPrefix.'a/path/to/an/resole/image5.png',
                $urlPrefix.'a/path/to/an/resole/image6.png',
                $urlPrefix.'a/path/to/an/resole/image7.png',
                $urlPrefix.'a/path/to/an/resole/image8.png'
            ))
        ;

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        /** @noinspection PhpParamsInspection */
        $this->cacheManager = new CacheManager($config, $router, new Signer('secret'), $eventDispatcher);

        /** @noinspection PhpParamsInspection */
        $this->cacheManager->addResolver('default', $resolver);
    }

    /**
     * Generate vichStorage mock
     */
    protected function generateVichStorage(): void
    {
        $this->vichStorage = $this->getMockBuilder(FileSystemStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->vichStorage
            ->method('resolveUri')
            ->willReturn('/uploads/photo.jpg');
    }
}
