<?php

namespace GaylordP\UploadBundle;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UploadCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasParameter('twig.form.resources')) {
            $resources = $container->getParameter('twig.form.resources') ?: [];
            array_unshift($resources, '@Upload/form/fields.html.twig', '@Upload/form/theme.html.twig');
            $container->setParameter('twig.form.resources', $resources);
        }
    }
}
