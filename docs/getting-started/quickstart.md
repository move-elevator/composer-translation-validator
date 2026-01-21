# Quick Start

After [installation](/getting-started/installation), you can immediately start validating your translation files.

## Basic Usage

Run the validator on a directory containing translation files:

```bash
composer validate-translations ./translations
```

The plugin will:
1. Scan the directory for translation files
2. Automatically detect the file format and grouping strategy
3. Run all enabled validators
4. Display results in the console

## Example Output

![Console Output](/images/console.png)

## Validate Multiple Paths

You can specify multiple paths:

```bash
composer validate-translations ./translations ./resources/lang
```

## Recursive Search

Search subdirectories recursively:

```bash
composer validate-translations ./translations --recursive
```

## Verbose Output

Get detailed information about each issue:

```bash
composer validate-translations ./translations --verbose
```

Or use the short form:

```bash
composer validate-translations ./translations -v
```

## Strict Mode

Treat warnings as errors (useful for CI/CD):

```bash
composer validate-translations ./translations --strict
```

## Dry Run

Test validation without failing the command:

```bash
composer validate-translations ./translations --dry-run
```

## Output Formats

### JSON Output

```bash
composer validate-translations ./translations --format json
```

### GitHub Actions Format

```bash
composer validate-translations ./translations --format github
```

## Using a Configuration File

For repeated use, create a configuration file:

```yaml
# translation-validator.yaml
paths:
  - translations/
  - resources/lang/
strict: true
exclude:
  - "**/backup/**"
```

Then run without arguments:

```bash
composer validate-translations
```

See [Configuration](/configuration/) for all available options.

## Running Specific Validators

Run only certain validators:

```bash
composer validate-translations ./translations --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator"
```

Skip specific validators:

```bash
composer validate-translations ./translations --skip "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateValuesValidator"
```

## Next Steps

- [Configuration](/configuration/) - Customize validation behavior
- [CLI Reference](/reference/cli) - Full command documentation
- [Validators](/reference/validators) - Detailed validator documentation
