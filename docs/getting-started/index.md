# Introduction

Composer Translation Validator is a Composer plugin that validates translation files in your PHP projects. It automatically detects translation files based on naming conventions and runs a comprehensive set of validators to ensure translation quality.

## Why Use Translation Validation?

Translation files are critical for internationalized applications. Common issues include:

- **Missing translations** - Keys exist in one language but not others
- **Duplicate keys** - Same key defined multiple times in a file
- **Placeholder mismatches** - Variables like `{name}` differ between languages
- **Empty values** - Translations that are blank or whitespace-only
- **HTML inconsistencies** - Tags differ between language versions
- **Encoding problems** - UTF-8 BOM or invalid characters

These issues can cause runtime errors, display raw key names to users, or break formatting. The Translation Validator catches these problems before they reach production.

## Key Features

### Auto-Detection
The plugin automatically detects and groups related translation files. It recognizes files organized by:
- **Prefix**: `en.messages.xlf`, `de.messages.xlf` (TYPO3 style)
- **Suffix**: `messages.en.yaml`, `messages.de.yaml` (Symfony style)
- **Directory**: `en/messages.php`, `de/messages.php` (Laravel style)

### Multiple Formats
Supports the most common translation file formats:
- **XLIFF** (`.xlf`, `.xliff`) - TYPO3 CMS
- **YAML** (`.yaml`, `.yml`) - Symfony Framework
- **JSON** (`.json`) - Laravel, Symfony, frontend frameworks
- **PHP Arrays** (`.php`) - Laravel, Symfony

### Comprehensive Validation
11 built-in validators covering:
- Structural issues (mismatches, duplicates)
- Content issues (empty values, encoding)
- Consistency (placeholders, HTML tags)
- Code quality (key naming, nesting depth)

### CI/CD Integration
Output formats for different environments:
- **CLI** - Human-readable console output
- **JSON** - Machine-readable for custom processing
- **GitHub** - Annotations in GitHub Actions

## Next Steps

- [Installation](/getting-started/installation) - Add the plugin to your project
- [Quick Start](/getting-started/quickstart) - Run your first validation
