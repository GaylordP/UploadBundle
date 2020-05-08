<?php

namespace GaylordP\UploadBundle\Twig;

use GaylordP\UploadBundle\Util\IsImage;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class Extension extends AbstractExtension
{
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
