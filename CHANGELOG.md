# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2025-07-24

- feat: add file headers to all PHP files for licensing and attribution
- build: add initial renovate configuration file
- fix: update dependencies and widen version constraints

## [1.0.1] - 2025-07-17

- fix: simplify getContentByKey method by removing unused attribute parameter

## [1.0.0] - 2025-07-15

- refactor: enhance validation output to differentiate between errors and warnings
- feat: implement ParserCache for caching parser instances and add cache statistics
- feat: add configuration files and schema validation for translation validator
- feat: enhance warning messages for strict mode in validation renderers
- refactor: rename SchemaValidator to XliffSchemaValidator and skip unsupported xliff version error
- feat: implement configuration factory and file reader for translation validator
- build: update test configurations for PHP 8.1, Symfony 5.x and Composer versions
- style: improve code formatting and readability across multiple files
- feat: add EmptyValuesValidator to detect empty or whitespace-only translation values
- feat: add PlaceholderConsistencyValidator for validating placeholder consistency across translation files
- feat: add support for JSON translation files and update validators
- feat: add support for PHP translation files and update validators
- feat: add EncodingValidator for file encoding and JSON syntax validation
- build: update phpstan configuration to level 7
- docs: update README to clarify translation validation features and improve formatting
- feat: add recursive option for validating translation files

## [0.5.0] - 2025-07-08

- refactor: enhance output rendering
- refactor: simplify validation process by introducing ValidationRun and FileSet classes
- feat: add validation statistics

## [0.4.0] - 2025-07-05

- feat: add skip/only options to command
- feat: add duplicate values validator
- fix: symfony dependency issue
- feat: improved output handling
- feat: add JSON output
- feat: introduce result type

## [0.3.0] - 2025-06-28

- feat: add Schema validator
- build: add CGL checks
- refactor: validators
- style: enhance console output
- feat: add Yaml support
- build: add unit tests

## [0.2.0] - 2025-06-24

- feat: add Duplicates and Mismatch validators

## [0.1.1] - 2025-06-15

- fix: update Symfony package versions to support 7.0

## [0.1.0] - 2025-05-28

Initial release

[1.0.1]: https://github.com/move-elevator/composer-translation-validator/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/move-elevator/composer-translation-validator/compare/0.5.0...1.0.0
[0.5.0]: https://github.com/move-elevator/composer-translation-validator/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/move-elevator/composer-translation-validator/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/move-elevator/composer-translation-validator/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/move-elevator/composer-translation-validator/compare/0.1.1...0.2.0
[0.1.1]: https://github.com/move-elevator/composer-translation-validator/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/move-elevator/composer-translation-validator/tree/0.1.0
