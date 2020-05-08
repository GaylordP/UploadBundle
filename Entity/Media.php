<?php

namespace GaylordP\UploadBundle\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use GaylordP\UserBundle\Annotation\CreatedAt;
use GaylordP\UserBundle\Annotation\CreatedBy;
use GaylordP\UserBundle\Entity\Traits\Deletable;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Media
 *
 * @ORM\Entity(repositoryClass="GaylordP\UploadBundle\Repository\MediaRepository")
 */
class Media
{
    use Deletable;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var File
     */
    private $file;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=36)
     */
    private $uuid;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=8)
     */
    private $extension;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=15)
     */
    private $mime;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $isImage;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $size;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @CreatedAt
     */
    private $createdAt;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @CreatedBy
     */
    private $createdBy;

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Get file
     *
     * @return File
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * Set file
     *
     * @param File $file
     */
    public function setFile(?File $file): void
    {
        $this->file = $file;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * Set uuid
     *
     * @param string $uuid
     */
    public function setUuid(?string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get extension
     *
     * @return string
     */
    public function getExtension(): ?string
    {
        return $this->extension;
    }

    /**
     * Set extension
     *
     * @param string $extension
     */
    public function setExtension(?string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * Get mime
     *
     * @return string
     */
    public function getMime(): ?string
    {
        return $this->mime;
    }

    /**
     * Set mime
     *
     * @param string $mime
     */
    public function setMime(?string $mime): void
    {
        $this->mime = $mime;
    }

    /**
     * Set isImage
     *
     * @param bool $isImage
     */
    public function setIsImage(?bool $isImage): void
    {
        $this->isImage = $isImage;
    }

    /**
     * Get isImage
     *
     * @return string
     */
    public function getIsImage(): ?bool
    {
        return $this->isImage;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Set size
     *
     * @param integer $size
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $date
     */
    public function setCreatedAt(\DateTime $date): void
    {
        $this->createdAt = $date;
    }

    /**
     * Get createdBy
     *
     * @return User
     */
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * Set createdBy
     *
     * @param User $user
     */
    public function setCreatedBy(User $user): void
    {
        $this->createdBy = $user;
    }
}
