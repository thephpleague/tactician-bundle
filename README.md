# TacticianBundle
[![Build Status](https://travis-ci.org/thephpleague/tactician-bundle.svg)](https://travis-ci.org/thephpleague/tactician-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thephpleague/tactician-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/thephpleague/tactician-bundle/?branch=master)

Symfony2 Bundle for the Tactician library
[https://github.com/thephpleague/tactician/](https://github.com/thephpleague/tactician/)

## Installation

### Step 1: Download the Bundle
Open a command console, enter your project directory and execute the
following command to download the latest release candidate for this bundle:

```bash
$ composer require league/tactician-bundle
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
The most common use case with Tactician is passing a Command to the Command Bus and having it routed to the Handler.

Since handlers often have extra dependencies and are best lazily-loaded, you'll want to register them in the service container.

Let's say we have two classes, `RegisterUserCommand` and `RegisterUserHandler`. We'll register the Handler in the service container, along with a repository it needs. 

```yaml
foo.user.register_user_handler:
    class: Foo\User\RegisterUserHandler
    arguments:
        - '@foo.user.user_repository'
```

However, we still need to map the Command to the Handler. We can do this by adding a tag to the Handler's DI definition.

The tag should have two attributes: the tag name, which should always be `tactician.handler`, and the command, which should be the FQCN of the Command.

```yaml
foo.user.register_user_handler:
    class: Foo\User\RegisterUserHandler
    arguments:
        - '@foo.user.user_repository'
    tags:
        - { name: tactician.handler, command: Foo\User\RegisterUserCommand }
```

### Symfony 3.3+
As of Symfony version 3.3 all services registered in the DI container are marked private by default. For this bundle to work properly the registered handlers needs to be public. This can be achieved by setting the public attribute on the service to `true`. 

*Note:* This is a temporary solution for versions earlier than the 1.0 release candidates. If you've upgraded to 1.0 RC or higher, this is no longer necessary.

```yaml
foo.user.register_user_handler:
    class: Foo\User\RegisterUserHandler
    public: true
    arguments:
        - '@foo.user.user_repository'
    tags:
        - { name: tactician.handler, command: Foo\User\RegisterUserCommand }
```

## Using the Command Bus 
Create a service and inject the command bus:

```yaml
services:
    app.your_controller:
        class: AppBundle\Controller\YourNameController
        arguments:
            - '@tactician.commandbus'
```

Then pass a command to the command bus for execution: 

```php
<?php namespace AppBundle\Controller;

use League\Tactician\CommandBus;
use AppBundle\Commands\DoSomethingCommand;

class YourNameController
{
    private $commandbus;

    public function __construct(CommandBus $commandbus)
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

## Configuring Middleware
Everything inside Tactician is a middleware plugin. Without any middleware configured, nothing will happen when you pass a command to `$commandBus->handle()`.

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

## Configuring Multiple Command Buses
The bundle is pre-configured with a command bus called "default", with the service id `tactician.commandbus`.
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

If you want, you can also change which command handler is registered to `tactician.commandbus`. You can do this by
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

By default, all commands are available in each bus. If you want to make a command available only to a specific bus, you need to specify its id :

```yaml
foo.user.register_user_handler:
    class: Foo\User\RegisterUserHandler
    arguments:
        - '@foo.user.user_repository'
    tags:
        - { name: tactician.handler, command: Foo\User\RegisterUserCommand, bus: queued }
```

## Extra Bundled Middleware

This bundles ships with a few pre-configured middlewares. To enable them, add them to the middlewares list in your bus configuration (see [Configuring Middleware](#configuring-middleware))

### Validator Middleware (tactician.middleware.validator)

This middleware uses Symfony's validator to check the command object before passing it along. In practice, this means you can add any Symfony validator annotations to your command to ensure it's fully correct before execution. This isn't a full replacement for writing your objects in an internally consistent style but it can be very helpful.  

Constraints can be added via configuration or annotations like in default Symfony practices, please refer to [their docs](https://symfony.com/doc/current/validation).

If the command fails, it will throw a [League\Tactician\Bundle\Middleware\InvalidCommandException](src/Middleware/InvalidCommandException.php). This exception also contains the ConstraintViolationList produced by the validator so you can inspect or log the errors yourself. 

### Locking Middleware (tactician.middleware.locking)

This middleware is bundled in Tactician, please refer to [the official documentation](http://tactician.thephpleague.com/plugins/locking-middleware/) for details.

### Security Middleware (tactician.middleware.security)

The security middleware will perform authorization on handling all commands. By default an AccessDenied exception will be thrown if the user is not authorized.

```yaml
tactician:
    security:
        My\User\Command:
            - 'ROLE_USER'
        My\Admin\Command:
            - 'ROLE_ADMIN'
        My\UserAndAdmin\Command:
            - 'ROLE_USER'
            - 'ROLE_ADMIN'
```

This middleware is based on Symfony's AccessDecisionManager and voter system. We recommend familiarizing yourself with it before trying to use this middleware. If you'd like to configure more complex scenarios, consider implementing a custom Symfony voter.

As a precaution, the middleware _requires_ you to register the command in the security configuration before it will be evaluated by Symfony's AccessDecisionManager.
 
Furthermore, while the security middleware is based on trusted components, we always recommend a defense in depth strategy. Simply hooking your command bus up to a public web endpoint and relying fully on this middleware may not be sufficient coverage for your application. 

The Security middleware is disabled by default.

### Command Handler Middleware (tactician.middleware.command_handler)

**Always ensure this is the last middleware listed**

This is the plugin that actually matches your command to a handler and executes it. If you have complex matching logic, feel free to implement your own variant and leave this middleware off.
   
However, for 99% of users, this should be enabled and set as the last middleware in the list. 

## Customizing the MethodNameInflector used by the `tactician.middleware.command_handler` middleware

By default, the bundle uses `HandleInflector` from Tactician core. That is to say, it expects your Command Handlers to have a `handle()` method that receives the command to execute.

However, [if you prefer a different inflector](http://tactician.thephpleague.com/tweaking-tactician/), you can pass the service name in `config.yml`.

```yaml
tactician:
    method_inflector: my_inflector.service.id
```

Tactician core offers a list of custom Inflectors, all of which are supported in this bundle. Assuming a class called My\App\RegisterUserCommand(), the invoked methods on the handler would be:

 * `tactician.handler.method_name_inflector.handle` - `handle()`
 * `tactician.handler.method_name_inflector.handle_class_name` - `handleRegisterUserCommand()`
 * `tactician.handler.method_name_inflector.handle_class_name_without_suffix` - `handleRegisterUser()`
 * `tactician.handler.method_name_inflector.invoke` - `__invoke()`

While `handle()` is a reasonable default, using one of the class name methods allows you to handle multiple commands on a single class (possibly useful if they share common dependencies or fit together in some fashion). Likewise, __invoke can be useful if you're mapping to a list of closures.

When using multiple buses, you can also specify the `method_inflector` of particular bus :

```yaml
tactician:
    commandbus:
        command:
            middleware:
                - tactician.middleware.command_handler
        query:
            middleware:
                - tactician.middleware.command_handler
            method_inflector: tactician.handler.method_name_inflector.handle_class_name_without_suffix
```

## Testing
``` bash
$ ./vendor/bin/phpunit
```

## Security
Disclosure information can be found on [the main Tactician repo](https://github.com/thephpleague/tactician#security).

## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.
