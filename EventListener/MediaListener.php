<?php

namespace GaylordP\UploadBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use GaylordP\UploadBundle\Entity\Media;
use GaylordP\UploadBundle\Util\IsImage;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MediaListener
{
    private $uploadDirectory;

    public function __construct(ParameterBagInterface $parameters)
    {
        $this->uploadDirectory = $parameters->get('upload_directory');
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        if ($object instanceof Media) {
            $object->setUuid($object->getFile()->getFileInfo()->getPathInfo()->getBasename());
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
            $uploadDirectory = $this->uploadDirectory . '/' . $object->getUuid() . '/';

            mkdir($uploadDirectory, 0777, true);

            rename(
                $object->getFile()->getRealPath(),
                $uploadDirectory .  $object->getName()
            );

            rmdir($object->getFile()->getPath());

            $object->setFile(null);
        }
    }
}
