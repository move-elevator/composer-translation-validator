# Validator Example Fixtures

This directory contains pre-built example files demonstrating validation issues for each validator in the Composer Translation Validator.

## Directory Structure

```
examples/
├── duplicate-keys/        # DuplicateKeysValidator examples
├── duplicate-values/      # DuplicateValuesValidator examples
├── empty-values/          # EmptyValuesValidator examples
├── encoding/              # EncodingValidator examples
├── html-tags/             # HtmlTagValidator examples
├── key-count/             # KeyCountValidator examples
├── key-depth/             # KeyDepthValidator examples
├── key-naming/            # KeyNamingConventionValidator examples
├── mismatch/              # MismatchValidator examples
├── placeholders/          # PlaceholderConsistencyValidator examples
└── xliff-schema/          # XliffSchemaValidator examples
```

## Usage

Run validators against these fixtures from the project root:

```bash
# Example: Test duplicate keys
composer validate-translations tests/Fixtures/examples/duplicate-keys --only "DuplicateKeysValidator"

# Example: Test translation mismatches
composer validate-translations tests/Fixtures/examples/mismatch --only "MismatchValidator"
```

## Purpose

These fixtures are used for:
- Documentation examples in `docs/validators.md`
- Quick testing of validator functionality
- Demonstrating problematic translation patterns
- Verifying validator behavior

Each directory contains intentionally problematic translation files that trigger specific validation warnings or errors.
