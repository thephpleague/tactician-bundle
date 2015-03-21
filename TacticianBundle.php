<?php namespace Xtrasmal\TacticianBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Xtrasmal\TacticianBundle\DependencyInjection\CommandHandlerCompilerPass;
use Xtrasmal\TacticianBundle\DependencyInjection\TacticianExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class TacticianBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new CommandHandlerCompilerPass());
    }

    public function getContainerExtension()
    {
        return new TacticianExtension();
    }

}
