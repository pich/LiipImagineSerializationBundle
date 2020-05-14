<?php declare(strict_types = 1);

namespace Bukashk0zzz\LiipImagineSerializationBundle\Normalizer;

/**
 * UrlNormalizerInterface
 */
interface UrlNormalizerInterface
{
    public const TYPE_ORIGIN = 'originUrlNormalizer';
    public const TYPE_FILTERED = 'filteredUrlNormalizer';

    public function normalize(?string $url): string;
}
