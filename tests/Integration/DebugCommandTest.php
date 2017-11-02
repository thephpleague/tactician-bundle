<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\Integration;

use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use League\Tactician\Bundle\Tests\Fake\OtherFakeCommand;
use League\Tactician\Bundle\Tests\Fake\SomeHandler;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @runTestsInSeparateProcesses
 */
final class DebugCommandTest extends IntegrationTest
{
    public function test_report()
    {
        // GIVEN
        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.command_handler
EOF
        );

        $this->registerService('a.handler', SomeHandler::class, [['name' => 'tactician.handler', 'command' => FakeCommand::class]]);
        $this->registerService('b.handler', SomeHandler::class, [['name' => 'tactician.handler', 'command' => OtherFakeCommand::class]]);

        // WHEN
        $result = $this->runCommand()->getDisplay();

        // THEN
        $expectation = <<<'EOF'

Tactician routing
=================

Bus: default
------------

 ----------------------------------------------------- ----------------- 
  Command                                               Handler Service  
 ----------------------------------------------------- ----------------- 
  League\Tactician\Bundle\Tests\Fake\FakeCommand        a.handler        
  League\Tactician\Bundle\Tests\Fake\OtherFakeCommand   b.handler        
 ----------------------------------------------------- ----------------- 


EOF;

        $this->assertEquals(
            $expectation,
            $result
        );
    }

    /**
     * @return CommandTester
     */
    private function runCommand(): CommandTester
    {
        $application = new Application(static::$kernel);

        $command = $application->find('tactician:debug');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName()
        ));
        return $commandTester;
    }
}
