# File Detection

File Detectors group translation files that represent the same content in different languages. The validator supports three detection strategies for different project layouts.

## Detection Strategies

### PrefixFileDetector

Groups files where the language code appears as a **prefix**.

- **Pattern**: `{lang}.{name}.{ext}` or `{name}.{ext}` (default language)
- **Usage**: TYPO3 CMS, some Symfony projects
- **Supported Extensions**: `.xlf`, `.xliff`, `.yaml`, `.yml`, `.json`, `.php`

**Example:**
```
en.messages.xlf    →  grouped as "messages.xlf"
de.messages.xlf    →
fr.messages.xlf    →
```

### SuffixFileDetector

Groups files where the language code appears as a **suffix**.

- **Pattern**: `{name}.{lang}.{ext}`
- **Usage**: Symfony Framework, modern PHP projects
- **Supported Extensions**: `.yml`, `.yaml`, `.xlf`, `.xliff`, `.json`, `.php`

**Example:**
```
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
```
en/messages.php    →  grouped as "messages"
de/messages.php    →
es/messages.php    →
```

## Real-World Examples

### TYPO3 CMS (PrefixFileDetector)

```
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

```
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

```
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

The validator automatically selects the most appropriate file detector based on discovered file patterns. Detection priority:

1. **DirectoryFileDetector** - If language directories are found
2. **SuffixFileDetector** - If files with language suffixes are found
3. **PrefixFileDetector** - If files with language prefixes are found

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

## Language Codes

### Supported Formats

- **ISO 639-1**: `en`, `de`, `fr`, `es`, `it`, `pt`, `ru`, `zh`, `ja`
- **With regions**: `en_US`, `de_DE`, `en_GB`, `pt_BR`, `zh_CN`, `zh_TW`
- **Hyphen notation**: `en-US`, `de-DE`, `en-GB`, `pt-BR`

### Examples

```
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

## See Also

- [File Formats](/reference/file-formats) - Supported translation formats
- [Configuration](/configuration/schema) - Configuration options
