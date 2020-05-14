<?php declare(strict_types = 1);
/*
 * This file is part of the Bukashk0zzzLiipImagineSerializationBundle
 *
 * (c) Denis Golubovskiy <bukashk0zzz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bukashk0zzz\LiipImagineSerializationBundle\Annotation;

use Doctrine\ORM\Mapping\Annotation;

/**
 * LiipImagineSerializableField
 *
 * @Annotation()
 * @Target({"PROPERTY", "METHOD"})
 */
final class LiipImagineSerializableField implements Annotation
{
    /**
     * @var string|string[]|null LiipImagine Filter
     */
    private $filter;

    /**
     * @var string Field
     */
    private $vichUploaderField;

    /**
     * @var string Virtual Field
     */
    private $virtualField;

    /**
     * @var mixed[] Options
     */
    private $options;

    /**
     * Constructor
     *
     * @param mixed[] $options Options
     */
    public function __construct(array $options)
    {
        $this->options = $options;

        if (!\array_key_exists('value', $this->options) && !\array_key_exists('filter', $this->options)) {
            throw new \LogicException(\sprintf('Either "value" or "filter" option must be set.'));
        }

        if ($this->checkOption('value', true)) {
            $this->setFilter($options['value']);
        } elseif ($this->checkOption('filter', true)) {
            $this->setFilter($this->options['filter']);
        }

        if ($this->checkOption('vichUploaderField', false)) {
            $this->setVichUploaderField($this->options['vichUploaderField']);
        }

        if ($this->checkOption('virtualField', false)) {
            $this->setVirtualField($this->options['virtualField']);
        }
    }

    /**
     * @return string|string[]|null
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param string|string[] $filter
     */
    public function setFilter($filter): LiipImagineSerializableField
    {
        $this->filter = $filter;

        return $this;
    }

    public function getVichUploaderField(): ?string
    {
        return $this->vichUploaderField;
    }

    public function setVichUploaderField(string $vichUploaderField): LiipImagineSerializableField
    {
        $this->vichUploaderField = $vichUploaderField;

        return $this;
    }

    public function getVirtualField(): ?string
    {
        return $this->virtualField;
    }

    public function setVirtualField(string $virtualField): LiipImagineSerializableField
    {
        $this->virtualField = $virtualField;

        return $this;
    }

    private function checkOption(string $optionName, bool $canBeArray): bool
    {
        if (\array_key_exists($optionName, $this->options)) {
            if (!\is_string($this->options[$optionName])) {
                if ($canBeArray && !\is_array($this->options[$optionName])) {
                    throw new \InvalidArgumentException(\sprintf(\sprintf('Option "%s" must be a array or string.', $optionName)));
                }

                if (!$canBeArray) {
                    throw new \InvalidArgumentException(\sprintf(\sprintf('Option "%s" must be a string.', $optionName)));
                }
            }

            return true;
        }

        return false;
    }
}
