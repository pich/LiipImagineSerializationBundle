<?php

/*
 * This file is part of the Bukashk0zzzLiipImagineSerializationBundle
 *
 * (c) Denis Golubovskiy <bukashk0zzz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bukashk0zzz\LiipImagineSerializationBundle\Tests\EventListener;

use Bukashk0zzz\LiipImagineSerializationBundle\Tests\Fixtures\UserPictures;
use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Routing\RequestContext;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use Vich\UploaderBundle\Storage\StorageInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Cache\Signer;
use Bukashk0zzz\LiipImagineSerializationBundle\Tests\Fixtures\User;

/**
 * JmsSerializeListenerTest
 *
 * @author Denis Golubovskiy <bukashk0zzz@gmail.com>
 */
class JmsSerializeListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestContext $requestContext Request context
     */
    private $requestContext;

    /**
     * @var CacheManager $cacheManager LiipImagineBundle Cache Manager
     */
    private $cacheManager;

    /**
     * @var StorageInterface $storage Vich storage
     */
    private $vichStorage;

    /**
     * @var JmsSerializeEventsManager $eventManager JMS Serialize test event manager
     */
    private $eventManager;

    /**
     * @var DeserializationContext $context JMS context
     */
    private $context;

    /**
     * @var string $filePath Image file path
     */
    private $filePath;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        AnnotationRegistry::registerLoader('class_exists');
        $this->filePath = (new User())->getCoverUrl();
        $this->generateVichStorage();
        $this->context = (new JmsSerializeContextGenerator())->generateContext();
        $this->eventManager = new JmsSerializeEventsManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->requestContext   = null;
        $this->eventManager   = null;
        $this->cacheManager = null;
        $this->vichStorage = null;
        $this->filePath = null;
    }

    /**
     * Test virtualField serialization
     */
    public function testVirtualFieldSerialization()
    {
        $user = new User();
        $this->generateCacheManager();
        $this->generateRequestContext();
        $this->eventManager->addEventListeners($this->requestContext, $this->cacheManager, $this->vichStorage);
        $serializer = SerializerBuilder::create()->configureListeners(function (EventDispatcher $dispatcher) {
            $this->eventManager->addEvents($dispatcher, $this->requestContext, $this->cacheManager, $this->vichStorage);
        })->build();
        $result = $serializer->serialize($user, 'json');

        static::assertJson($result);
        $data = json_decode($result, true);
        static::assertEquals('http://a/path/to/an/image3.png', $data['imageThumb']);
    }

    /**
     * Test serialization
     */
    public function testSerialization()
    {
        $user = new User();
        $this->generateCacheManager();
        $this->generateRequestContext();
        $this->eventManager->addEventListeners($this->requestContext, $this->cacheManager, $this->vichStorage);
        $this->eventManager->dispatchEvents($this->context, $user);
        static::assertEquals('http://a/path/to/an/image1.png', $user->getCoverUrl());
        static::assertEquals('http://a/path/to/an/image2.png', $user->getPhotoName());

        // Serialize same object second time (check cache)
        $this->context = (new JmsSerializeContextGenerator())->generateContext();
        $this->eventManager->dispatchEvents($this->context, $user);

        static::assertEquals('http://a/path/to/an/image1.png', $user->getCoverUrl());
        static::assertEquals('http://a/path/to/an/image2.png', $user->getPhotoName());
        static::assertEquals($this->filePath, $user->getImageUrl());
    }

    /**
     * Test serialization of proxy object and field with array of filters
     */
    public function testProxySerialization()
    {
        $userPictures = new UserPictures();
        $this->generateCacheManager();
        $this->generateRequestContext(false, true);
        $this->eventManager->addEventListeners($this->requestContext, $this->cacheManager, $this->vichStorage);
        $data = $this->serializeObject($userPictures);

        static::assertEquals('http://a/path/to/an/image1.png', $data['cover']['big']);
        static::assertEquals('http://a/path/to/an/image2.png', $data['cover']['small']);
        static::assertEquals('http://example.com:8000/uploads/photo.jpg', $data['photo']);
        static::assertEquals('http://a/path/to/an/image3.png', $data['photoThumb']);
    }

    /**
     * Test serialization with included http host and port in the URI and include original option "true"
     */
    public function testHttpsSerialization()
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
        static::assertEquals('http://a/path/to/an/image1.png', $data['cover']['big']);
        static::assertEquals('http://a/path/to/an/image2.png', $data['cover']['small']);
        static::assertEquals('http://a/path/to/an/image3.png', $data['photoThumb']['thumb_filter']);
        static::assertEquals('/uploads/photo.jpg', $data['photoThumb']['original']);
    }

    /**
     * @param User|UserPictures $user
     * @param array             $config JMS serializer listner config
     * @return array
     */
    protected function serializeObject($user, array $config = [])
    {
        $serializer = SerializerBuilder::create()->configureListeners(function (EventDispatcher $dispatcher) use ($config) {
            $this->eventManager->addEvents($dispatcher, $this->requestContext, $this->cacheManager, $this->vichStorage, $config);
        })->build();
        $result = $serializer->serialize($user, 'json');

        static::assertJson($result);

        return json_decode($result, true);
    }

    /**
     * @param bool $https
     * @param bool $port
     */
    protected function generateRequestContext($https = false, $port = false)
    {
        $this->requestContext = $this->getMockBuilder('Symfony\Component\Routing\RequestContext')
            ->disableOriginalConstructor()
            ->getMock();

        $scheme = $https ? 'https':'http';

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
    protected function generateCacheManager()
    {
        $resolver = static::getMock('Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface');
        $resolver
            ->expects(static::any())
            ->method('isStored')
            ->will(static::returnValue(true))
        ;
        $resolver
            ->expects(static::any())
            ->method('resolve')
            ->will(static::onConsecutiveCalls('http://a/path/to/an/image1.png', 'http://a/path/to/an/image2.png', 'http://a/path/to/an/image3.png'))
        ;

        $config = static::getMock('Liip\ImagineBundle\Imagine\Filter\FilterConfiguration');
        $config->expects(static::any())
            ->method('get')
            ->will(static::returnValue(array(
                'size' => array(180, 180),
                'mode' => 'outbound',
                'cache' => null,
            )))
        ;

        $router = static::getMock('Symfony\Component\Routing\RouterInterface');
        $router->expects(static::never())
            ->method('generate')
        ;

        $eventDispatcher = static::getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        /** @noinspection PhpParamsInspection */
        $this->cacheManager = new CacheManager($config, $router, new Signer('secret'), $eventDispatcher);

        /** @noinspection PhpParamsInspection */
        $this->cacheManager->addResolver('default', $resolver);
    }

    /**
     * Generate vichStorage mock
     */
    protected function generateVichStorage()
    {
        $this->vichStorage = $this->getMockBuilder('Vich\UploaderBundle\Storage\FileSystemStorage')
            ->disableOriginalConstructor()
            ->getMock();
        $this->vichStorage->expects(static::any())
            ->method('resolveUri')
            ->will(static::returnValue('/uploads/photo.jpg'));
    }
}
