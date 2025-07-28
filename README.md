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
* Supports various [translation formats](#supported-translation-file-formats)
* Provides multiple [validators](#translation-validators)

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

![console.png](docs/console.png)

The command `validate-translations` can be used to validate translation files in your project. It will automatically detect the translation files based on the supported formats and run the configured validators.

```bash
composer validate-translations [<path>...] [--dry-run] [--strict] [--format|-f <cli|json>] [--skip|-s <VALIDATOR>...] [--only|-o <VALIDATOR>...] [--recursive|-r] [--verbose|-v] [--config|-c <CONFIG>]```
```

| Argument / Option | Shortcut | Description                                                                                       |
|-------------------|----------|---------------------------------------------------------------------------------------------------|
| `<path>`          |          | (Optional) Path to the translation files or directories to validate (can be used multiple times). |
| `--format`        | `-f`     | Sets the output format (`cli`, `json`).                                                           |
| `--skip`          | `-s`     | Skips specific validators (can be used multiple times).                                           |
| `--only`          | `-o`     | Runs only the specified validators (can be used multiple times).                                  |
| `--recursive`     | `-r`     | Search for translation files recursively in subdirectories                                  |
| `--verbose`       | `-v`     | Shows additional output for detailed information.                                                 |
| `--strict`        |          | Enables strict mode, treating warnings as errors.                                                 |
| `--dry-run`       |          | Runs the validation in test mode without saving changes.                                          |
| `--config`        | `-c`     | Path to a configuration file (e.g. `translation-validator.yaml`).                                 |

Find more information about store a [config file](docs/config-file.md).

## 📝 Documentation

### Supported File Formats

| Format | Frameworks | Examples |
|--------|------------|----------|
| **[XLIFF](docs/file-detector.md#xliff-xml-localization-interchange-file-format)** | TYPO3 CMS | `locallang.xlf`, `de.locallang.xlf` |
| **[YAML](docs/file-detector.md#yaml-yaml-aint-markup-language)** | Symfony | `messages.en.yaml`, `messages.de.yaml` |
| **[JSON](docs/file-detector.md#json-javascript-object-notation)** | Laravel, Symfony | `messages.en.json`, `messages.de.json` |
| **[PHP](docs/file-detector.md#php-arrays)** | Laravel, Symfony | `en/messages.php`, `messages.en.php` |

See detailed format [documentation](docs/file-detector.md) and file grouping.

### Translation Validators

The following translation validators are available:

| Validator | Description | Result |
|-----------|-------------|---------|
| **[DuplicateKeysValidator](docs/validators.md#duplicatekeysvalidator)** | Catches duplicate keys within files | <span style="color:red">ERROR</span> |
| **[DuplicateValuesValidator](docs/validators.md#duplicatevaluesvalidator)** | Finds identical translation values | <span style="color:orange">WARNING</span> |
| **[EmptyValuesValidator](docs/validators.md#emptyvaluesvalidator)** | Detects empty or whitespace-only values | <span style="color:orange">WARNING</span> |
| **[EncodingValidator](docs/validators.md#encodingvalidator)** | Validates UTF-8 encoding and Unicode issues | <span style="color:orange">WARNING</span> |
| **[HtmlTagValidator](docs/validators.md#htmltagvalidator)** | Ensures HTML tag consistency across languages | <span style="color:orange">WARNING</span> |
| **[KeyNamingConventionValidator](docs/validators.md#keynamingconventionvalidator)** | Enforces key naming patterns (requires config) | <span style="color:orange">WARNING</span> |
| **[MismatchValidator](docs/validators.md#mismatchvalidator)** | Finds missing translations between files | <span style="color:orange">WARNING</span> |
| **[PlaceholderConsistencyValidator](docs/validators.md#placeholderconsistencyvalidator)** | Validates placeholder patterns | <span style="color:orange">WARNING</span> |
| **[XliffSchemaValidator](docs/validators.md#xliffschemavalidator)** | Validates XLIFF against XML schemas | <span style="color:red">ERROR</span> |

View detailed [documentation](docs/validators.md) with examples.

### Validator-Specific Configuration

Some validators support additional configuration options. For detailed configuration instructions and examples, see [Validator Configuration](docs/validator-configuration.md).

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).
