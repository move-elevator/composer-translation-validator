# Configuration File

The Composer Translation Validator can be configured using various configuration file formats to customize validation behavior, specify paths and control which validators are used.

::: tip
The configuration schema is validated automatically when using JSON or YAML format files.
:::

## Supported Formats

The configuration can be specified in the following formats:

- **PHP** (`.php`) - Full programmatic control
- **JSON** (`.json`) - Simple structured format
- **YAML** (`.yaml` or `.yml`) - Human-readable format

## PHP Configuration

Create a PHP file that returns a configured `TranslationValidatorConfig` instance:

```php
<?php

declare(strict_types=1);

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;

$config = new TranslationValidatorConfig();
$config->setPaths(['translations/', 'locale/'])
    ->setFormat('json')
    ->setStrict(true)
    ->setOnly([
        'MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator',
        'MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator',
    ])
    ->setExclude(['**/backup/**', '**/cache/**']);

return $config;
```

### Enabling DuplicateValuesValidator in PHP

Explicitly include it in the `only` option:

```php
<?php

declare(strict_types=1);

use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\Validator\DuplicateValuesValidator;

$config = new TranslationValidatorConfig();
$config->setPaths(['translations/'])
    ->setOnly([
        DuplicateValuesValidator::class,
    ]);

return $config;
```

Or remove it from the skip list:

```php
$config = new TranslationValidatorConfig();
$config->setPaths(['translations/'])
    ->setSkip([]); // Empty skip list enables all validators

return $config;
```

## JSON Configuration

Create a JSON file with the following structure:

```json
{
  "paths": [
    "translations/",
    "locale/"
  ],
  "validators": [
    "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator",
    "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator",
    "MoveElevator\\ComposerTranslationValidator\\Validator\\XliffSchemaValidator"
  ],
  "file-detectors": [
    "MoveElevator\\ComposerTranslationValidator\\FileDetector\\SuffixFileDetector"
  ],
  "only": [
    "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator"
  ],
  "skip": [
    "MoveElevator\\ComposerTranslationValidator\\Validator\\XliffSchemaValidator"
  ],
  "exclude": [
    "**/backup/**",
    "**/cache/**",
    "**/*.bak"
  ],
  "strict": true,
  "dry-run": false,
  "format": "json",
  "verbose": false
}
```

### Enabling DuplicateValuesValidator in JSON

Set an empty skip array:

```json
{
  "paths": ["translations/"],
  "skip": []
}
```

Or use the `only` option:

```json
{
  "paths": ["translations/"],
  "only": [
    "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateValuesValidator"
  ]
}
```

## YAML Configuration

Create a YAML file with the following structure:

```yaml
paths:
  - translations/
  - locale/

validators:
  - MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator
  - MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator
  - MoveElevator\ComposerTranslationValidator\Validator\XliffSchemaValidator

file-detectors:
  - MoveElevator\ComposerTranslationValidator\FileDetector\SuffixFileDetector

only:
  - MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator

skip:
  - MoveElevator\ComposerTranslationValidator\Validator\XliffSchemaValidator

exclude:
  - "**/backup/**"
  - "**/cache/**"
  - "**/*.bak"

strict: true
dry-run: false
format: cli
verbose: false
```

### Enabling DuplicateValuesValidator in YAML

Set an empty skip array:

```yaml
paths:
  - translations/
skip: []
```

Or use the `only` option:

```yaml
paths:
  - translations/
only:
  - MoveElevator\ComposerTranslationValidator\Validator\DuplicateValuesValidator
```

## Validator Settings

Some validators accept additional configuration options:

```yaml
validator-settings:
  KeyCountValidator:
    threshold: 500  # Warn when files have more than 500 keys
  KeyDepthValidator:
    threshold: 5    # Warn when keys have more than 5 nesting levels
  KeyNamingConventionValidator:
    convention: snake_case  # Enforce snake_case naming
```

See the [Validators Reference](/reference/validators) for validator-specific options.

## Configuration in composer.json

You can also specify the path to a configuration file in your `composer.json`:

```json
{
  "extra": {
    "translation-validator": {
      "config-file": "./translation-validator.json"
    }
  }
}
```

## Auto-Detection Order

The plugin automatically searches for configuration files in this order:

1. `translation-validator.php`
2. `translation-validator.json`
3. `translation-validator.yaml`
4. `translation-validator.yml`
