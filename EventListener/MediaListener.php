<?php

namespace GaylordP\UploadBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use GaylordP\UploadBundle\Entity\Media;
use GaylordP\UploadBundle\Util\IsImage;
use Hashids\Hashids;

class MediaListener
{
    private $uploadDirectory;
    private $salt;

    public function __construct(
        string $uploadDirectory,
        string $salt
    ) {
        $this->uploadDirectory = $uploadDirectory;
        $this->salt = $salt;
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
            $hashids = new Hashids($this->salt, 4);
            $object->setToken($hashids->encode($object->getId()));

            $uploadDirectory = $this->uploadDirectory . '/' . $object->getToken() . '/';

            mkdir($uploadDirectory, 0777, true);

            rename(
                $object->getFile()->getRealPath(),
                $uploadDirectory .  $object->getName()
            );

            rmdir($object->getFile()->getPath());

            $object->setFile(null);
            $args->getEntityManager()->flush();
        }
    }
}
