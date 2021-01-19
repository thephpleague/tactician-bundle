<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugCommand extends Command
{
    /**
     * @var array
     */
    private $mappings;

    public function __construct(array $mappings)
    {
        parent::__construct();

        $this->mappings = $mappings;
    }

    protected function configure()
    {
        $this->setName('debug:tactician');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Tactician routing');

        $headers = ['Command', 'Handler Service'];

        foreach ($this->mappings as $busId => $map) {
            $io->section('Bus: ' . $busId);

            if (count($map) > 0) {
                $io->table($headers, $this->mappingToRows($map));
            } else {
                $io->warning("No registered commands for bus $busId");
            }
        }

        return 0;
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
