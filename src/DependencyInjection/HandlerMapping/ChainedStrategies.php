<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\HandlerMapping;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ChainedStrategies implements MappingStrategy
{
    /**
     * @var MappingStrategy[]
     */
    private $strategies;

    public function __construct(MappingStrategy ...$strategies)
    {
        $this->strategies = $strategies;
    }

    public function build(ContainerBuilder $container, Routing $routing): Routing
    {
        foreach ($this->strategies as $strategy) {
            $routing = $strategy->build($container, $routing);
        }

        return $routing;
    }
}