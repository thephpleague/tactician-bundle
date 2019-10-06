<?php

namespace League\Tactician\Bundle;

use League\Tactician\Bundle\DependencyInjection\Compiler;
use League\Tactician\Bundle\DependencyInjection\TacticianExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class TacticianBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new Compiler\DoctrineMiddlewarePass());
        $container->addCompilerPass(new Compiler\ValidatorMiddlewarePass());
        $container->addCompilerPass(new Compiler\SecurityMiddlewarePass());
        $container->addCompilerPass(new Compiler\CommandHandlerPass());
    }

    public function getContainerExtension()
    {
        return new TacticianExtension();
    }
}
