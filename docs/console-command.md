# Console command `validate-translations`

```bash
composer validate-translations [<path>...] [--dry-run] [--strict] [--format|-f <cli|json>] [--skip|-s <VALIDATOR>...] [--only|-o <VALIDATOR>...] [--recursive|-r] [--exclude|-e <PATTERN>] [--verbose|-v] [--config|-c <CONFIG>]
```

| Argument / Option | Shortcut | Description                                                                                       |
|-------------------|----------|---------------------------------------------------------------------------------------------------|
| `<path>`          |          | (Optional) Path to the translation files or directories to validate (can be used multiple times). |
| `--format`        | `-f`     | Sets the output format (`cli`, `json`, `github`).                                                  |
| `--skip`          | `-s`     | Skips specific validators (can be used multiple times).                                           |
| `--only`          | `-o`     | Runs only the specified validators (can be used multiple times).                                  |
| `--recursive`     | `-r`     | Search for translation files recursively in subdirectories                                        |
| `--exclude`       | `-e`     | Exclude files matching glob patterns, comma-separated (e.g., `"**/backup/**,**/*.bak"`).         |
| `--verbose`       | `-v`     | Shows additional output for detailed information.                                                 |
| `--strict`        |          | Enables strict mode, treating warnings as errors.                                                 |
| `--dry-run`       |          | Runs the validation in test mode without saving changes.                                          |
| `--config`        | `-c`     | Path to a configuration file (e.g. `translation-validator.yaml`).                                 |

> [!IMPORTANT]
> Either a path to translation files has to provided as a command argument or within the configuration file. If no path is provided, the validator will abort.

Find more information about storing configuration within a [config file](config-file.md).
