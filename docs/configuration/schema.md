# Schema Reference

This page documents all available configuration options for the Composer Translation Validator.

## paths

- **Type**: `array<string>`
- **Default**: `[]`
- **Description**: Array of directory paths to scan for translation files

```yaml
paths:
  - translations/
  - resources/lang/
```

## validators

- **Type**: `array<string>`
- **Default**: `[]` (uses all available validators)
- **Description**: Array of validator class names to use for validation

```yaml
validators:
  - MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator
  - MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator
```

## file-detectors

- **Type**: `array<string>`
- **Default**: `[]` (uses automatic detection)
- **Description**: Array of file detector class names to use for file grouping

```yaml
file-detectors:
  - MoveElevator\ComposerTranslationValidator\FileDetector\SuffixFileDetector
```

Available detectors:
- `PrefixFileDetector` - Groups files like `en.messages.xlf`
- `SuffixFileDetector` - Groups files like `messages.en.yaml`
- `DirectoryFileDetector` - Groups files like `en/messages.php`

## only

- **Type**: `array<string>`
- **Default**: `[]`
- **Description**: Array of validator class names to run exclusively (overrides `validators`)

```yaml
only:
  - MoveElevator\ComposerTranslationValidator\Validator\MismatchValidator
```

## skip

- **Type**: `array<string>`
- **Default**: `['DuplicateValuesValidator']`
- **Description**: Array of validator class names to skip

```yaml
skip:
  - MoveElevator\ComposerTranslationValidator\Validator\HtmlTagValidator
```

## exclude

- **Type**: `array<string>`
- **Default**: `[]`
- **Description**: Array of glob patterns for files/directories to exclude from validation

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
- **Description**: Output format for validation results

```yaml
format: json
```

## verbose

- **Type**: `boolean`
- **Default**: `false`
- **Description**: Whether to enable verbose output

```yaml
verbose: true
```

## validator-settings

- **Type**: `object`
- **Default**: `{}`
- **Description**: Per-validator configuration options

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
| `custom_pattern` | `string` | - | Custom regex pattern |

Supported conventions:
- `snake_case` - `user_name`, `form_submit`
- `camelCase` - `userName`, `formSubmit`
- `kebab-case` - `user-name`, `form-submit`
- `PascalCase` - `UserName`, `FormSubmit`

Custom pattern examples:
```yaml
validator-settings:
  KeyNamingConventionValidator:
    # Only lowercase letters and numbers
    custom_pattern: '/^[a-z0-9]+$/'

    # Specific prefix requirement
    custom_pattern: '/^app\.[a-z][a-z0-9_]*$/'

    # Maximum length constraint
    custom_pattern: '/^[a-z][a-z0-9_]{0,29}$/'
```
