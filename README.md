# TacticianBundle
[![Build Status](https://travis-ci.org/thephpleague/tactician-bundle.svg)](https://travis-ci.org/thephpleague/tactician-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thephpleague/tactician-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/thephpleague/tactician-bundle/?branch=master)

Symfony2 Bundle for the Tactician library
[https://github.com/thephpleague/tactician/](https://github.com/thephpleague/tactician/)

## Installation

### Step 1: Download the Bundle
Open a command console, enter your project directory and execute the
following command to download the latest stable release for this bundle:

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
    private $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function doSomething()
    {
        $command = new DoSomethingCommand();
        $this->commandBus->handle($command);
    }
}
```

### Autowiring

If Symfony autowire feature is enabled (avaliable for Symfony 2.8+), instead of creating a service for each controller using the default commandbus you can inject and use it as follows:

```php
<?php namespace AppBundle\Controller;

use League\Tactician\CommandBus;
use AppBundle\Commands\DoSomethingCommand;

class YourNameController
{
    public function doSomething(CommandBus $commandBus)
    {
        $command = new DoSomethingCommand();
        $commandBus->handle($command);
    }
}
```

Note that this only works for the default commandbus, if you want to inject other than the default one you can override the config through an alias with:

```yaml
services:
    League\Tactician\CommandBus: '@tactician.commandbus.your_commandbus'
```

## Configuring Command Handlers
When you pass a command to Tactician, the ultimate goal is to have it mapped to a Handler.

Since handlers often have extra dependencies and are best lazily-loaded, you'll want to register them in the service container.

There's a few different ways to map your Commands to a Handler, all of which can be combined. We'll walk through them below:

### 1. Manually Mapping
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
<?php
foo.user.register_user_handler:
    class: Foo\User\RegisterUserHandler
    arguments:
        - '@foo.user.user_repository'
    tags:
        - { name: tactician.handler, command: Foo\User\RegisterUserCommand }
```

### 2. Map Based On Typehints
Rather than repeating the command's full class name, we can also reflect on the Handler's method typehints.

```yaml
foo.user.register_user_handler:
    class: Foo\User\RegisterUserHandler
    arguments:
        - '@foo.user.user_repository'
    tags:
        - { name: tactician.handler, typehints: true }
```

This detects what commands this handler receives by inspecting the class' methods. The rules for matching are:

1. The method must be public.
2. The method must accept only one parameter.
3. The parameter must be typehinted with a class name.

In other words, the RegisterUserHandler class should look like this:

```php
<?php
class RegisterUserHandler
{
    public function handle(RegisterUser $command)
    {
       // do stuff
    }
}
```

If you have multiple commands going into a single handler, they will all be detected, provided they follow the rules above. The actual name of the method is NOT important.

If you're using typehints AND FQCN mappings, then the FQCN mapping always wins out.

Registering by typehints can be very useful if you're using the autowiring features in the latest versions of Symfony.

### 3. Custom Mapping Rules

If you'd like to define your own rules for automatically mapping commands to handlers in the container, you can do that as well.

First, implement the [HandlerMapping interface](src/DependencyInjection/HandlerMapping/HandlerMapping.php). During compile time, you'll receive a ContainerBuilder and a Tactician [Router object](src/DependencyInjection/HandlerMapping/Routing.php) you can use to map your commands to your handler services.

There's a good chance that your strategy will involve using container tags of some sort. If that's the case, look into extending the [TagBasedMapping](src/DependencyInjection/HandlerMapping/TagBasedMapping.php) abstract class. This will save you some of the boiler plate associated with handling multiple buses.

Once your object is ready, pass it to the TacticianBundle instance when setting your AppKernel.php:

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
            new League\Tactician\Bundle\TacticianBundle(
                new My\Custom\HandlerMapping()
            ),
        );
    }
}
```

### 4. Combining Mapping Strategies

If you have multiple strategies you'd like to chain together, you can use the CompositeMapping object to chain them together.

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
            new League\Tactician\Bundle\TacticianBundle(
                new League\Tactician\Bundle\DependencyInjection\HandlerMapping\CompositeMapping(
                    new League\Tactician\Bundle\DependencyInjection\HandlerMapping\ClassNameMapping(), // standard command: "FQCN" mapping
                    new League\Tactician\Bundle\DependencyInjection\HandlerMapping\TypeHintMapping(), // standard typehints: true mapping
                    new My\Custom\HandlerMapping() // your custom routing
                )
            ),
        );
    }
}
```
If multiple HandlerMapping strategies detect the same Command, but different Handlers, then the last mentioned mapping strategy wins. Therefore, it's usually best to put your custom strategy last OR the ClassNameMapping last so you can make full overrides when necessary.

### 5. Write Your Own Middleware
Remember, Tactician is based purely on middleware. If you don't want to mess around with all this and you have a simple convention based way of mapping commands to handlers, [just write your own middleware to execute Handlers](https://github.com/thephpleague/tactician/blob/master/src/Handler/CommandHandlerMiddleware.php#L56). 

### Checking your wiring
You can run the ```debug:tactician``` command to get a list of which commands are mapped to which services.

## Configuring Middleware
Everything inside Tactician is a middleware plugin. Without any middleware configured, nothing will happen when you pass a command to `$commandBus->handle()`.

By default, the only Middleware enabled is the Command Handler support. You can override this and add your own middleware in the `app/config.yml`.

```yaml
tactician:
    commandbus:
        default:
            middleware:
                # service ids for all your middlewares, top down. First in, last out.
                - tactician.middleware.locking
                - my.custom.middleware.plugin
                - tactician.middleware.command_handler
```

**Important**: Adding your own middleware is absolutely encouraged, just be sure to always add `tactician.middleware.command_handler` as the final middleware. Otherwise, your commands won't actually be executed.

Check the [Tactician docs](http://tactician.thephpleague.com/) for more info and a complete list of middleware.

## Configuring Multiple Command Buses
The bundle is pre-configured with a command bus called "default", with the service id `tactician.commandbus`.
Some users want to configure more than one command bus though. 

Let's say you're integrating a remote accounting system into your application and you'd like to use a separate command bus for just those commands. You can wire up two command buses like this:

You can do this via configuration, like so:

```yaml
tactician:
    commandbus:
        default:    # the "regular" command bus in your application
            middleware:
                - tactician.middleware.validator
                - tactician.middleware.command_handler
        accounting: # the command bus for accounting specific commands
            middleware:
                - tactician.middleware.locking
                - some.middleware.service.to.call.the.remote.accounting.app
                - tactician.commandbus.accounting.middleware.command_handler # Because "tactician.middleware.command_handler" refers to the default bus


```

The configuration defines two buses: "default" and "accounting". These buses will be registered as the
`tactician.commandbus.default` and `tactician.commandbus.accounting` services respectively.

Take note that each bus has now configured their own command handler middleware: `tactician.middleware.command_handler` for the default and `tactician.commandbus.accounting.middleware.command_handler` for the account one. 

If you want, you can also change which command handler is registered to `tactician.commandbus`. You can do this by
setting the `default_bus` value in the configuration, like so:

```yaml
tactician:
    default_bus: accounting
    commandbus:
        default:
            middleware:
                # ...
        accounting:
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
        - { name: tactician.handler, command: Foo\User\RegisterUserCommand, bus: accounting }
```

and you will be able to handle this command only on the accounting bus:
```php
$bus = $container->get('tactician.commandbus.accounting');
$bus->handle(new Foo\User\RegisterUserCommand('my', 'arguments'));
```

## Extra Bundled Middleware

This bundles ships with a few pre-configured middlewares. To enable them, add them to the middlewares list in your bus configuration (see [Configuring Middleware](#configuring-middleware))

### Validator Middleware (tactician.middleware.validator)

This middleware uses Symfony's validator to check the command object before passing it along. In practice, this means you can add any Symfony validator annotations to your command to ensure it's fully correct before execution. This isn't a full replacement for writing your objects in an internally consistent style but it can be very helpful.  

Constraints can be added via configuration or annotations like in default Symfony practices, please refer to [their docs](https://symfony.com/doc/current/validation).

If the command fails, it will throw a [League\Tactician\Bundle\Middleware\InvalidCommandException](src/Middleware/InvalidCommandException.php). This exception also contains the ConstraintViolationList produced by the validator so you can inspect or log the errors yourself. 

### Locking Middleware (tactician.middleware.locking)

This middleware is bundled in Tactician, please refer to [the official documentation](http://tactician.thephpleague.com/plugins/locking-middleware/) for details.

### Logger Middleware (tactician.middleware.logger)

This middleware is bundled in Tactician, please refer to [the official documentation](http://tactician.thephpleague.com/plugins/logger/) for details.

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
