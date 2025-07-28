# File Detection and Translation Formats

This document provides comprehensive information about supported translation file formats and how the validator detects and groups related translation files across different languages.

- [Supported Translation File Formats](#supported-translation-file-formats)
  - [XLIFF](#xliff)
  - [YAML](#yaml)
  - [JSON](#json)
  - [PHP Arrays](#php-arrays)
- [File Detection Strategies](#file-detection-strategies)
  - [PrefixFileDetector](#prefixfiledetector)
  - [SuffixFileDetector](#suffixfiledetector)
  - [DirectoryFileDetector](#directoryfiledetector)

## Supported Translation File Formats

The Composer Translation Validator supports multiple translation file formats commonly used in PHP frameworks and applications:

### XLIFF
- **Extensions**: `.xlf`, `.xliff`
- **Frameworks**: TYPO3 CMS, Symfony (optional)
- **Example**:
  ```xml
  <?xml version="1.0" encoding="UTF-8"?>
  <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" target-language="de">
      <body>
        <trans-unit id="welcome">
          <source>Welcome</source>
          <target>Willkommen</target>
        </trans-unit>
      </body>
    </file>
  </xliff>
  ```

### YAML
- **Extensions**: `.yaml`, `.yml`
- **Frameworks**: Symfony (primary), Laravel (supported)
- **Example**:
  ```yaml
  welcome: "Welcome to our application"
  user:
    greeting: "Hello, %name%!"
    logout: "Logout"
  navigation:
    home: "Home"
    about: "About Us"
  ```

### JSON
- **Extensions**: `.json`
- **Frameworks**: Laravel, Symfony, Vue.js, React
- **Example**:
  ```json
  {
    "welcome": "Welcome to our application",
    "user": {
      "greeting": "Hello, {{name}}!",
      "logout": "Logout"
    },
    "navigation": {
      "home": "Home",
      "about": "About Us"
    }
  }
  ```

### PHP Arrays
- **Extensions**: `.php`
- **Frameworks**: Laravel (primary), Symfony (supported)
- **Example**:
  ```php
  <?php
  return [
      'welcome' => 'Welcome to our application',
      'user' => [
          'greeting' => 'Hello, :name!',
          'logout' => 'Logout',
      ],
      'navigation' => [
          'home' => 'Home',
          'about' => 'About Us',
      ],
  ];
  ```

## File Detection Strategies

File Detectors group translation files that represent the same content in different languages. The validator supports three detection strategies for different project layouts.

### PrefixFileDetector
Groups files where the language code appears as a **prefix**.

- **Pattern**: `{lang}.{name}.{ext}` or `{name}.{ext}`
- **Usage**: TYPO3 CMS, some Symfony projects
- **Supported Extensions**: `.xlf`, `.xliff`, `.yaml`, `.yml`, `.json`, `.php`
- **Example**: `en.messages.xlf`, `de.messages.xlf` → grouped as `messages.xlf`
- **Framework Context**: Common in TYPO3 where the default language file has no prefix

### SuffixFileDetector
Groups files where the language code appears as a **suffix**.

- **Pattern**: `{name}.{lang}.{ext}`
- **Usage**: Symfony Framework, modern PHP projects
- **Supported Extensions**: `.yml`, `.yaml`, `.xlf`, `.xliff`, `.json`, `.php`
- **Example**: `messages.en.yaml`, `messages.de.yaml` → grouped as `messages`
- **Framework Context**: Standard approach in Symfony applications

### DirectoryFileDetector
Groups files organized in **language directories**.

- **Pattern**: `{lang}/{name}.{ext}`
- **Usage**: Laravel, directory-based organization
- **Supported Extensions**: `.php`, `.json`, `.yml`, `.yaml`, `.xlf`, `.xliff`
- **Example**: `en/messages.php`, `de/messages.php` → grouped as `messages`
- **Framework Context**: Default structure in Laravel's `resources/lang/` directory

## Real-World Examples

### TYPO3 CMS Project Structure (PrefixFileDetector)
```
Resources/Private/Language/
├── locallang.xlf              # Default English
├── de.locallang.xlf           # German translation
├── fr.locallang.xlf           # French translation
├── locallang_db.xlf           # Database labels (English)
├── de.locallang_db.xlf        # Database labels (German)
└── fr.locallang_db.xlf        # Database labels (French)
```

**File Grouping Results**:
- Group 1: `locallang.xlf` (en), `de.locallang.xlf` (de), `fr.locallang.xlf` (fr)
- Group 2: `locallang_db.xlf` (en), `de.locallang_db.xlf` (de), `fr.locallang_db.xlf` (fr)

### Symfony Application Structure (SuffixFileDetector)
```
translations/
├── messages.en.yaml           # General messages
├── messages.de.yaml
├── messages.fr.yaml
├── validators.en.yaml         # Form validation messages
├── validators.de.yaml
├── validators.fr.yaml
├── security.en.yaml           # Security-related messages
├── security.de.yaml
└── security.fr.yaml
```

**File Grouping Results**:
- Group 1: `messages.en.yaml`, `messages.de.yaml`, `messages.fr.yaml`
- Group 2: `validators.en.yaml`, `validators.de.yaml`, `validators.fr.yaml`
- Group 3: `security.en.yaml`, `security.de.yaml`, `security.fr.yaml`

### Laravel Application Structure (DirectoryFileDetector)
```
resources/lang/
├── en/
│   ├── auth.php               # Authentication messages
│   ├── pagination.php         # Pagination messages
│   ├── passwords.php          # Password reset messages
│   └── validation.php         # Validation messages
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

**File Grouping Results**:
- Group 1: `en/auth.php`, `de/auth.php`, `es/auth.php`
- Group 2: `en/pagination.php`, `de/pagination.php`, `es/pagination.php`
- Group 3: `en/passwords.php`, `de/passwords.php`, `es/passwords.php`
- Group 4: `en/validation.php`, `de/validation.php`, `es/validation.php`

## Framework-Specific Usage Patterns

### TYPO3 CMS
- **Primary Format**: XLIFF (`.xlf`, `.xliff`)
- **File Detection**: PrefixFileDetector
- **Language Pattern**: `{lang}.{name}.xlf` or `{name}.xlf` (default)
- **Location**: `Resources/Private/Language/`
- **Special Features**: Supports XLIFF 1.2 metadata and translation states

### Symfony Framework
- **Primary Format**: YAML (`.yaml`, `.yml`)
- **File Detection**: SuffixFileDetector
- **Language Pattern**: `{domain}.{locale}.{format}`
- **Location**: `translations/` or `config/translations/`
- **Domain Examples**: `messages`, `validators`, `security`, `forms`

### Laravel Framework
- **Primary Format**: PHP Arrays (`.php`)
- **File Detection**: DirectoryFileDetector
- **Language Pattern**: `{locale}/{file}.php`
- **Location**: `resources/lang/` (Laravel ≤8) or `lang/` (Laravel ≥9)
- **Namespace Support**: Supports package namespaces like `package::file.key`

## Language Codes and Locales

### Supported Formats
- **ISO 639-1**: `en`, `de`, `fr`, `es`, `it`, `pt`, `ru`, `zh`, `ja`
- **With regions**: `en_US`, `de_DE`, `en_GB`, `pt_BR`, `zh_CN`, `zh_TW`
- **Hyphen notation**: `en-US`, `de-DE`, `en-GB`, `pt-BR`
- **Case sensitivity**: Language codes lowercase, regions uppercase

### Examples by Format
```
# Simple language codes
en/messages.php      → locale: "en"
de.locallang.xlf     → locale: "de"
messages.fr.yaml     → locale: "fr"

# With regions
en_US/auth.php       → locale: "en_US"
de_AT.labels.xlf     → locale: "de_AT"
messages.pt_BR.yaml  → locale: "pt_BR"

# Mixed notations (both supported)
en-GB/validation.php → locale: "en-GB"
messages.zh-CN.json  → locale: "zh-CN"
```

## Automatic Detection Strategy

The validator automatically selects the most appropriate file detector based on the discovered file patterns. The detection priority is:

1. **DirectoryFileDetector**: If language directories are found
2. **SuffixFileDetector**: If files with language suffixes are found
3. **PrefixFileDetector**: If files with language prefixes are found

### Manual Configuration

Override automatic detection by specifying detectors in your configuration:

```yaml
# translation-validator.yaml
file-detectors:
  - "MoveElevator\\ComposerTranslationValidator\\FileDetector\\DirectoryFileDetector"
  - "MoveElevator\\ComposerTranslationValidator\\FileDetector\\SuffixFileDetector"
```

```json
// translation-validator.json
{
  "file-detectors": [
    "MoveElevator\\ComposerTranslationValidator\\FileDetector\\PrefixFileDetector"
  ]
}
```
