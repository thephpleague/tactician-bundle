# TacticianBundle
[![Build Status](https://travis-ci.org/xtrasmal/TacticianBundle.svg)](https://travis-ci.org/xtrasmal/TacticianBundle)

Symfony2 Bundle for the Tactician library
[https://github.com/thephpleague/tactician/](https://github.com/thephpleague/tactician/)

If you are looking for a Laravel Provider or want to help: [https://github.com/xtrasmal/TacticianProvider](https://github.com/xtrasmal/TacticianProvider)

## Setup
First add this bundle to your composer dependencies:

`> composer require xtrasmal\tactician-bundle`

Then register it in your AppKernel.php.

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Xtrasmal\TacticianBundle\TacticianBundle(),
            // ...
```

That's it! 

## Configuring Command Handlers
The most common use case with Tactician is passing a Command to the Command Bus and having it routed to the Command Bus.

Since handlers often have extra dependencies and are best lazily-loaded, you'll want to register them in the service container.

Let's say we have two classes, `RegisterUserCommand` and `RegisterUserHandler`. We'll register the Handler in the service container, along with a repository it needs. 

```
    foo.user.register_user_handler:
        class: Foo\User\RegisterUserHandler
        arguments:
            - @foo.user.user_repository
```

However, we still need to map the Command to the Handler. We can do this by adding a tag to the Handler's DI definition.

The tag should have two attributes: the tag name, which should always be `tactician.handler`, and the command, which should be FQCN of a Command it can handle.

```yml
    foo.user.register_user_handler:
        class: Foo\User\RegisterUserHandler
        arguments:
            - @foo.user.user_repository
        tags:
          - { name: tactician.handler, command: Foo\User\RegisterUserCommand }
```

## Configuring Middleware
Everything inside Tactician is a middleware plugin. Without any middleware configured, nothing will happen when you pass a command to `handle()`.

By default, the only Middleware enabled is the Command Handler support. You can override this and add your own middleware in the `app/config.yml`.
    
```yaml

   tactician:
       middlewares:
         # service ids for all your middlewares, top down. First in, first out.
         - tactician.middleware.locking
         - my.custom.middleware.plugin
         - tactician.middleware.command_handler

```

**Important**: Adding your own middleware is absolutely encouraged, just be sure to always add `tactician.middleware.command_handler` as the final middleware. Otherwise, your commands won't actually be executed.

Check the [Tactician docs](http://tactician.thephpleague.com/) for more info and a complete list of middleware. 

### Extra Bundled Middleware

This bundles ships with a few pre-configured middlewares, they can be enabled using the method above by just listing their ids.

#### Validator Middleware (tactician.middleware.validator)

The validator middleware will plug into Symfony's Validator (@validator) and will throw and exception if the command is not valid.

Constraints can be added via configuration or annotations like in default Symfony practices, please refer to [their docs](http://symfony.com/doc/current/book/validation.html). 

The middleware will throw an `InvalidCommand` Exception that will contain the command and the `ContraintViolationList` returned by the validator.

#### Locking Middleware (tactician.middleware.locking)

This middleware is bundled in Tactician, please refer to [the official documentation](http://tactician.thephpleague.com/plugins/locking-middleware/) for details.

#### Command Handler Middleware (tactician.middleware.command_handler)

**Always ensure this is the last middleware listed**

While not listed this is the core of Tactician and handles executing commands, it should always be enabled.

## Using the Command Bus 
Create a service and inject the command bus:

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
