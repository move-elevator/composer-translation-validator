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
| `--format` | `-f` | Sets the output format: `cli` (default), `json`, or `github` |
| `--skip` | `-s` | Skips specific validators (FQCN, comma-separated) |
| `--only` | `-o` | Runs only the specified validators (FQCN, comma-separated) |
| `--recursive` | `-r` | Search for translation files recursively in subdirectories |
| `--exclude` | `-e` | Exclude files and directories matching glob patterns, comma-separated. A pattern matches a file or directory name or its path relative to the scanned directory. Excluded directories are not traversed |
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

A pattern is matched against the file name and the path relative to the scanned directory (directories above it are ignored). It applies to directories as well, and excluded directories are not traversed, so `-r --exclude "**/vendor/**"` skips `vendor/` entirely.

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
| `0` | No files found (the command prints a warning) |
| `1` | No paths provided (`No paths provided.`) |
| `1` | Invalid `--format` value (only checked after files were found) |

In `--dry-run` mode, errors no longer produce a non-zero exit code. This does **not** apply to `--strict`: warnings still produce exit code `1` under `--strict` even when combined with `--dry-run`.

## Output Formats

Logger warnings and notices (for example the warning for a file set with a single file) go to stderr, so JSON and GitHub output on stdout stays valid. The "No files found" warning is printed to stdout and is not JSON.

### CLI Format (Default)

Human-readable output with colored indicators:

```text
translations/messages.de.yaml

- Error (MismatchValidator)  the translation key `delete` is missing but present in other translation files (`messages.en.yaml`)

translations/messages.en.yaml

- Error (MismatchValidator)  the translation key `delete` is missing from other translation files (`messages.de.yaml`)


 [ERROR] Language validation failed with errors. See more details with the `-v`
         verbose option.
```

### JSON Format

Machine-readable JSON output. Issues are keyed by file path, then by validator short name:

```json
{
    "status": 1,
    "message": "Language validation failed with errors.",
    "issues": {
        "translations\/messages.de.yaml": {
            "MismatchValidator": {
                "type": "Error",
                "issues": [
                    {
                        "message": "- Error  the translation key `delete` is missing but present in other translation files (`messages.en.yaml`)",
                        "details": {
                            "key": "delete",
                            "files": [
                                {
                                    "file": "\/project\/translations\/messages.de.yaml",
                                    "value": null
                                },
                                {
                                    "file": "\/project\/translations\/messages.en.yaml",
                                    "value": "Delete"
                                }
                            ]
                        }
                    }
                ]
            }
        }
    },
    "statistics": {
        "execution_time": 0.002724885940551758,
        "execution_time_formatted": "3ms",
        "files_checked": 2,
        "keys_checked": 3,
        "validators_run": 9,
        "parsers_cached": 2
    }
}
```

`status` is the same process exit code described under [Exit Codes](#exit-codes) above (`0` success, `1` failure). `details` varies per validator and mirrors the data available to `--verbose` CLI output. A clean run emits `"issues": []`. The example is shortened to one file and uses `/project` for the absolute paths in `details`.

### GitHub Format

Outputs GitHub Actions workflow commands for annotations, one per issue, followed by a summary annotation and a statistics notice:

```text
::error file=translations/messages.de.yaml::- Error  the translation key `delete` is missing but present in other translation files (`messages.en.yaml`)
::error file=translations/messages.en.yaml::- Error  the translation key `delete` is missing from other translation files (`messages.de.yaml`)
::error::Language validation failed with errors.
::notice::Validation completed in 3ms - Files: 2, Keys: 3, Validators: 9
```

The annotation message is the validator message prefixed with `- <Level> `. In the message, `%`, CR and LF are percent-encoded. In properties such as `file=` and `title=`, `:` and `,` are encoded as well. Spaces are not encoded.

Annotations additionally include `line=`/`col=` when a validator reports a source position (e.g. `XliffSchemaValidator`), and `title=` when available.

## See Also

- [Configuration File](/configuration/config-file)
- [Validators Reference](/reference/validators)
