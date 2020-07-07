<?php

namespace GaylordP\UploadBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use GaylordP\UploadBundle\Entity\Media;
use GaylordP\UploadBundle\Util\IsImage;
use Hashids\Hashids;

class MediaListener
{
    private $uploadDirectory;

    public function __construct(string $uploadDirectory)
    {
        $this->uploadDirectory = $uploadDirectory;
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        if ($object instanceof Media) {
            $object->setName($object->getFile()->getFilename());
            $object->setExtension($object->getFile()->guessExtension());
            $object->setMime($object->getFile()->getMimeType());
            $object->setSize($object->getFile()->getSize());
            $object->setIsImage(IsImage::check($object->getFile()->getMimeType()));
        }
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        if ($object instanceof Media) {
            $uuid = uuid_create(UUID_TYPE_RANDOM);
            $object->setUuid($uuid);

            $uploadDirectory = $this->uploadDirectory . '/' . $uuid . '/';

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0777, true);
            }

            $realPath = $object->getFile()->getRealPath();

            rename(
                $realPath,
                $uploadDirectory .  $object->getName()
            );

            if ('mp4' === $object->getExtension()) {
                rename(
                    $realPath . '.jpg',
                    $uploadDirectory .  $object->getName() . '.jpg'
                );
            }

            rmdir($object->getFile()->getPath());

            $object->setFile(null);
            $args->getEntityManager()->flush();
        }
    }
}
