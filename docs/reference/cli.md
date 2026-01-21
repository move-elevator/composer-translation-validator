# CLI Reference

The `validate-translations` command is the main interface for the Composer Translation Validator.

## Synopsis

```bash
composer validate-translations [<path>...] [options]
```

## Arguments

| Argument | Description |
|----------|-------------|
| `<path>` | Path to the translation files or directories to validate. Can be used multiple times. Optional if paths are defined in a configuration file. |

## Options

| Option | Shortcut | Description |
|--------|----------|-------------|
| `--format` | `-f` | Sets the output format: `cli`, `json`, or `github` |
| `--skip` | `-s` | Skips specific validators (can be used multiple times) |
| `--only` | `-o` | Runs only the specified validators (can be used multiple times) |
| `--recursive` | `-r` | Search for translation files recursively in subdirectories |
| `--exclude` | `-e` | Exclude files matching glob patterns, comma-separated |
| `--verbose` | `-v` | Shows additional output for detailed information |
| `--strict` | | Enables strict mode, treating warnings as errors |
| `--dry-run` | | Runs validation without failing on errors |
| `--config` | `-c` | Path to a configuration file |

::: warning
Either a path to translation files must be provided as a command argument or within the configuration file. If no path is provided, the validator will abort.
:::

## Examples

### Basic Validation

```bash
composer validate-translations ./translations
```

### Multiple Paths

```bash
composer validate-translations ./translations ./resources/lang
```

### Recursive Search

```bash
composer validate-translations ./translations --recursive
```

### With Verbose Output

```bash
composer validate-translations ./translations -v
```

### Strict Mode

```bash
composer validate-translations ./translations --strict
```

### JSON Output

```bash
composer validate-translations ./translations --format json
```

### GitHub Actions Format

```bash
composer validate-translations ./translations --format github
```

### Exclude Patterns

```bash
composer validate-translations ./translations --exclude "**/backup/**,**/*.bak"
```

### Run Specific Validators

```bash
composer validate-translations ./translations \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator"
```

### Skip Validators

```bash
composer validate-translations ./translations \
  --skip "MoveElevator\\ComposerTranslationValidator\\Validator\\HtmlTagValidator"
```

### Multiple Validators

```bash
composer validate-translations ./translations \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator" \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator"
```

### Using a Configuration File

```bash
composer validate-translations --config ./translation-validator.yaml
```

### Dry Run Mode

```bash
composer validate-translations ./translations --dry-run
```

## Exit Codes

| Code | Description |
|------|-------------|
| `0` | Validation passed (no errors) |
| `1` | Validation failed (errors found) |

In `--dry-run` mode, the exit code is always `0` regardless of validation results.

## Output Formats

### CLI Format (Default)

Human-readable output with colored indicators:

```
translations/messages.en.yaml

  MismatchValidator
    - Error  the translation key `delete` is missing but present in other files

[ERROR] Language validation failed with errors.
```

### JSON Format

Machine-readable JSON output:

```json
{
  "success": false,
  "files": [
    {
      "path": "translations/messages.en.yaml",
      "issues": [
        {
          "validator": "MismatchValidator",
          "type": "error",
          "message": "the translation key `delete` is missing"
        }
      ]
    }
  ]
}
```

### GitHub Format

Outputs GitHub Actions workflow commands for annotations:

```
::error file=translations/messages.en.yaml::the translation key `delete` is missing
```

## See Also

- [Configuration File](/configuration/config-file)
- [Validators Reference](/reference/validators)
