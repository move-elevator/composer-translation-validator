# CLI Reference

The `validate-translations` command is the main interface for the Composer Translation Validator.

## Synopsis

```bash
composer validate-translations [<path>...] [options]
```

The command is also available under the alias `vt`.

## Arguments

| Argument | Description |
|----------|-------------|
| `<path>` | Path to the translation files or directories to validate. Can be used multiple times. Optional if paths are defined in a configuration file. |

## Options

| Option | Shortcut | Description |
|--------|----------|-------------|
| `--format` | `-f` | Sets the output format: `cli`, `json`, or `github` |
| `--skip` | `-s` | Skips specific validators (FQCN, comma-separated) |
| `--only` | `-o` | Runs only the specified validators (FQCN, comma-separated) |
| `--recursive` | `-r` | Search for translation files recursively in subdirectories |
| `--exclude` | `-e` | Exclude files matching glob patterns, comma-separated |
| `--verbose` | `-v` | Shows additional output for detailed information |
| `--strict` | | Enables strict mode, treating warnings as errors |
| `--dry-run` | | Runs validation without failing on errors |
| `--config` | `-c` | Path to a configuration file |

::: warning
Either a path to translation files must be provided as a command argument or within the configuration file. If no path is provided, the validator will abort.
:::

::: warning
If `--only` or `--skip` is given a value that resolves to no valid validator classes (e.g. a typo in the FQCN), the command fails with an error rather than silently falling back to running all validators.
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

Pass several validators as a single comma-separated value:

```bash
composer validate-translations ./translations \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator,MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator"
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
| `0` | Validation passed (no errors, or only warnings without `--strict`) |
| `1` | Validation failed (errors found, or warnings found with `--strict`) |

In `--dry-run` mode, errors no longer produce a non-zero exit code. This does **not** apply to `--strict`: warnings still produce exit code `1` under `--strict` even when combined with `--dry-run`.

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

Machine-readable JSON output. Issues are keyed by file path, then by validator short name:

```json
{
  "status": 1,
  "message": "Language validation failed with errors.",
  "issues": {
    "translations/messages.en.yaml": {
      "MismatchValidator": {
        "type": "Error",
        "issues": [
          {
            "message": "the translation key `delete` is missing but present in other files",
            "details": {
              "key": "delete"
            }
          }
        ]
      }
    }
  },
  "statistics": {
    "execution_time": 0.012,
    "execution_time_formatted": "12ms",
    "files_checked": 1,
    "keys_checked": 4,
    "validators_run": 10,
    "parsers_cached": 1
  }
}
```

`status` is the same process exit code described under [Exit Codes](#exit-codes) above (`0` success, `1` failure). `details` varies per validator and mirrors the data available to `--verbose` CLI output.

### GitHub Format

Outputs GitHub Actions workflow commands for annotations, one per issue, followed by a summary annotation and a statistics notice:

```
::error file=translations/messages.en.yaml::the translation key `delete` is missing but present in other files
::error::Language validation failed with errors.
::notice::Validation completed in 12ms - Files: 1, Keys: 4, Validators: 10
```

Annotations additionally include `line=`/`col=` when a validator reports a source position (e.g. `XliffSchemaValidator`), and `title=` when available.

## See Also

- [Configuration File](/configuration/config-file)
- [Validators Reference](/reference/validators)
