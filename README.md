<div align="center">

# Composer Translation Validator

[![Coverage](https://img.shields.io/coverallsCoverage/github/move-elevator/composer-translation-validator?logo=coveralls)](https://coveralls.io/github/move-elevator/composer-translation-validator)
[![CGL](https://img.shields.io/github/actions/workflow/status/move-elevator/composer-translation-validator/cgl.yml?label=cgl&logo=github)](https://github.com/move-elevator/composer-translation-validator/actions/workflows/cgl.yml)
[![Tests](https://img.shields.io/github/actions/workflow/status/move-elevator/composer-translation-validator/tests.yml?label=tests&logo=github)](https://github.com/move-elevator/composer-translation-validator/actions/workflows/tests.yml)
[![Supported PHP Versions](https://img.shields.io/packagist/dependency-v/move-elevator/composer-translation-validator/php?logo=php)](https://packagist.org/packages/move-elevator/composer-translation-validator)

</div>

A Composer plugin that validates translation files in your project.
Provides a command `validate-translations` to check for translations mismatches, duplicates, schema validation and more.
Supports XLIFF, YAML, JSON and PHP translation files.

## ✨ Features

* Autodetect coherent language files
* Supports various [translation file formats](#supported-file-formats)
* Provides multiple [validators](#translation-validators)
* Configurable via separate [configuration files](docs/config-file.md)

## 🔥 Installation

[![Packagist](https://img.shields.io/packagist/v/move-elevator/composer-translation-validator?label=version&logo=packagist)](https://packagist.org/packages/move-elevator/composer-translation-validator)
[![Packagist Downloads](https://img.shields.io/packagist/dt/move-elevator/composer-translation-validator?color=brightgreen)](https://packagist.org/packages/move-elevator/composer-translation-validator)


```bash
composer require --dev move-elevator/composer-translation-validator
```

## 📊 Usage

Validate your translation files by running the command:

```bash
composer validate-translations ./translations
```

![console.png](docs/images/console.png)

The command `validate-translations` can be used to validate translation files in your project. It will automatically detect the translation files based on the [supported formats](#supported-file-formats) and run the configured [validators]((#translation-validators)). See the [console command documentation](docs/console-command.md) for more details.

## 📝 Documentation

### Supported File Formats

Translations will be detected and grouped by the following file formats (regarding the associated frameworks):

| Format | Frameworks | Example files                          |
|--------|------------|----------------------------------------|
| [XLIFF](docs/file-detector.md#xliff-xml-localization-interchange-file-format) | TYPO3 CMS | `locallang.xlf`, `de.locallang.xlf`    |
| [YAML](docs/file-detector.md#yaml-yaml-aint-markup-language) | Symfony | `messages.en.yaml`, `messages.de.yaml` |
| [JSON](docs/file-detector.md#json-javascript-object-notation) | Laravel, Symfony | `messages.en.json`, `messages.de.json` |
| [PHP](docs/file-detector.md#php-arrays) | Laravel, Symfony | `en/messages.php`, `messages.en.php`   |

See detailed [file format and file detection documentation](docs/file-detector.md) with examples.

### Translation Validators

The following translation validators are available (and enabled by default):

| Validator | Description |
|-----------|-------------|
| [DuplicateKeysValidator](docs/validators.md#duplicatekeysvalidator) | Catches duplicate keys within files |
| [DuplicateValuesValidator](docs/validators.md#duplicatevaluesvalidator) | Finds identical translation values |
| [EmptyValuesValidator](docs/validators.md#emptyvaluesvalidator) | Detects empty or whitespace-only values |
| [EncodingValidator](docs/validators.md#encodingvalidator) | Validates UTF-8 encoding and Unicode issues |
| [HtmlTagValidator](docs/validators.md#htmltagvalidator) | Ensures HTML tag consistency across languages |
| [KeyCountValidator](docs/validators.md#keycountvalidator) | Warns when files exceed a configurable key count threshold |
| [KeyDepthValidator](docs/validators.md#keydepthvalidator) | Warns when translation keys have excessive nesting depth |
| [KeyNamingConventionValidator](docs/validators.md#keynamingconventionvalidator) | Enforces key naming patterns (requires config) |
| [MismatchValidator](docs/validators.md#mismatchvalidator) | Finds missing translations between files |
| [PlaceholderConsistencyValidator](docs/validators.md#placeholderconsistencyvalidator) | Validates placeholder patterns |
| [XliffSchemaValidator](docs/validators.md#xliffschemavalidator) | Validates XLIFF against XML schemas |

View detailed [validator documentation](docs/validators.md) with examples.

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).
