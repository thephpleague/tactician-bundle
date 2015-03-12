<?php namespace TacticianBundle;

use CommandBusBundle\DependencyInjection\CommandBusExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class TacticianBundle extends Bundle
{

    public function getContainerExtension()
    {
        return new TacticianExtension();
    }

}
