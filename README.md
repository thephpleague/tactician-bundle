# TacticianBundle
Symfony2 Bundle for the Tactician library

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
