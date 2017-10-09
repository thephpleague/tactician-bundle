## 1.1 (2017-XX-XX)

New features
- DI tags can now use "typehints: true" instead of mapping every command by hand.
- You can specify custom mapping strategies.

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
