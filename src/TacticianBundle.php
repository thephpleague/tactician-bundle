<?php

namespace League\Tactician\Bundle;

use League\Tactician\Bundle\DependencyInjection\HandlerMapping\ClassNameStrategy;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\MappingStrategy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use League\Tactician\Bundle\DependencyInjection\Compiler;
use League\Tactician\Bundle\DependencyInjection\TacticianExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class TacticianBundle extends Bundle
{
    /**
     * @var MappingStrategy
     */
    private $handlerMapping;

    public function __construct(MappingStrategy $handlerMapping = null)
    {
        if ($handlerMapping == null) {
            $handlerMapping = new ClassNameStrategy();
        }

        $this->handlerMapping = $handlerMapping;
    }


    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new Compiler\DoctrineMiddlewarePass());
        $container->addCompilerPass(new Compiler\ValidatorMiddlewarePass());
        $container->addCompilerPass(new Compiler\SecurityMiddlewarePass());
        $container->addCompilerPass(new Compiler\CommandHandlerPass($this->handlerMapping));
    }

    public function getContainerExtension()
    {
        return new TacticianExtension();
    }
}
