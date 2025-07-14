<div align="center">

# Composer Translation Validator

[![Coverage](https://img.shields.io/coverallsCoverage/github/move-elevator/composer-translation-validator?logo=coveralls)](https://coveralls.io/github/move-elevator/composer-translation-validator)
[![CGL](https://img.shields.io/github/actions/workflow/status/move-elevator/composer-translation-validator/cgl.yml?label=cgl&logo=github)](https://github.com/move-elevator/composer-translation-validator/actions/workflows/cgl.yml)
[![Tests](https://img.shields.io/github/actions/workflow/status/move-elevator/composer-translation-validator/tests.yml?label=tests&logo=github)](https://github.com/move-elevator/composer-translation-validator/actions/workflows/tests.yml)
[![Supported PHP Versions](https://img.shields.io/packagist/dependency-v/move-elevator/composer-translation-validator/php?logo=php)](https://packagist.org/packages/move-elevator/composer-translation-validator)

</div>

A Composer plugin that validates translation files in your project.
Provides a command `validate-translations` to check for translations mismatches, duplicates and schema validation.

## ✨ Features

* Autodetect coherent language files
* Supports various translation formats
* Provides multiple validators

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
composer validate-translations [<path>] [--dry-run] [--strict] [-f|--format cli|json] [-s|--skip VALIDATOR] [-o|--only VALIDATOR] [-v|--verbose]
```

| Argument / Option         | Shortcut | Description                                                                                       |
|--------------------------|----------|---------------------------------------------------------------------------------------------------|
| `<path>`                 |          | (Optional) Path to the translation files or directories to validate (can be used multiple times). |
| `--format`               | `-f`     | Sets the output format (`cli`, `json`).                                                           |
| `--skip`                 | `-s`     | Skips specific validators (can be used multiple times).                                           |
| `--only`                 | `-o`     | Runs only the specified validators (can be used multiple times).                                  |
| `--verbose`              | `-v`     | Shows additional output for detailed information.                                                 |
| `--strict`               |          | Enables strict mode, treating warnings as errors.                                                 |
| `--dry-run`              |          | Runs the validation in test mode without saving changes.                                          |
| `--config`               | `-c`     | Path to a configuration file (e.g. `translation-validator.yaml`).                                 |

Find more information about store a [config file](docs/config-file.md).

## 📝 Documentation

### Supported Formats

The plugin supports the following translation file formats (and targets the following frameworks):

| Format                                       | Description                                                                                                  | Framework | Example files                          |
|----------------------------------------------|--------------------------------------------------------------------------------------------------------------|-----------|----------------------------------------|
| [XLIFF](https://en.wikipedia.org/wiki/XLIFF) | Supports source/target translations in xliff language files. | [TYPO3 CMS](https://typo3.org/)          | `locallang.xlf`, `de.locallang.xlf`    |
| [Yaml](https://en.wikipedia.org/wiki/YAML)   | Supports yaml language files.                     | [Symfony Framework](https://symfony.com/)          | `messages.en.yaml`, `messages.de.yaml` |
| [JSON](https://en.wikipedia.org/wiki/JSON)   | Supports JSON language files with nested key support.                     | [Laravel](https://laravel.com/) / [Symfony](https://symfony.com/)          | `messages.en.json`, `messages.de.json` |

> [!NOTE]
> The translation files will be grouped to file sets based on the file name prefix or suffix. For example, `locallang.xlf` and `de.locallang.xlf` will be grouped together as they share the same prefix (`locallang`), while `messages.en.yaml` and `messages.de.yaml` will be grouped by their suffix (`.en`, `.de`). See the [File Detectors](docs/file-detector.md) for more details.

### Validators

The following validators are available:

| Validator                  | Function                                                                                                                                                                 | Supports    | Throws  |
|----------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-------------|---------|
| `MismatchValidator`        | This validator checks for keys that are present in some files but not in others. It helps to identify mismatches in translation keys across different translation files. | XLIFF, YAML, JSON | WARNING   |
| `DuplicateKeysValidator`   | This validator checks for duplicate keys in translation files.                                                                                                           | XLIFF       | ERROR   |
| `DuplicateValuesValidator` | This validator checks for duplicate values in translation files.                                                                                                         | XLIFF, YAML, JSON     | WARNING |
| `XliffSchemaValidator`     | Validates the XML schema of translation files against the XLIFF standard. See available [schemas](https://github.com/symfony/translation/tree/6.4/Resources/schemas).    | XLIFF       | ERROR   |
| `EmptyValuesValidator`      | Finds empty or whitespace-only translation values.                                                                                                                       | XLIFF, YAML, JSON              | WARNING        |
| `PlaceholderConsistencyValidator`                                | Validates placeholder consistency across files.                                                                                                                          | XLIFF, YAML, JSON     | WARNING |


## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).
