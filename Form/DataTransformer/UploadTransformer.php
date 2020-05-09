<?php

namespace GaylordP\UploadBundle\Form\DataTransformer;

use GaylordP\UploadBundle\Entity\Media;
use GaylordP\UploadBundle\Util\IsImage;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File;

class UploadTransformer implements DataTransformerInterface
{
    public function transform($data): ?string
    {
        return $data;
    }

    public function reverseTransform($data): Media
    {
        $media = new Media();
        $media->setFile(new File($data));
        $media->setName($media->getFile()->getFilename());
        $media->setExtension($media->getFile()->guessExtension());
        $media->setMime($media->getFile()->getMimeType());
        $media->setSize($media->getFile()->getSize());
        $media->setIsImage(IsImage::check($media->getMime()));

        if (true === $media->getIsImage()) {
            list($width, $height) = getimagesize($media->getFile()->getPathname());

            $media->setWidth($width);
            $media->setHeight($height);
        }

        return $media;
    }
}
