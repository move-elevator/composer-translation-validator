# File Detection

File Detectors group translation files that represent the same content in different languages. The validator supports three detection strategies for different project layouts.

## Detection Strategies

### PrefixFileDetector

Groups files where the language code appears as a **prefix**.

- **Pattern**: `{lang}.{name}.{ext}` or `{name}.{ext}` (default language)
- **Usage**: TYPO3 CMS, some Symfony projects
- **Supported Extensions**: `.xlf`, `.xliff`, `.yaml`, `.yml`, `.json`, `.php`

The detector assigns a file to a set by the first rule that matches:

1. The name starts with a language code and a dot (`de.messages.xlf`). The set key is the rest of the name (`messages.xlf`).
2. The name starts with `locallang`, `messages`, `validation`, `errors`, `labels`, `translation`, `translations` or `test`, followed by a dot. The file forms a set on its own, keyed by its full name.
3. The name has a single dot and does not contain `config`, `validator` or `setting` (`app.yaml`). The file forms a set on its own, keyed by its full name.

A file that matches rule 2 or 3 and has no language-prefixed counterpart ends up in a one-file set (see [Scanning](#scanning)).

**Example:**
```text
en.messages.xlf    →  grouped as "messages.xlf"
de.messages.xlf    →
fr.messages.xlf    →
```

### SuffixFileDetector

Groups files where the language code appears as a **suffix**.

- **Pattern**: `{name}.{lang}.{ext}` (the extension is optional in the match)
- **Usage**: Symfony Framework, modern PHP projects
- **Supported Extensions**: `.yml`, `.yaml`, `.xlf`, `.json`, `.php`

The name must not contain a dot. The set key is the name without language code and extension. `.xliff` files do not match.

**Example:**
```text
messages.en.yaml   →  grouped as "messages"
messages.de.yaml   →
messages.fr.yaml   →
```

### DirectoryFileDetector

Groups files organized in **language directories**.

- **Pattern**: `{lang}/{name}.{ext}`
- **Usage**: Laravel, directory-based organization
- **Supported Extensions**: `.php`, `.json`, `.yml`, `.yaml`, `.xlf`, `.xliff`

**Example:**
```text
en/messages.php    →  grouped as "messages"
de/messages.php    →
es/messages.php    →
```

## Real-World Examples

### TYPO3 CMS (PrefixFileDetector)

```text
Resources/Private/Language/
├── locallang.xlf              # Default English
├── de.locallang.xlf           # German
├── fr.locallang.xlf           # French
├── locallang_db.xlf           # Database labels (English)
├── de.locallang_db.xlf        # Database labels (German)
└── fr.locallang_db.xlf        # Database labels (French)
```

**File Grouping:**
- Group 1: `locallang.xlf`, `de.locallang.xlf`, `fr.locallang.xlf`
- Group 2: `locallang_db.xlf`, `de.locallang_db.xlf`, `fr.locallang_db.xlf`

### Symfony (SuffixFileDetector)

```text
translations/
├── messages.en.yaml           # General messages
├── messages.de.yaml
├── messages.fr.yaml
├── validators.en.yaml         # Form validation
├── validators.de.yaml
├── validators.fr.yaml
├── security.en.yaml           # Security messages
├── security.de.yaml
└── security.fr.yaml
```

**File Grouping:**
- Group 1: `messages.en.yaml`, `messages.de.yaml`, `messages.fr.yaml`
- Group 2: `validators.en.yaml`, `validators.de.yaml`, `validators.fr.yaml`
- Group 3: `security.en.yaml`, `security.de.yaml`, `security.fr.yaml`

### Laravel (DirectoryFileDetector)

```text
resources/lang/
├── en/
│   ├── auth.php
│   ├── pagination.php
│   ├── passwords.php
│   └── validation.php
├── de/
│   ├── auth.php
│   ├── pagination.php
│   ├── passwords.php
│   └── validation.php
└── es/
    ├── auth.php
    ├── pagination.php
    ├── passwords.php
    └── validation.php
```

**File Grouping:**
- Group 1: `en/auth.php`, `de/auth.php`, `es/auth.php`
- Group 2: `en/pagination.php`, `de/pagination.php`, `es/pagination.php`
- Group 3: `en/passwords.php`, `de/passwords.php`, `es/passwords.php`
- Group 4: `en/validation.php`, `de/validation.php`, `es/validation.php`

## Automatic Detection

Without configured detectors, the validator pairs the files of each scan path in two steps:

1. **Language directories** (`en/`, `de/`) are paired first, per parent directory of the language directories. Only a name that exists in at least two language directories forms a layout.
2. **The remaining files** are grouped by directory. In each directory the detectors run in this order, and each one only sees the files the previous ones did not pair:
   1. **SuffixFileDetector**
   2. **PrefixFileDetector**
   3. **DirectoryFileDetector**

One directory can therefore mix layouts. The order matters because `PrefixFileDetector` also claims names like `messages.en.yaml`.

## Manual Configuration

Override automatic detection by specifying detectors in your configuration:

**YAML:**
```yaml
file-detectors:
  - "MoveElevator\\ComposerTranslationValidator\\FileDetector\\DirectoryFileDetector"
  - "MoveElevator\\ComposerTranslationValidator\\FileDetector\\SuffixFileDetector"
```

**JSON:**
```json
{
  "file-detectors": [
    "MoveElevator\\ComposerTranslationValidator\\FileDetector\\PrefixFileDetector"
  ]
}
```

The validator applies the configured detectors in the listed order. A later detector only sees the files the earlier ones did not pair. Classes that are not usable file detectors are skipped. Detectors group files per directory, except `DirectoryFileDetector`, which sees all files of the scan path.

The order decides how files are paired. In a directory with `messages.en.yaml`, `messages.de.yaml`, `en.labels.yaml` and `de.labels.yaml`, list `SuffixFileDetector` before `PrefixFileDetector`:

```yaml
file-detectors:
  - "MoveElevator\\ComposerTranslationValidator\\FileDetector\\SuffixFileDetector"
  - "MoveElevator\\ComposerTranslationValidator\\FileDetector\\PrefixFileDetector"
```

This pairs both `messages.*.yaml` files and both `*.labels.yaml` files. With the two entries swapped, `PrefixFileDetector` claims `messages.en.yaml` and `messages.de.yaml` first as separate one-file sets, and the validator warns that each has no counterpart.

## Language Codes

### Supported Formats

- **ISO 639-1**: `en`, `de`, `fr`, `es`, `it`, `pt`, `ru`, `zh`, `ja`
- **With regions**: `en_US`, `de_DE`, `en_GB`, `pt_BR`, `zh_CN`, `zh_TW`
- **Hyphen notation**: `en-US`, `de-DE`, `en-GB`, `pt-BR`

### Examples

```text
# Simple codes
en/messages.php      → locale: "en"
de.locallang.xlf     → locale: "de"
messages.fr.yaml     → locale: "fr"

# With regions
en_US/auth.php       → locale: "en_US"
de_AT.labels.xlf     → locale: "de_AT"
messages.pt_BR.yaml  → locale: "pt_BR"

# Hyphen notation
en-GB/validation.php → locale: "en-GB"
messages.zh-CN.json  → locale: "zh-CN"
```

## Scanning

The validator reads only the given directory unless `--recursive` is set. It skips files larger than 30 MB and files and directories matched by `--exclude` patterns. The recursive scan also skips symlinks and unsafe system paths such as `/etc`.

A file set with only one file triggers the warning `<file> has no counterpart in other languages, cross-file checks were skipped.` on stderr.

## See Also

- [File Formats](/reference/file-formats) - Supported translation formats
- [Configuration](/configuration/schema) - Configuration options
