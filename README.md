# TacticianBundle
Symfony2 Bundle for the Tactician library by Ross Tuck
[https://github.com/thephpleague/tactician/](https://github.com/thephpleague/tactician/)


## Setup

Add the bundle to your AppKernel.php


new Xtrasmal\TacticianBundle\TacticianBundle() 


Add this to the config.yml
    
```yaml

   tactician:
       quickstart:
         # command + handler pairs
          YourName\Commands\DoSomethingCommand: YourName\Handlers\DoSomethingHandler
       middlewares:
         # all your middlewares, top down. First in, first out.
         - YourName\Middleware\DoSomethingMiddleware

```


Then create a service and inject the command bus:

```yaml

services:
    your.controller:
        class: %your.controller.class%
        arguments:
            - @tactician.commandbus
```

Then party like it's 1994

```php

<?php namespace YourName\Controller;

use League\Tactician\CommandBus;
use YourName\Commands\DoSomethingCommand;

class YourNameController
{

    private $commandbus;

    public function __construct( CommandBus $commandbus )
    {
        $this->commandbus = $commandbus;
    }

    public function doSomething()
    {
        $command = new DoSomethingCommand();
        $this->commandbus->handle($command);
    }

}


```