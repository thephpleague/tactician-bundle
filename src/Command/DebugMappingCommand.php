<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Command;

use League\Tactician\Bundle\DependencyInjection\RoutingDebugReport;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugMappingCommand extends ContainerAwareCommand
{
    /**
     * @var RoutingDebugReport
     */
    private $report;

    public function __construct(RoutingDebugReport $report)
    {
        parent::__construct();

        $this->report = $report;
    }

    protected function configure()
    {
        $this->setName('tactician:debug');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Tactician routing');

        $headers = ['Command', 'Handler Service'];

        foreach ($this->report->toArray() as $busId => $map) {
            $io->section('Bus: ' . $busId);

            if (count($map) > 0) {
                $io->table($headers, $this->mappingToRows($map));
            } else {
                $io->warning("No registered commands for bus $busId");
            }
        }
    }

    private function mappingToRows(array $map)
    {
        $rows = [];
        foreach ($map as $commandName => $handlerService) {
            $rows[] = [$commandName, $handlerService];
        }

        return $rows;
    }
}
