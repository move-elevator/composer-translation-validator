# File Detectors

File Detectors group translation files that represent the same content in different languages. The validator supports three detection strategies for different project layouts.

## Available Detectors

### PrefixFileDetector
Groups files where the language code appears as a **prefix**.

- **Pattern**: `{lang}.{name}.{ext}` or `{name}.{ext}`
- **Usage**: TYPO3 CMS, some Symfony projects
- **Example**: `en.messages.xlf`, `de.messages.xlf` → grouped as `messages.xlf`

### SuffixFileDetector
Groups files where the language code appears as a **suffix**.

- **Pattern**: `{name}.{lang}.{ext}`
- **Usage**: Symfony Framework, modern PHP projects
- **Extensions**: `.yml`, `.yaml`, `.xlf`, `.xliff`, `.json`, `.php`
- **Example**: `messages.en.yaml`, `messages.de.yaml` → grouped as `messages`

### DirectoryFileDetector
Groups files organized in **language directories**.

- **Pattern**: `{lang}/{name}.{ext}`
- **Usage**: Laravel, directory-based organization
- **Extensions**: `.php`, `.json`, `.yml`, `.yaml`, `.xlf`, `.xliff`
- **Example**: `en/messages.php`, `de/messages.php` → grouped as `messages`

## Examples

### TYPO3 Style (PrefixFileDetector)
```
translations/
├── locallang.xlf
├── de.locallang.xlf
└── fr.locallang.xlf
```

### Symfony Style (SuffixFileDetector)
```
translations/
├── messages.en.yaml
├── messages.de.yaml
├── validators.en.yaml
└── validators.de.yaml
```

### Laravel Style (DirectoryFileDetector)
```
resources/lang/
├── en/
│   ├── messages.php
│   └── auth.php
├── de/
│   ├── messages.php
│   └── auth.php
```

## Language Codes

Supported formats:
- **Simple**: `en`, `de`, `fr`, `es`
- **With region**: `en_US`, `de_DE`, `en-GB`, `pt-BR`
- **Case sensitive**: Language codes lowercase, regions uppercase

## Configuration

The validator automatically selects the best detector. To specify one manually:

```yaml
# translation-validator.yaml
file_detectors:
  - "MoveElevator\\ComposerTranslationValidator\\FileDetector\\DirectoryFileDetector"
```

## Troubleshooting

**No files detected?**
- Check file naming follows supported patterns
- Verify language codes are correct format
- Use `--verbose` flag to see detection details

**Files not grouped correctly?**
- Ensure consistent naming throughout project
- Don't mix different patterns in same project
- Check for typos in language codes
