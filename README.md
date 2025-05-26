# Composer translation validator plugin

A Composer plugin that validates translation files in your project regarding mismatches between language source and target files.

## Features

* Autodetect according source and target files
* Detects missing translations
* Supports the following formats:
  * XLIFF

## Installation

```bash
composer require --dev move-elevator/composer-translation-validator
```

## Usage

```bash
composer validate-translations ./Resources/Private/Language
```
![console.jpg](docs/console.jpg)

## License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE.md).
