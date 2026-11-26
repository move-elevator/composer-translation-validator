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

The plugin looks for the configuration file in this order and uses the first match. Configurations are not merged.

1. The file passed with `--config`.
2. The `extra.translation-validator.config-file` entry of the `composer.json` in the working directory. A relative path resolves against the directory of `composer.json`.
3. Auto-detection in the working directory, first match wins:
   1. `translation-validator.json`
   2. `translation-validator.yaml`
   3. `translation-validator.yml`
4. The built-in defaults, if none of the above exists.

PHP configuration files are **not** auto-detected, because they are executed when loaded. Auto-loading one from an untrusted working directory would allow arbitrary code execution. To use a PHP configuration file, reference it explicitly via the `--config` option or the `composer.json` `config-file` entry.

## Configuration Priority

When the same option is specified in multiple places, this priority applies:

1. **Command line arguments** (highest priority)
2. **Configuration file**
3. **Default values** (lowest priority)

How a command line argument combines with the configuration file depends on the option:

| Option | Combination |
|--------|-------------|
| `--exclude` | Appended to `exclude` from the configuration file |
| `--strict`, `--dry-run` | Can only switch the setting on. Without the flag, the value from the configuration file applies |
| `--only`, `--skip` | Replace `only` and `skip` from the configuration file |
| `--format` | Replaces `format` from the configuration file. Without the option, the configuration value applies |
| `<path>` arguments | Replace `paths` from the configuration file |

Relative paths resolve against the current working directory, not against the location of the configuration file.

## Opt-In Validators

::: info
The `DuplicateValuesValidator` and the `KeyNamingConventionValidator` are disabled by default to reduce noise in validation results:

- **DuplicateValuesValidator** — duplicate values are often intentional (e.g., common button labels like "OK" or "Cancel").
- **KeyNamingConventionValidator** — auto-detected naming conventions frequently produce false positives on mixed but intentional key styles.
:::

To enable one of them, either:
- Use `--only` to explicitly include it
- Set `skip: []` in your configuration file (enables all validators)

## Next Steps

- [Configuration File](/configuration/config-file) - Detailed format examples
- [Schema Reference](/configuration/schema) - All available options
