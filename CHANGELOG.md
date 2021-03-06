# Changelog

All notable changes to this project will be documented in this file.

## [0.3.6] 2020-02-12
### Change
Response json validation
> The validaton json used in_array and not array_key_exists for search key. The error are fixed for this version.
> The syntax error for monthly installments is corrected.

## [0.3.5] 2020-02-07
This version only contain a better error handler for give in a better error information to final customer and the
development area.
### Added
Both in the Response class and the base abstract class for card model has a custom validation functions.
> This validation don't return nothing, but throw a Exception (ClientException class) for handling the errors in the http client flow.
> The base abstract PagoFacil exception class are implemented a custom error code not numeric, these codes are a string.
### Changed
> The logger instance in the response class and Http client Class are passed by dependency injection.
### Removed
- Logger implementen into the directory Client's class
> The logger implementation is removed in the Response and Http client class, and now, the responsability is 
> the class that implements it

## [v0.3.x]
### Added
- Exceptions for better error handling.
> Added new abstract base exception that extends to Magento's LocalizedException, and created more exceptions that
> extends of this abstract class exception.
- Interfaces for http handling.
> The interfaces for pagofacil's http handlers, extends to [psr 7] and [psr 18]
- new classes for custom pagofacil handling.
> The classes for interaction with http protocols and the pagofacil.net system, are created by implementing the extendend
> interfaces of psr 7 and 18.
- Used a class user for all configuration.
> A class is used to encapsulate the http client base configuration.
- A custom model config provider.
> Used a custom config provider for setting the data to card application form.
- The functionality for the magento model is extended and segregated.
> New interfaces are created for the card model and its execution.
>  A trait is created to be able to access the configuration of the system, 
> as well as abtract classes for the management of different behaviors.
- a custom template for form card payment.
- A trait for create a custom zend logger.
- The Registry pattern.
### Changed
- Refactoring

> The responsibilities of both the card payment model and the implementations on psr 7 and 18 have been segregated. 
> This has reduced the percentage of complexity, cyclometric complexity and maintenance of the project.
- Readme file
> The readme file are added new commands in the install section, this commands are about permitions on 
> Magento's directory. 
> The official documentation of magento are added like reference.
- Error trying to clean register pattern implementation.
> when trying to clean the register (that is bad implementation) the alloc of
> memory is empty and throw a exception that intercept the magento platform
> and show the user and stop the payment flow
### Fixed
### Deprecated
### Removed
### Security

[psr 7]: https://www.php-fig.org/psr/psr-7/
[psr 18]: https://www.php-fig.org/psr/psr-18/
