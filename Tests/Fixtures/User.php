<?php declare(strict_types = 1);
/*
 * This file is part of the Bukashk0zzzLiipImagineSerializationBundle
 *
 * (c) Denis Golubovskiy <bukashk0zzz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bukashk0zzz\LiipImagineSerializationBundle\Tests\Fixtures;

use Bukashk0zzz\LiipImagineSerializationBundle\Annotation as Bukashk0zzz;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * User Entity
 *
 * @ORM\Table(name="users")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 *
 * @Vich\Uploadable()
 * @Bukashk0zzz\LiipImagineSerializableClass()
 */
class User
{
    /**
     * @var string Cover url
     *
     * @ORM\Column(type="string", length=255)
     *
     * @JMS\Expose()
     * @JMS\SerializedName("cover")
     *
     * @Bukashk0zzz\LiipImagineSerializableField("thumb_filter")
     */
    public $coverUrl;

    /**
     * @var string Image url
     *
     * @ORM\Column(type="string", length=255)
     *
     * @JMS\Expose()
     * @JMS\SerializedName("image")
     *
     * @Bukashk0zzz\LiipImagineSerializableField(filter="thumb_filter", virtualField="imageThumb")
     */
    public $imageUrl;

    /**
     * @var string Photo name
     *
     * @ORM\Column(type="string", length=255)
     *
     * @JMS\Expose()
     * @JMS\SerializedName("photo")
     *
     * @Bukashk0zzz\LiipImagineSerializableField(filter="thumb_filter", vichUploaderField="photoFile")
     */
    public $photoName;

    /**
     * @var File Photo file
     *
     * @JMS\Exclude()
     *
     * @Vich\UploadableField(mapping="user_photo_mapping", fileNameProperty="photoName")
     */
    public $photoFile;

    /**
     * @var UserPictures
     *
     * @ORM\OneToMany(targetEntity="Bukashk0zzz\LiipImagineSerializationBundle\Tests\Fixtures\UserPictures", mappedBy="user")
     */
    public $userPictures;

    /**
     * @var UserPhotos
     *
     * @ORM\OneToMany(targetEntity="Bukashk0zzz\LiipImagineSerializationBundle\Tests\Fixtures\UserPhotos", mappedBy="user")
     */
    public $userPhotos;

    /**
     * User constructor.
     *
     * @param null $photoName
     */
    public function __construct($photoName = null)
    {
        $this->setCoverUrl(__DIR__.'/test.png');
        if ($photoName) {
            $this->setPhotoName(__DIR__.'/'.$photoName);
        } else {
            $this->setPhotoName(__DIR__.'/test.png');
        }
        $this->setImageUrl(__DIR__.'/test.png');
    }

    /**
     * To string
     */
    public function __toString(): string
    {
        return 'New User';
    }

    /**
     * Get photo name
     *
     * @return string Photo name
     */
    public function getPhotoName(): string
    {
        return $this->photoName;
    }

    /**
     * Set photo name
     *
     * @param string $photoName Photo name
     */
    public function setPhotoName(string $photoName): User
    {
        $this->photoName = $photoName;

        return $this;
    }

    /**
     * Get photo file
     *
     * @return File Photo file
     */
    public function getPhotoFile(): File
    {
        return $this->photoFile;
    }

    /**
     * Set photo file
     *
     * @param File $photoFile Photo file
     */
    public function setPhotoFile(File $photoFile): User
    {
        $this->photoFile = $photoFile;

        return $this;
    }

    /**
     * Add userPictures
     */
    public function addUserPictures(UserPictures $userPictures): User
    {
        $this->userPictures[] = $userPictures;

        return $this;
    }

    /**
     * Get userPictures
     */
    public function getUserPictures(): Collection
    {
        return $this->userPictures;
    }

    /**
     * Add userPhotos
     */
    public function addUserPhotos(UserPhotos $userPhotos): User
    {
        $this->userPhotos[] = $userPhotos;

        return $this;
    }

    /**
     * Get userPhotos
     */
    public function getUserPhotos(): Collection
    {
        return $this->userPhotos;
    }

    public function getCoverUrl(): string
    {
        return $this->coverUrl;
    }

    public function setCoverUrl(string $coverUrl): User
    {
        $this->coverUrl = $coverUrl;

        return $this;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): User
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }
}
