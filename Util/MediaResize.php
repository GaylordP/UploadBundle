<?php

namespace GaylordP\UploadBundle\Util;

use GaylordP\UploadBundle\Entity\Media;
use Intervention\Image\ImageManager;

class MediaResize
{
    private $uploadDirectory;
    private $publicDir;

    public function __construct(
        string $uploadDirectory,
        string $projectDir
    ) {
        $this->uploadDirectory = $uploadDirectory;
        $this->publicDir = $projectDir . '/public';
    }

    public function resize(
        Media $media,
        int $width = null,
        int $height = null,
        string $resizeType = 'ratio'
    ): string {
        $filePath = $this->uploadDirectory . '/' . $media->getToken() . '/' . $media->getName();

        if (null === $width && null === $height) {
            return $this->returnFilePath($filePath);
        }

        $fileResizePath = $this->uploadDirectory . '/resize/' . $resizeType . '/_' . $width . '_' . $height. '_/' . $media->getToken() . '/' . $media->getName();

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

    private function returnFilePath(string $filePath): string
    {
        return str_replace($this->publicDir, '', $filePath);
    }
}
