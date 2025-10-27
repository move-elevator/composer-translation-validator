# Config file

The Composer Translation Validator can be configured using various configuration file formats
to customize validation behavior, specify paths and control which validators are used.

> [!TIP]
> The configuration schema is validated automatically when using JSON or YAML format files.
>
> See the [JSON Schema](../schema/translation-validator.schema.json) section for more details.

> [!NOTE]
> **DuplicateValuesValidator is disabled by default** (opt-in)
>
> The `DuplicateValuesValidator` is skipped by default to reduce noise in validation results.
> You can enable it by explicitly including it in the `only` option or by setting an empty `skip` array.

See the [Configuration Schema](schema.md) for details on available options.

## Formats

The configuration can be specified in the following formats:

* **JSON** (`.json`)
* **PHP** (`.php`)
* **YAML** (`.yaml` or `.yml`)

## Configuration in PHP file

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

### Enabling DuplicateValuesValidator

To enable the `DuplicateValuesValidator`, explicitly include it in the `only` option:

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
    ->setSkip([]); // Empty skip list enables all validators including DuplicateValuesValidator

return $config;
```

## Configuration in JSON file

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
    "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateValuesValidator",
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

To enable the `DuplicateValuesValidator`:

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

## Configuration in YAML file

Create a YAML file with the following structure:

```yaml
paths:
  - translations/
  - locale/

validators:
  - MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator
  - MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator
  - MoveElevator\ComposerTranslationValidator\Validator\DuplicateValuesValidator
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

To enable the `DuplicateValuesValidator`:

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

## Auto-detection

The plugin automatically searches for configuration files in the following order:

1. `translation-validator.php`
2. `translation-validator.json`
3. `translation-validator.yaml`
4. `translation-validator.yml`
