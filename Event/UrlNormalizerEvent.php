<?php declare(strict_types = 1);

namespace Bukashk0zzz\LiipImagineSerializationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * UrlNormalizerEvent
 */
class UrlNormalizerEvent extends Event
{
    public const ORIGIN = 'bukashk0zzz_liip_imagine.event_pre_origin_normalize';
    public const FILTERED = 'bukashk0zzz_liip_imagine.event_pre_filtered_normalize';

    /**
     * @var string
     */
    protected $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): UrlNormalizerEvent
    {
        $this->url = $url;

        return $this;
    }
}
