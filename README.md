# Symfony LiipImagineSerialization Bundle

[![Build Status](https://img.shields.io/scrutinizer/build/g/Bukashk0zzz/LiipImagineSerializationBundle.svg?style=flat-square)](https://travis-ci.org/Bukashk0zzz/LiipImagineSerializationBundle)
[![Code Coverage](https://img.shields.io/codecov/c/github/Bukashk0zzz/LiipImagineSerializationBundle.svg?style=flat-square)](https://codecov.io/github/Bukashk0zzz/LiipImagineSerializationBundle)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Bukashk0zzz/LiipImagineSerializationBundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/Bukashk0zzz/LiipImagineSerializationBundle/?branch=master)
[![License](https://img.shields.io/packagist/l/Bukashk0zzz/liip-imagine-serialization-bundle.svg?style=flat-square)](https://packagist.org/packages/Bukashk0zzz/liip-imagine-serialization-bundle)
[![Latest Stable Version](https://img.shields.io/packagist/v/Bukashk0zzz/liip-imagine-serialization-bundle.svg?style=flat-square)](https://packagist.org/packages/Bukashk0zzz/liip-imagine-serialization-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/Bukashk0zzz/liip-imagine-serialization-bundle.svg?style=flat-square)](https://packagist.org/packages/Bukashk0zzz/liip-imagine-serialization-bundle)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/01074784-ea2d-4902-8a62-53a5f743bc6f/small.png)](https://insight.sensiolabs.com/projects/01074784-ea2d-4902-8a62-53a5f743bc6f)
[![knpbundles.com](http://knpbundles.com/Bukashk0zzz/LiipImagineSerializationBundle/badge-short)](http://knpbundles.com/Bukashk0zzz/LiipImagineSerializationBundle)

About
-----

Provides integration between [LiipImagineBundle](https://github.com/liip/LiipImagineBundle "LiipImagineBundle") and
[JMSSerializerBundle](https://github.com/schmittjoh/JMSSerializerBundle "JMSSerializerBundle").
Allows to generate full or relative URIs to entity fields mapped with `@Bukashk0zzz` and `@JMS` annotations during the serialization.
Also bundle supports [VichUploaderBundle](https://github.com/dustin10/VichUploaderBundle "VichUploaderBundle") field type.

Installation Symfony Flex
------------

```bash
composer config extra.symfony.allow-contrib true
composer require bukashk0zzz/liip-imagine-serialization-bundle
```

Installation without Symfony Flex
------------

```bash
composer require bukashk0zzz/liip-imagine-serialization-bundle
```

Add the bundle to `app/AppKernel.php`

```php
$bundles = array(
	// ... other bundles
	new Bukashk0zzz\LiipImagineSerializationBundle\Bukashk0zzzLiipImagineSerializationBundle(),
);
```

Configuration
-------------

Add this to your `config.yml`:

```yaml
bukashk0zzz_liip_imagine_serialization:
    # Set true for generating url for vichUploader fields
    vichUploaderSerialize: false
    # Set true for generating url with host for vichUploader fields
    includeHost: false
    # Set true for adding original field value to object
    includeOriginal: false
    # Set true for adding host url to original value for vichUploader fields
    includeHostForOriginal: false
    # You can pass there your UrlNormalizer class that implements UrlNormalizerInterface
    originUrlNormalizer: null
    # You can pass there your UrlNormalizer class that implements UrlNormalizerInterface
    filteredUrlNormalizer: null
```


Usage
-----

Add the next class to the `use` section of your entity class.

```php
use Bukashk0zzz\LiipImagineSerializationBundle\Annotation as Bukashk0zzz;
```

Bundle provides two annotations which allow the serialization of url or `@Vich\UploadableField` fields in your entities.
At first you have to add `@Bukashk0zzz\LiipImagineSerializableClass` to the entity class which has image fields.
Then you have to add `@Bukashk0zzz\LiipImagineSerializableField` annotation to the field you want to serialize.

Annotation `@Bukashk0zzz\LiipImagineSerializableClass` does not have any option.  
Annotation `@Bukashk0zzz\LiipImagineSerializableField` has one required option *filter* which value should link to the LiipImagine filter.

It can be set like this `@Bukashk0zzz\LiipImagineSerializableField("photoFile")` or `@Bukashk0zzz\LiipImagineSerializableField(filter="photoFile")`. 
*filter* can be array of filters in this case serialized field will be also array.
For example if you add annotation `@Bukashk0zzz\LiipImagineSerializableField(filter={"big", "small"})` for field `image`  then you get:

```json
{
  "image": {
             "big": "/uploads/users/big/5659828fa80a7.jpg",
             "small": "/uploads/users/small/5659828fa80a7.jpg"
           }
}
```

Also there is another two options: 
- `vichUploaderField` - If you use VichUploaderBundle for your uploads you must specify link to the field with `@Vich\UploadableField` annotation 
- `virtualField` - By default serializer will override field value with link to filtered image. If you add `virtualField` option serializer will add to serialized object new field with name that you provided in this option and url to filtered image, original field in this case will be unattached. This option are required if you're using an array of filters.

Don't forget that to serialize image fields they also should be marked with `@JMS` annotations to be serialized.

The generated URI by default:

```json
{
  "photo": "http://example.com/uploads/users/photos/5659828fa80a7.jpg",
  "cover": "http://example.com/uploads/users/covers/456428fa8g4a8.jpg"
}
```

The generated URI with `includeHost` set to `false`:

```json
{
  "photo": "/uploads/users/photos/5659828fa80a7.jpg",
  "cover": "/uploads/users/covers/456428fa8g4a8.jpg"
}
```

If you need to change url before passing it to LiipImagine, for example you need to swap origin name, you can use originUrlNormalizer option in bundle config.
```yaml
bukashk0zzz_liip_imagine_serialization:
    originUrlNormalizer: AppBundle\Normalizer\UrlNormalizer
```

If you need to change url after LiipImagine processing, for example you need to swap origin domain, you can use filteredUrlNormalizer option in bundle config.
```yaml
bukashk0zzz_liip_imagine_serialization:
    filteredUrlNormalizer: AppBundle\Normalizer\UrlNormalizer
```

UrlNormalizer class must implement [UrlNormalizerInterface](https://github.com/Bukashk0zzz/LiipImagineSerializationBundle/blob/master/Normalizer/UrlNormalizerInterface.php)

```php
<?php

namespace AppBundle\Normalizer;

use Bukashk0zzz\LiipImagineSerializationBundle\Normalizer\UrlNormalizerInterface;

/**
 * Url normalizer
 */
class UrlNormalizer implements UrlNormalizerInterface
{    
    /**
    * {@inheritdoc} 
    */
    public function normalize($url){
        return str_replace('photo.jpg', 'my_photo.jpg', $url);
    }
}
```

Events
-------
There are two events:
- bukashk0zzz_liip_imagine.event_pre_origin_normalize // Dispatch before origin url normalization
- bukashk0zzz_liip_imagine.event_pre_filtered_normalize // Dispatch before filtered url normalization

**Example subscriber:**
```yaml
services:
    app.liip_imagine_serialization_subscriber:
        class: AppBundle\Subscribers\LiipImagineSerializationEventSubscriber
        tags:
            - { name: bukashk0zzz_liip_imagine_subscriber }
```

```php
<?php

namespace AppBundle\Subscribers;

use Bukashk0zzz\LiipImagineSerializationBundle\Event\UrlNormalizerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * LiipImagineSerializationEventSubscriber
 */
class LiipImagineSerializationEventSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
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

    /**
     * @param UrlNormalizerEvent $event
     */
    public function normalizeOrigin(UrlNormalizerEvent $event)
    {
        $event->setUrl(str_replace('photo', 'newPhoto', $event->getUrl()));
    }

    /**
     * @param UrlNormalizerEvent $event
     */
    public function normalizeFiltered(UrlNormalizerEvent $event)
    {
        $event->setUrl(str_replace('example.com', 'img.example.com', $event->getUrl()));
    }
}
```


Example
-------

```php
<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Bukashk0zzz\LiipImagineSerializationBundle\Annotation as Bukashk0zzz;
use Symfony\Component\HttpFoundation\File\File;

/**
 * User Entity
 *
 * @ORM\Table(name="users")
 * @ORM\Entity()
 *
 * @Vich\Uploadable
 * @Bukashk0zzz\LiipImagineSerializableClass
 */
class User
{    
    /**
     * @var string $coverUrl Cover url
     *
     * @ORM\Column(type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\SerializedName("cover")
     *
     * @Bukashk0zzz\LiipImagineSerializableField("thumb_filter")
     */
    public $coverUrl; 
    
    /**
     * @var string $photoName Photo name
     *
     * @ORM\Column(type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\SerializedName("photo")
     *
     * @Bukashk0zzz\LiipImagineSerializableField("thumb_filter", vichUploaderField="photoFile")
     */
    public $photoName;

    /**
     * @var File $photoFile Photo file
     *
     * @JMS\Exclude
     *
     * @Vich\UploadableField(mapping="user_photo_mapping", fileNameProperty="photoName")
     */
    public $photoFile;
}
```

Copyright / License
-------------------

See [LICENSE](https://github.com/bukashk0zzz/LiipImagineSerializationBundle/blob/master/LICENSE)
