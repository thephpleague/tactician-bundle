<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\HandlerMapping;

use Symfony\Component\DependencyInjection\ContainerBuilder;

interface HandlerMapping
{
    public function build(ContainerBuilder $container, Routing $routing): Routing;
}