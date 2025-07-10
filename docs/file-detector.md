# File Detectors

### SuffixFileDetector
* **Class**: `MoveElevator\ComposerTranslationValidator\FileDetector\SuffixFileDetector`
* **Purpose**: Groups translation files by suffix pattern (e.g., `messages.en.yml`, `messages.de.xlf`)
* **Pattern**: `^([^.]+)\.[a-z]{2}([-_][A-Z]{2})?(\.ya?ml|\.xlf)?$`

### PrefixFileDetector
* **Class**: `MoveElevator\ComposerTranslationValidator\FileDetector\PrefixFileDetector`
* **Purpose**: Groups translation files by prefix pattern (e.g., `en.messages.xlf`, `de_DE.validation.yml`)
* **Pattern**: `^([a-z]{2}(?:[-_][A-Z]{2})?)\.(.+)$`
