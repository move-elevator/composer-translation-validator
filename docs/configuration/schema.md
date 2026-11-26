# Schema Reference

This page documents all available configuration options for the Composer Translation Validator.

## paths

- **Type**: `array<string>`
- **Default**: `[]`
- **Description**: Array of directory paths to scan for translation files. The key is optional, but if it is present it needs at least one non-empty string. Without paths from the configuration file or from the command line, the command fails with `No paths provided.`. Relative paths resolve against the current working directory, not against the location of the configuration file

```yaml
paths:
  - translations/
  - resources/lang/
```

## file-detectors

- **Type**: `array<string>`
- **Default**: `[]` (runs `SuffixFileDetector`, `PrefixFileDetector` and `DirectoryFileDetector` in this fixed order)
- **Description**: Array of file detector class names to use for file grouping. The detectors are applied in the listed order, a later detector only sees the files the earlier ones did not pair. Classes that do not exist or do not implement the detector interface are logged and skipped

```yaml
file-detectors:
  - MoveElevator\ComposerTranslationValidator\FileDetector\SuffixFileDetector
```

Available detectors:
- `PrefixFileDetector` - Groups files like `en.messages.xlf`
- `SuffixFileDetector` - Groups files like `messages.en.yaml`
- `DirectoryFileDetector` - Groups files like `en/messages.php`

`PrefixFileDetector` also claims names like `messages.en.yaml`, which is why `SuffixFileDetector` comes first in the default order.

## only

- **Type**: `array<string>`
- **Default**: `[]`
- **Description**: Array of validator class names to run exclusively. `skip` is ignored when `only` is set. Each entry must be the fully qualified name of an existing class that implements `ValidatorInterface`. An unusable entry is logged and ignored. If no entry is usable, the command fails

```yaml
only:
  - MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator
```

## skip

- **Type**: `array<string>`
- **Default**: `MoveElevator\ComposerTranslationValidator\Validator\DuplicateValuesValidator` and `MoveElevator\ComposerTranslationValidator\Validator\KeyNamingConventionValidator`
- **Description**: Array of validator class names to skip. `DuplicateValuesValidator` and `KeyNamingConventionValidator` stay skipped whatever this list contains, name them in `only` to run them. Each entry must be the fully qualified name of an existing class that implements `ValidatorInterface`. An unusable entry is logged and ignored. If no entry is usable, the command fails

```yaml
skip:
  - MoveElevator\ComposerTranslationValidator\Validator\HtmlTagValidator
```

## exclude

- **Type**: `array<string>`
- **Default**: `[]`
- **Description**: Array of glob patterns for files/directories to exclude from validation. A pattern is matched against the file name and the path relative to the scanned directory (directories above it are ignored), it applies to directories as well and excluded directories are not traversed. The `--exclude` option is appended to this list

```yaml
exclude:
  - "**/backup/**"
  - "**/cache/**"
  - "**/*.bak"
```

## strict

- **Type**: `boolean`
- **Default**: `false`
- **Description**: Whether to treat warnings as errors

```yaml
strict: true
```

## dry-run

- **Type**: `boolean`
- **Default**: `false`
- **Description**: Whether to run in dry-run mode (no errors thrown)

```yaml
dry-run: true
```

## format

- **Type**: `string`
- **Default**: `cli`
- **Options**: `cli`, `json`, `github`
- **Description**: Output format for validation results. The `--format` option replaces this setting

```yaml
format: json
```

## verbose

- **Type**: `boolean`
- **Default**: `false`
- **Description**: Whether to enable verbose output. Has the same effect as `-v`. A verbosity option on the command line (`-q`, `-v`, `-vv`, `-vvv`) takes precedence

```yaml
verbose: true
```

## validator-settings

- **Type**: `object`
- **Default**: `{}`
- **Description**: Per-validator configuration options. The keys are short validator class names without namespace, a fully qualified name is rejected by the schema. Only `KeyCountValidator`, `KeyDepthValidator` and `KeyNamingConventionValidator` read settings, entries for other validators are ignored

```yaml
validator-settings:
  KeyCountValidator:
    threshold: 500
  KeyDepthValidator:
    threshold: 5
  KeyNamingConventionValidator:
    convention: snake_case
```

### KeyCountValidator Settings

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `threshold` | `integer` | `300` | Maximum number of keys before warning |

### KeyDepthValidator Settings

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `threshold` | `integer` | `8` | Maximum nesting depth before warning |

### KeyNamingConventionValidator Settings

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `convention` | `string` | auto-detect | Naming convention to enforce |
| `custom_pattern` | `string` | - | Custom regex pattern, overrides `convention` |

Supported values for `convention`:
- `snake_case` - `user_name`, `form_submit`
- `camelCase` - `userName`, `formSubmit`
- `kebab-case` - `user-name`, `form-submit`
- `PascalCase` - `UserName`, `FormSubmit`

An invalid `convention` or `custom_pattern` only logs a warning and the setting is not applied.

`custom_pattern` is a separate setting, not a `convention` value. Set one pattern, the examples are alternatives:

Only lowercase letters and numbers:

```yaml
validator-settings:
  KeyNamingConventionValidator:
    custom_pattern: '/^[a-z0-9]+$/'
```

Specific prefix requirement:

```yaml
validator-settings:
  KeyNamingConventionValidator:
    custom_pattern: '/^app\.[a-z][a-z0-9_]*$/'
```

Maximum length constraint:

```yaml
validator-settings:
  KeyNamingConventionValidator:
    custom_pattern: '/^[a-z][a-z0-9_]{0,29}$/'
```
