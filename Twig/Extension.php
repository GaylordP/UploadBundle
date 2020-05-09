<?php

namespace GaylordP\UploadBundle\Twig;

use GaylordP\UploadBundle\Util\IsImage;
use GaylordP\UploadBundle\Util\MediaResize;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

class Extension extends AbstractExtension
{
    private $mediaResize;

    public function __construct(MediaResize $mediaResize)
    {
        $this->mediaResize = $mediaResize;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'media_resize',
                [$this->mediaResize, 'resize']
            ),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest(
                'image',
                [IsImage::class, 'check']
            ),
        ];
    }
}
