<?php

namespace League\Tactician\Bundle\Tests;

class EchoTextHandler
{
    public function handle(EchoText $command)
    {
        echo $command->getText();
    }
}
