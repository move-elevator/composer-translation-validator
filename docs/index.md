---
layout: home

hero:
  name: Composer Translation Validator
  text: Validate your translation files
  tagline: A Composer plugin that validates XLIFF, YAML, JSON and PHP translation files for mismatches, duplicates, schema issues and more.
  image:
    src: /logo.svg
    alt: Composer Translation Validator
  actions:
    - theme: brand
      text: Get Started
      link: /getting-started/
    - theme: alt
      text: View on GitHub
      link: https://github.com/move-elevator/composer-translation-validator

features:
  - icon: "&#128269;"
    title: Auto-Detection
    details: Automatically detects and groups related translation files based on naming conventions and directory structure.
    link: /reference/file-detection
  - icon: "&#128196;"
    title: Multiple Formats
    details: Supports XLIFF, YAML, JSON and PHP translation files commonly used in TYPO3, Symfony and Laravel.
    link: /reference/file-formats
  - icon: "&#9989;"
    title: 11 Validators
    details: Comprehensive validation including mismatch detection, duplicate keys, placeholder consistency, HTML tags and more.
    link: /reference/validators
  - icon: "&#9881;"
    title: Configurable
    details: Flexible configuration via PHP, JSON or YAML files. Enable/disable validators and customize thresholds.
    link: /configuration/
  - icon: "&#128736;"
    title: CI/CD Ready
    details: Integrates seamlessly into your CI/CD pipeline with JSON and GitHub output formats.
    link: /reference/cli
  - icon: "&#9888;"
    title: Strict Mode
    details: Treat warnings as errors for rigorous validation in production environments.
    link: /configuration/schema#strict
---

## Quick Start

Install the plugin via Composer:

```bash
composer require --dev move-elevator/composer-translation-validator
```

Validate your translation files:

```bash
composer validate-translations ./translations
```

![Console Output](/images/console.png)

## Supported Formats

| Format | Extensions | Frameworks |
|--------|------------|------------|
| [XLIFF](/reference/file-formats#xliff-xml-localization-interchange-file-format) | `.xlf`, `.xliff` | TYPO3 CMS |
| [YAML](/reference/file-formats#yaml-yaml-ain-t-markup-language) | `.yaml`, `.yml` | Symfony |
| [JSON](/reference/file-formats#json-javascript-object-notation) | `.json` | Laravel, Symfony |
| [PHP](/reference/file-formats#php-arrays) | `.php` | Laravel, Symfony |

## Available Validators

| Validator | Type | Description |
|-----------|------|-------------|
| [MismatchValidator](/reference/validators#mismatchvalidator) | ERROR | Finds missing translations between language files |
| [DuplicateKeysValidator](/reference/validators#duplicatekeysvalidator) | ERROR | Catches duplicate keys within files |
| [XliffSchemaValidator](/reference/validators#xliffschemavalidator) | ERROR | Validates XLIFF against XML schemas |
| [DuplicateValuesValidator](/reference/validators#duplicatevaluesvalidator) | WARNING | Finds identical translation values (opt-in) |
| [EmptyValuesValidator](/reference/validators#emptyvaluesvalidator) | WARNING | Detects empty or whitespace-only values |
| [PlaceholderConsistencyValidator](/reference/validators#placeholderconsistencyvalidator) | WARNING | Validates placeholder patterns |
| [HtmlTagValidator](/reference/validators#htmltagvalidator) | WARNING | Ensures HTML tag consistency across languages |
| [EncodingValidator](/reference/validators#encodingvalidator) | WARNING | Validates UTF-8 encoding and Unicode issues |
| [KeyCountValidator](/reference/validators#keycountvalidator) | WARNING | Warns when files exceed key threshold |
| [KeyDepthValidator](/reference/validators#keydepthvalidator) | WARNING | Warns about excessive nesting depth |
| [KeyNamingConventionValidator](/reference/validators#keynamingconventionvalidator) | WARNING | Enforces key naming patterns |

See the [Validators Reference](/reference/validators) for detailed documentation.
