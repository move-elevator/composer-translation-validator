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

The `executeValidation()` method returns a `ValidationResult` object, or `null` if no matching
files were found:

```php
if ($result === null) {
    // No files found to validate
    return;
}

if ($result->hasIssues()) {
    foreach ($result->getValidatorsWithIssues() as $validator) {
        foreach ($validator->getIssues() as $issue) {
            echo $issue->getFile() . ': ' . $validator->formatIssueMessage($issue) . PHP_EOL;
        }
    }
}

// The exit code Composer/the CLI would use, respecting --dry-run and --strict
$exitCode = $result->getOverallResult()->resolveErrorToCommandExitCode(
    dryRun: $config->getDryRun(),
    strict: $config->getStrict(),
);
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
