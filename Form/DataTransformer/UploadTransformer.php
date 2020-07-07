<?php

namespace GaylordP\UploadBundle\Form\DataTransformer;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
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

    public function reverseTransform($data): ?Media
    {
        if (null === $data) {
            return null;
        }

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
        } elseif ('mp4' === $media->getFile()->guessExtension()) {
            $ffprobe = FFProbe::create();
            $ffmpeg = FFMpeg::create();

            $duration = round($ffprobe
                ->format($media->getFile()->getPathname())
                ->get('duration')
            );
            $media->setVideoTime((new \DateTime())->setTime(0, 0, 0)->modify('+' . $duration . ' seconds'));

            $dimensions = $ffprobe
                ->streams($media->getFile()->getPathname())
                ->videos()
                ->first()
                ->getDimensions()
            ;
            $media->setWidth($dimensions->getWidth());
            $media->setHeight($dimensions->getHeight());

            $video = $ffmpeg->open($media->getFile()->getPathname());
            $frame = $video->frame(TimeCode::fromSeconds($duration / 3 * 2));
            $frame->save($media->getFile()->getPath() . '/' . $media->getFile()->getFilename() . '.jpg');
        }

        return $media;
    }
}
