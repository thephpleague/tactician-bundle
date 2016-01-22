# TacticianBundle
[![Build Status](https://travis-ci.org/thephpleague/tactician-bundle.svg)](https://travis-ci.org/thephpleague/tactician-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thephpleague/tactician-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/thephpleague/tactician-bundle/?branch=master)

Symfony2 Bundle for the Tactician library
[https://github.com/thephpleague/tactician/](https://github.com/thephpleague/tactician/)

If you are looking for a Laravel Provider or want to help: [https://github.com/xtrasmal/TacticianProvider](https://github.com/xtrasmal/TacticianProvider)

## Installation

### Step 1: Download the Bundle
Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require league/tactician-bundle "~0.4"
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle
Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new League\Tactician\Bundle\TacticianBundle(),
        );

        // ...
    }

    // ...
}
```

## Configuring Command Handlers
The most common use case with Tactician is passing a Command to the Command Bus and having it routed to the Command Bus.

Since handlers often have extra dependencies and are best lazily-loaded, you'll want to register them in the service container.

Let's say we have two classes, `RegisterUserCommand` and `RegisterUserHandler`. We'll register the Handler in the service container, along with a repository it needs. 

```yaml
foo.user.register_user_handler:
    class: Foo\User\RegisterUserHandler
    arguments:
        - '@foo.user.user_repository'
```

However, we still need to map the Command to the Handler. We can do this by adding a tag to the Handler's DI definition.

The tag should have two attributes: the tag name, which should always be `tactician.handler`, and the command, which should be FQCN of a Command it can handle.

```yaml
foo.user.register_user_handler:
    class: Foo\User\RegisterUserHandler
    arguments:
        - '@foo.user.user_repository'
    tags:
        - { name: tactician.handler, command: Foo\User\RegisterUserCommand }
```

## Configuring Middleware
Everything inside Tactician is a middleware plugin. Without any middleware configured, nothing will happen when you pass a command to `handle()`.

By default, the only Middleware enabled is the Command Handler support. You can override this and add your own middleware in the `app/config.yml`.

```yaml
tactician:
    commandbus:
        default:
            middleware:
                # service ids for all your middlewares, top down. First in, first out.
                - tactician.middleware.locking
                - my.custom.middleware.plugin
                - tactician.middleware.command_handler
```

**Important**: Adding your own middleware is absolutely encouraged, just be sure to always add `tactician.middleware.command_handler` as the final middleware. Otherwise, your commands won't actually be executed.

Check the [Tactician docs](http://tactician.thephpleague.com/) for more info and a complete list of middleware.

## Configuring Command buses
The bundle is pre-configured with a command bus called "default". Which has the service name `tactician.commandbus`.
Some users want to configure more than one command bus though. You can do this via configuration, like so:

```yaml
tactician:
    commandbus:
        default:
            middleware:
                - tactician.middleware.command_handler
        queued:
            middleware:
                - tactician.middleware.queued_command_handler
```

The configuration defines two buses: "default" and "queued". These buses will be registered as the
`tactician.commandbus.default` and `tactician.commandbus.queued` services respectively.

If you want, you can also change which command handler is registered under `tactician.commandbus`. You can do this by
setting the `default_bus` value in the configuration, like so:

```yaml
tactician:
    default_bus: queued
    commandbus:
        default:
            middleware:
                # ...
        queued:
            middleware:
                # ...
```

### Extra Bundled Middleware

This bundles ships with a few pre-configured middlewares, they can be enabled using the method above by just listing their ids.

#### Validator Middleware (tactician.middleware.validator)

The validator middleware will plug into Symfony's Validator (@validator) and will throw and exception if the command is not valid.

Constraints can be added via configuration or annotations like in default Symfony practices, please refer to [their docs](http://symfony.com/doc/current/book/validation.html). 

The middleware will throw an `InvalidCommandException` that will contain the command and the `ConstraintViolationList` returned by the validator.

#### Locking Middleware (tactician.middleware.locking)

This middleware is bundled in Tactician, please refer to [the official documentation](http://tactician.thephpleague.com/plugins/locking-middleware/) for details.

#### Command Handler Middleware (tactician.middleware.command_handler)

**Always ensure this is the last middleware listed**

While not listed this is the core of Tactician and handles executing commands, it should always be enabled.

## Customizing the MethodNameInflector used by the `tactician.middleware.command_handler` middleware

By default the library uses `HandleInflector` to define the handling method names, which maps to `handle()`.

To use a different inflector you can now pass the service name in the config.

```yaml
tactician:
    method_inflector: my_inflector.service.id
```

Tactician offers a list of custom Inflectors, these are all supported.

 * `tactician.handler.method_name_inflector.handle`
 * `tactician.handler.method_name_inflector.handle_class_name`
 * `tactician.handler.method_name_inflector.handle_class_name_without_suffix`
 * `tactician.handler.method_name_inflector.invoke`

## Using the Command Bus 
Create a service and inject the command bus:

```yaml
services:
    app.your_controller:
        class: AppBundle\Controller\YourNameController
        arguments:
            - '@tactician.commandbus'
```

Then party like it's 1994

```php
<?php namespace AppBundle\Controller;

use League\Tactician\CommandBus;
use AppBundle\Commands\DoSomethingCommand;

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

## Testing
``` bash
$ ./vendor/bin/phpunit
```

## Security
Disclosure information can be found on [the main Tactician repo](https://github.com/thephpleague/tactician#security).

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
