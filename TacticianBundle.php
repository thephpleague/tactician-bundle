<?php namespace Xtrasmal\TacticianBundle;

use Xtrasmal\TacticianBundle\DependencyInjection\TacticianExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class TacticianBundle extends Bundle
{

    public function getContainerExtension()
    {
        return new TacticianExtension();
    }

}
