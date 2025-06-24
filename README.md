# Composer translation validator plugin

A Composer plugin that validates translation files in your project regarding mismatches between language files.

## Features

* Autodetect according to language files
* Detects missing translations
* Supports the following formats:
  * XLIFF - `*.xlf`, `*.xliff`
* Support the following validators:
  * [MismatchValidator.php](src/Validator/MismatchValidator.php)
  * [DuplicatesValidator.php](src/Validator/DuplicatesValidator.php)

## Installation

```bash
composer require --dev move-elevator/composer-translation-validator
```

## Usage

```bash
composer validate-translations ./Resources/Private/Language
```
![console.jpg](docs/console.jpg)

See [ValidateTranslationCommand.php](src/Command/ValidateTranslationCommand.php) for further details.

## License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE.md).
