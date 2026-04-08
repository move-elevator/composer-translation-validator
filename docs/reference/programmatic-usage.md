# Programmatic Usage

The validation pipeline has zero coupling to the Composer plugin and can be used standalone in any PHP application. Only a [PSR-3 LoggerInterface](https://www.php-fig.org/psr/psr-3/) is required.

## Basic Example

```php
use MoveElevator\ComposerTranslationValidator\Config\TranslationValidatorConfig;
use MoveElevator\ComposerTranslationValidator\FileDetector\PrefixFileDetector;
use MoveElevator\ComposerTranslationValidator\Service\ValidationOrchestrationService;
use MoveElevator\ComposerTranslationValidator\Validator\ValidatorRegistry;
use Psr\Log\NullLogger;

$service = new ValidationOrchestrationService(new NullLogger());
$config = new TranslationValidatorConfig();

$result = $service->executeValidation(
    paths: ['/path/to/translations'],
    excludePatterns: [],
    recursive: true,
    fileDetector: new PrefixFileDetector(),
    validators: ValidatorRegistry::getAvailableValidators(),
    config: $config,
);
```

## Custom Validators and Settings

You can select specific validators and configure per-validator settings:

```php
use MoveElevator\ComposerTranslationValidator\Validator\{
    MismatchValidator,
    EmptyValuesValidator,
    KeyCountValidator
};

$config = new TranslationValidatorConfig();
$config->setValidatorSetting('KeyCountValidator', ['threshold' => 500]);
$config->setStrict(true);

$result = $service->executeValidation(
    paths: ['/path/to/translations'],
    excludePatterns: ['**/vendor/**'],
    recursive: true,
    fileDetector: null, // auto-detect
    validators: [
        MismatchValidator::class,
        EmptyValuesValidator::class,
        KeyCountValidator::class,
    ],
    config: $config,
);
```

## Working with Results

The `executeValidation()` method returns a `ValidationResult` object:

```php
if ($result === null) {
    // No files found to validate
    return;
}

if ($result->hasErrors()) {
    foreach ($result->getIssues() as $issue) {
        echo $issue->getFilePath() . ': ' . $issue->getMessage() . PHP_EOL;
    }
}
```

## Use Cases

This makes the validation pipeline suitable for:

- **CI pipelines** without Composer runtime
- **Custom CLI tools** built with Symfony Console
- **Integration into existing applications** (e.g., TYPO3, Symfony, Laravel)
- **Pre-commit hooks** or custom quality gates

## See Also

- [CLI Reference](/reference/cli)
- [Validators](/reference/validators)
- [Configuration](/configuration/)
