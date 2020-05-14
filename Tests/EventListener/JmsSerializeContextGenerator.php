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

use Doctrine\Common\Annotations\AnnotationReader;
use JMS\Serializer\Accessor\DefaultAccessorStrategy;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\Driver\AnnotationDriver;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use Metadata\MetadataFactory;

class JmsSerializeContextGenerator
{
    public function generateContext(): DeserializationContext
    {
        $namingStrategy = new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy());

        $context = DeserializationContext::create();
        $factory = new MetadataFactory(new AnnotationDriver(new AnnotationReader(), $namingStrategy));
        $context->initialize('json', new JsonSerializationVisitor(), new GraphNavigator\SerializationGraphNavigator($factory, new HandlerRegistry(), new DefaultAccessorStrategy()), $factory);

        return $context;
    }
}
