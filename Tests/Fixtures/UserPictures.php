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
use Doctrine\Common\Persistence\Proxy;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * UserPictures Entity
 *
 * @ORM\Table(name="userPictures")
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("all")
 * @Vich\Uploadable()
 * @Bukashk0zzz\LiipImagineSerializableClass()
 */
class UserPictures implements Proxy
{
    /**
     * @var string Cover url
     * @ORM\Column(type="string", length=255)
     * @JMS\Expose()
     * @JMS\SerializedName("cover")
     * @Bukashk0zzz\LiipImagineSerializableField(filter={"big", "small"})
     */
    public $coverUrl;

    /**
     * @var string Image url
     * @ORM\Column(type="string", length=255)
     * @JMS\Expose()
     * @JMS\SerializedName("image")
     * @Bukashk0zzz\LiipImagineSerializableField(filter="thumb_filter", virtualField="image_thumb")
     */
    public $imageUrl;

    /**
     * @var string Photo name
     * @ORM\Column(type="string", length=255)
     * @JMS\Expose()
     * @JMS\SerializedName("photo")
     * @Bukashk0zzz\LiipImagineSerializableField(filter="thumb_filter", vichUploaderField="photoFile", virtualField="photoThumb")
     */
    public $photoName;

    /**
     * @var File Photo file
     * @JMS\Exclude()
     * @Vich\UploadableField(mapping="user_photo_mapping", fileNameProperty="photoName")
     */
    public $photoFile;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Bukashk0zzz\LiipImagineSerializationBundle\Tests\Fixtures\User", inversedBy="pictures")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $userId;

    /** @var bool */
    private $status = false;

    // @codingStandardsIgnoreStart

    /**
     * {@inheritdoc}
     */
    public function __load()
    {
        $this->setCoverUrl(__DIR__.'/test.png');
        $this->setPhotoName('/uploads/photo.jpg');
        $this->status = true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function __isInitialized()
    {
        return $this->status;
    }

    // @codingStandardsIgnoreEnd

    /**
     * To string
     */
    public function __toString(): string
    {
        return 'New Photo';
    }

    /**
     * Set userId
     */
    public function setUserId(int $userId): UserPictures
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Set user
     *
     * @param User $user
     */
    public function setUser(?User $user = null): UserPictures
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function getCoverUrl(): string
    {
        return $this->coverUrl;
    }

    public function setCoverUrl(string $coverUrl): UserPictures
    {
        $this->coverUrl = $coverUrl;

        return $this;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): UserPictures
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPhotoName()
    {
        return $this->photoName;
    }

    /**
     * @param mixed $photoName
     */
    public function setPhotoName($photoName): UserPictures
    {
        $this->photoName = $photoName;

        return $this;
    }

    public function getPhotoFile(): File
    {
        return $this->photoFile;
    }

    public function setPhotoFile(File $photoFile): UserPictures
    {
        $this->photoFile = $photoFile;

        return $this;
    }
}
