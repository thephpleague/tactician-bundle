## 1.1.6 (2018-12-16)
- #109 - Remove deprecation notices for Symfony 4.2, increase test matrix
- #108 - Move handler service tag into constant for easier PHP-based mapping

## 1.1.5 (2018-04-25)
- #102 & #103 - Fix bug registering new Doctrine Rollback Only middleware

## 1.1.4 (2018-04-24)
- #100 - Allow autowiring the default Command Bus instance based on a `CommandBus` typehint
- #101 - Register the new Doctrine Rollback Only middleware

## 1.1.3 (2018-03-03)
- #99 - Add class name inflector support
- #87 - Add Tactician logger support

## 1.1.2 (2017-12-08)
Bugfixes
- #92 Exclude interfaces from auto routing in the Typehints mapping strategy
- #91 Fix container visibility issues in Symfony 4

BC Breaks
- None

## 1.1.1 (2017-11-11)
Bugfixes
- #82 Fixed trying to use a bus when absolutely no config was defined

Other
- Improved version constraints
- Updated docs

BC Breaks
- None

## 1.1 (2017-11-05)

New features
- DI tags can now use "typehints: true" instead of mapping every command by hand.
- You can specify custom mapping strategies.
- The ```debug:tactician``` command prints command to service mappings.

BC Breaks:
- Different exceptions are now be thrown during the container build phase.
- Remove tactician.commandbus.class container parameter.
- Removed some container parameters that were used for internal state passing (tactician.commandbus.ids, etc)

Other:
- Internals and test suite have been significantly cleaned up.

## 1.0.0 (2017-08-28)
- No changes since RC1

## 1.0.0-RC1 (2017-08-03)

BC Breaks:
- PHP 7 is now required
- `tactician.middleware.command_handler` now only contains the default bus handlers. Check [#83](https://github.com/thephpleague/tactician-bundle/pull/83/files) to see how to update your config accordingly.

New features:
- Allow multiple command buses, each with separated handlers and different method inflectors
- Added security middleware
- Allow command handlers to be private in the service container
- Better Symfony 3.x support
- Improved test suite

## 0.4.1 (2016-04-21)

BC Breaks:
- None

New features:
- [#15](https://github.com/thephpleague/tactician-bundle/pull/15) Removed block against using custom inflectors

## Pre-0.4.0
It worked back then, okay?
