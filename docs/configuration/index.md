# Configuration Overview

The Composer Translation Validator can be configured in multiple ways to customize validation behavior.

## Configuration Methods

### 1. Command Line Arguments

Pass options directly to the command:

```bash
composer validate-translations ./translations --strict --format json
```

See [CLI Reference](/reference/cli) for all available options.

### 2. Configuration File

Create a dedicated configuration file in your project root. The plugin supports multiple formats:

- `translation-validator.php` - PHP configuration
- `translation-validator.json` - JSON configuration
- `translation-validator.yaml` - YAML configuration
- `translation-validator.yml` - YAML configuration (alternative extension)

See [Configuration File](/configuration/config-file) for detailed examples.

### 3. composer.json Reference

Specify a custom configuration file path in `composer.json`:

```json
{
  "extra": {
    "translation-validator": {
      "config-file": "./config/translation-validator.yaml"
    }
  }
}
```

## Auto-Detection

The plugin automatically searches for configuration files in this order:

1. `translation-validator.php`
2. `translation-validator.json`
3. `translation-validator.yaml`
4. `translation-validator.yml`

The first file found will be used.

## Configuration Priority

When the same option is specified in multiple places, this priority applies:

1. **Command line arguments** (highest priority)
2. **Configuration file**
3. **Default values** (lowest priority)

## DuplicateValuesValidator

::: info
The `DuplicateValuesValidator` is disabled by default to reduce noise in validation results, as duplicate values are often intentional (e.g., common button labels like "OK" or "Cancel").
:::

To enable it, either:
- Use `--only` to explicitly include it
- Set `skip: []` in your configuration file

## Next Steps

- [Configuration File](/configuration/config-file) - Detailed format examples
- [Schema Reference](/configuration/schema) - All available options
