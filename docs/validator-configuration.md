# Validator-Specific Configuration

Some validators support additional configuration options that can be specified in your configuration file. This allows you to customize validator behavior to match your project's specific requirements.

## Configuration Format

Validator-specific settings are configured under the `validator-settings` key in your configuration file:

### YAML Configuration
```yaml
# translation-validator.yaml
paths:
  - translations/

validator-settings:
  ValidatorName:
    setting1: value1
    setting2: value2
```

### JSON Configuration
```json
{
  "paths": ["translations/"],
  "validator-settings": {
    "ValidatorName": {
      "setting1": "value1",
      "setting2": "value2"
    }
  }
}
```

## Available Validators

### KeyNamingConventionValidator

Configure naming conventions for translation keys. **This validator only runs when explicitly configured.**

#### Configuration Options

- `convention` - Use a predefined naming convention (see available conventions below)
- `custom_pattern` - Define your own regex pattern (overrides convention if both are provided)

#### YAML Configuration Example
```yaml
# translation-validator.yaml
paths:
  - translations/

validator-settings:
  KeyNamingConventionValidator:
    convention: snake_case  # Available: snake_case, camelCase, dot.notation, kebab-case, PascalCase
    # OR use a custom regex pattern:
    # custom_pattern: '/^[a-z][a-z0-9_]*$/'
```

#### JSON Configuration Example
```json
{
  "paths": ["translations/"],
  "validator-settings": {
    "KeyNamingConventionValidator": {
      "convention": "camelCase"
    }
  }
}
```

#### Available Conventions

- `snake_case` - user_name, form_submit
- `camelCase` - userName, formSubmit
- `dot.notation` - user.name, form.submit
- `kebab-case` - user-name, form-submit
- `PascalCase` - UserName, FormSubmit
- `custom_pattern` - Define your own regex pattern

#### Usage Examples

```bash
# Using config file
composer validate-translations translations/ --config translation-validator.yaml

# Using command-line (only to enable/disable, not configure)
composer validate-translations translations/ --only KeyNamingConventionValidator
```

#### Custom Pattern Examples

```yaml
validator-settings:
  KeyNamingConventionValidator:
    # Only lowercase letters and numbers
    custom_pattern: '/^[a-z0-9]+$/'

    # Specific prefix requirement
    custom_pattern: '/^app\.[a-z][a-z0-9_]*$/'

    # Maximum length constraint
    custom_pattern: '/^[a-z][a-z0-9_]{0,29}$/' # Max 30 characters
```

## Creating Custom Validator Configurations

If you're developing a custom validator that supports configuration:

1. Implement the `setConfig()` method in your validator:
```php
public function setConfig(?TranslationValidatorConfig $config): void
{
    $this->config = $config;
    $this->loadSettingsFromConfig();
}

private function loadSettingsFromConfig(): void
{
    if (null === $this->config) {
        return;
    }

    $settings = $this->config->getValidatorSettings('YourValidatorClassName');

    // Load your specific settings
    if (isset($settings['your_setting'])) {
        $this->yourSetting = $settings['your_setting'];
    }
}
```

2. Add configuration validation and error handling:
```php
private function loadSettingsFromConfig(): void
{
    // ... existing code ...

    if (isset($settings['your_setting'])) {
        if (!is_string($settings['your_setting'])) {
            $this->logger?->warning('Invalid setting type for your_setting');
            return;
        }
        $this->yourSetting = $settings['your_setting'];
    }
}
```

3. Implement conditional execution if needed:
```php
public function shouldRun(): bool
{
    return null !== $this->yourRequiredSetting;
}
```

## Configuration File Locations

The validator will automatically search for configuration files in the following order:

1. File specified with `--config` option
2. `translation-validator.php`
3. `translation-validator.json`
4. `translation-validator.yaml`
5. `translation-validator.yml`
6. Configuration referenced in `composer.json`

For more information about configuration files in general, see [Configuration Files](config-file.md).
