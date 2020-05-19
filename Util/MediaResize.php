<?php

namespace GaylordP\UploadBundle\Util;

use GaylordP\UploadBundle\Entity\Media;
use Intervention\Image\ImageManager;

class MediaResize
{
    private $uploadDirectory;
    private $publicDir;
    private $uploadParameters;

    public function __construct(
        string $uploadDirectory,
        string $projectDir,
        array $uploadParameters
    ) {
        $this->uploadDirectory = $uploadDirectory;
        $this->publicDir = $projectDir . '/public';
        $this->uploadParameters = $uploadParameters;
    }

    public function resize(
        Media $media,
        int $width = null,
        int $height = null,
        string $resizeType = 'ratio'
    ): string {
        if (true === $media->getIsImage()) {
            $mediaName = $media->getName();
        } else {
            $mediaName = $media->getName() . '.jpg';
        }

        $filePath = $this->uploadDirectory . '/' . $media->getToken() . '/' . $mediaName;

        if (null === $width && null === $height) {
            return $this->returnFilePath($filePath);
        }

        if (false === $this->resizeIsEnabled($resizeType, $width, $height)) {
            return 'resize-is-not-enabled';
        }

        $fileResizePath = $this->uploadDirectory . '/resize/' . $resizeType . '/_' . $width . '_' . $height. '_/' . $media->getToken() . '/' . $mediaName;

        if (file_exists($fileResizePath)) {
            return $this->returnFilePath($fileResizePath);
        }

        $dirName = dirname($fileResizePath);
        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }

        $manager = new ImageManager();
        $img = $manager->make($filePath);
        if ('ratio' === $resizeType) {
            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        } elseif ('square' === $resizeType) {
            $img->fit($width, $height, function ($constraint) {
                $constraint->upsize();
            });
        }
        $img->save($fileResizePath);

        return $this->returnFilePath($fileResizePath);
    }

    public function resizeIsEnabled($resizeType, $width, $height): bool
    {
        return
            (
                in_array($resizeType, ['ratio', 'square'])
                    &&
                array_key_exists('media_resize_enabled', $this->uploadParameters)
                    &&
                array_key_exists($resizeType, $this->uploadParameters['media_resize_enabled'])
                    &&
                in_array(''. $width .'-'. $height .'', $this->uploadParameters['media_resize_enabled'][$resizeType])
            )
        ;
    }

    private function returnFilePath(string $filePath): string
    {
        return str_replace($this->publicDir, '', $filePath);
    }
}
