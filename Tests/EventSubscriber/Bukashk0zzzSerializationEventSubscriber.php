<?php declare(strict_types = 1);
/*
 * This file is part of the Bukashk0zzzLiipImagineSerializationBundle
 *
 * (c) Denis Golubovskiy <bukashk0zzz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bukashk0zzz\LiipImagineSerializationBundle\Tests\EventSubscriber;

use Bukashk0zzz\LiipImagineSerializationBundle\Event\UrlNormalizerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Bukashk0zzzSerializationEventSubscriber
 */
class Bukashk0zzzSerializationEventSubscriber implements EventSubscriberInterface
{
    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UrlNormalizerEvent::ORIGIN => [
                ['normalizeOrigin', 10],
            ],
            UrlNormalizerEvent::FILTERED => [
                ['normalizeFiltered', 10],
            ],
        ];
    }

    public function normalizeOrigin(UrlNormalizerEvent $event): void
    {
        $event->setUrl(\str_replace('photo', 'newPhoto', $event->getUrl()));
    }

    public function normalizeFiltered(UrlNormalizerEvent $event): void
    {
        $event->setUrl(\str_replace('example.com', 'img.example.com', $event->getUrl()));
    }
}
