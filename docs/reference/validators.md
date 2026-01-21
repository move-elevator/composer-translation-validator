# Validators

This page provides detailed documentation for each validator available in the Composer Translation Validator.

[[toc]]

::: warning Result Types
Validators differ in their result types:
- **ERROR** - Critical issues that cause validation to fail. The translation files may not be usable.
- **WARNING** - Potential issues that should be addressed. Validation succeeds but consider fixing these. Use `--strict` to treat warnings as errors.

Use `-v` or `--verbose` to see detailed information about issues.
:::

## DuplicateKeysValidator

Catches duplicate translation keys within the same file, which can cause unpredictable behavior.

**Result:** ERROR

### Example

**File: `messages.en.xlf`**
```xml
<xliff version="1.2">
  <file source-language="en" target-language="en">
    <body>
      <trans-unit id="welcome">
        <source>Welcome to our app!</source>
        <target>Welcome to our app!</target>
      </trans-unit>
      <trans-unit id="greeting">
        <source>Hello user</source>
        <target>Hello user</target>
      </trans-unit>
      <trans-unit id="welcome">  <!-- Duplicate ID! -->
        <source>Welcome back!</source>
        <target>Welcome back!</target>
      </trans-unit>
    </body>
  </file>
</xliff>
```

### Console Output

```
messages.en.xlf

- Error (DuplicateKeysValidator) the translation key `welcome` occurs multiple times (2x)

[ERROR] Language validation failed with errors.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/duplicate-keys --only "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator"
```

**Example files:** Check `tests/Fixtures/examples/duplicate-keys/` to see the XLIFF file with duplicate ID attributes.

</details>

Most parsers will silently use the last occurrence, making your first translation unreachable.

::: info
In many formats, such errors are already caught by IDEs or interpreters. However, these problems can occur especially with XLIFF files.
:::

---

## DuplicateValuesValidator

Identifies identical translation values that might indicate copy-paste errors or missing translations.

**Result:** WARNING

::: info Opt-In Validator
This validator is disabled by default to reduce noise, as duplicate values are often intentional (e.g., "OK", "Cancel").
:::

### Enable via CLI

```bash
composer validate-translations translations/ \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateValuesValidator"
```

### Enable via Configuration

```yaml
paths:
  - translations/
skip: []  # Empty skip list enables all validators
```

### Example

**File: `errors.en.yaml`**
```yaml
validation:
  required: "This field is required"
  email: "This field is required"     # Same value as 'required'
  phone: "Please enter a valid phone"
  address: "Please enter a valid phone" # Copy-paste error
```

### Console Output

```
errors.en.yaml

  DuplicateValuesValidator
    - Warning the translation value `This field is required` occurs in multiple keys
    - Warning the translation value `Please enter a valid phone` occurs in multiple keys

[WARNING] Language validation completed with warnings.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/duplicate-values --only "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateValuesValidator" -v
```

**Example files:** Check `tests/Fixtures/examples/duplicate-values/` to see the problematic translation file.

</details>

::: tip
While sometimes duplicate values are intentional, they often reveal incomplete translations or copy-paste mistakes.
:::

---

## EmptyValuesValidator

Hunts down empty or whitespace-only translation values that would display nothing to users.

**Result:** WARNING

### Example

**File: `navigation.de.xlf`**
```xml
<xliff version="1.2">
  <file source-language="en" target-language="de">
    <body>
      <trans-unit id="home">
        <source>Home</source>
        <target>Startseite</target>
      </trans-unit>
      <trans-unit id="about">
        <source>About</source>
        <target></target> <!-- Empty translation -->
      </trans-unit>
      <trans-unit id="contact">
        <source>Contact</source>
        <target>   </target> <!-- Only whitespace -->
      </trans-unit>
    </body>
  </file>
</xliff>
```

### Console Output

```
navigation.de.xlf

  EmptyValuesValidator
    - Warning the translation key `contact` has an whitespace only value

[WARNING] Language validation completed with warnings.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/empty-values --only "MoveElevator\\ComposerTranslationValidator\\Validator\\EmptyValuesValidator" -v
```

**Example files:** Check `tests/Fixtures/examples/empty-values/` to see the XLIFF file with empty translations.

</details>

::: info
Either provide proper translations or remove these entries entirely if they're not needed yet.
:::

---

## EncodingValidator

Ensures your files use proper UTF-8 encoding and catches Unicode issues that can break your app.

**Result:** WARNING

### Example

**File: `special.en.json` (with BOM)**
```json
{
  "currency": "Price: €99",
  "copyright": "© 2024 Company"
}
```

### Console Output

```
special.en.json

  EncodingValidator
    - Warning encoding issue: File contains UTF-8 Byte Order Mark (BOM)
    - Warning encoding issue: File contains invisible characters

[WARNING] Language validation completed with warnings.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/encoding --only "MoveElevator\\ComposerTranslationValidator\\Validator\\EncodingValidator"
```

**Example files:** Check `tests/Fixtures/examples/encoding/` to see the JSON file with BOM and encoding issues.

</details>

::: info
Encoding issues can cause mysterious character displays, especially with special symbols, emojis or non-Latin scripts.
:::

---

## HtmlTagValidator

Verifies HTML tags are consistent across all language versions: same tags, proper nesting, matching attributes.

**Result:** WARNING

### Example

**File: `messages.en.yaml`**
```yaml
welcome: "Welcome <strong>new user</strong>!"
footer: 'Visit our <a href="/about" class="link">about page</a>'
```

**File: `messages.de.yaml`**
```yaml
welcome: "Willkommen <em>neuer Nutzer</em>!"  # <strong> became <em>
footer: 'Besuchen Sie unsere <a href="/about">Über-Seite</a>'  # Missing class
```

### Console Output

```
messages.de.yaml

  HtmlTagValidator
    - Warning HTML tag inconsistency in translation key `welcome`

+-----------------+-----------------------------------+------------------------------------+
| Translation Key | messages.de.yaml                  | messages.en.yaml                   |
+-----------------+-----------------------------------+------------------------------------+
| welcome         | Willkommen <em>neuer Nutzer</em>! | Welcome <strong>new user</strong>! |
+-----------------+-----------------------------------+------------------------------------+

[WARNING] Language validation completed with warnings.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/html-tags --only "MoveElevator\\ComposerTranslationValidator\\Validator\\HtmlTagValidator" -v
```

**Example files:** Check `tests/Fixtures/examples/html-tags/` to see the English and German files with mismatched HTML tags.

</details>

---

## KeyCountValidator

Warns when translation files contain more keys than a configurable threshold.

**Result:** WARNING

::: tip
Default threshold is 300 keys. Customize via configuration.
:::

### Example

**File: `large-file.en.yaml` (339 keys)**
```yaml
user:
  profile:
    name: "Name"
    email: "Email"
    # ... many more keys
navigation:
  home: "Home"
  # ... many more keys
# Total: 339 translation keys
```

### Console Output

```
large-file.en.yaml

  KeyCountValidator
    - Warning File contains 339 translation keys, exceeds threshold of 300

[WARNING] Language validation completed with warnings.
```

### Configuration

```yaml
validator-settings:
  KeyCountValidator:
    threshold: 500  # Warn when files have more than 500 keys
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer validate-translations Fixtures/examples/key-count --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyCountValidator" -v

# Test with custom configuration (threshold: 100)
composer validate-translations Fixtures/examples/key-count --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyCountValidator" -v --config Fixtures/examples/key-count/translation-validator.yaml
```

</details>

::: info
Large translation files can become difficult to maintain. Consider splitting them into smaller, domain-specific files.
:::

---

## KeyDepthValidator

Warns when translation keys have excessive nesting depth.

**Result:** WARNING

::: tip
Default threshold is 8 nesting levels. Customize via configuration.
:::

### Example

**File: `deeply-nested.en.yaml`**
```yaml
# 7 levels - OK
user:
  profile:
    settings:
      privacy:
        notifications:
          email:
            enabled: "Email notifications enabled"

# 11 levels - EXCEEDS threshold
application:
  modules:
    auth:
      forms:
        login:
          validation:
            rules:
              password:
                complexity:
                  requirements: "Password requirements"
```

### Console Output

```
deeply-nested.en.yaml

  KeyDepthValidator
    - Warning Found 4 translation keys with nesting depth exceeding threshold of 8

[WARNING] Language validation completed with warnings.
```

### Configuration

```yaml
validator-settings:
  KeyDepthValidator:
    threshold: 5  # Warn when keys have more than 5 nesting levels
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer validate-translations -d tests Fixtures/examples/key-depth --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyDepthValidator" -v

# Test with custom configuration (threshold: 5)
composer validate-translations -d tests Fixtures/examples/key-depth --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyDepthValidator" -v --config Fixtures/examples/key-depth/translation-validator.yaml
```

</details>

::: info
The validator recognizes multiple separators (`.`, `_`, `-`, `:`) and calculates the maximum depth for keys using mixed separators.
:::

---

## KeyNamingConventionValidator

Enforces consistent naming patterns for translation keys.

**Result:** WARNING

::: tip
This validator auto-detects the most common pattern in your files. Configure a specific convention for strict enforcement.
:::

### Supported Conventions

| Convention | Example |
|------------|---------|
| `snake_case` | `user_name`, `form_submit` |
| `camelCase` | `userName`, `formSubmit` |
| `kebab-case` | `user-name`, `form-submit` |
| `PascalCase` | `UserName`, `FormSubmit` |
| `custom_pattern` | Your own regex |

### Example

**Configuration:**
```yaml
validator-settings:
  KeyNamingConventionValidator:
    convention: snake_case
```

**File: `mixed.en.yaml`**
```yaml
user_name: "Username"
userEmail: "Email"              # camelCase
user-phone: "Phone"             # kebab-case
User.Address: "Address"         # Mixed styles
```

### Console Output

```
mixed.en.yaml

  KeyNamingConventionValidator
    - Warning key naming violation: `userEmail` does not follow snake_case (suggestion: `user_email`)
    - Warning key naming violation: `user-phone` does not follow snake_case (suggestion: `user_phone`)
    - Warning key naming violation: `User.Address` does not follow snake_case (suggestion: `user.address`)

[WARNING] Language validation completed with warnings.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/key-naming --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyNamingConventionValidator" --config Fixtures/examples/key-naming/translation-validator.yaml -v
```

**Example files:** Check `tests/Fixtures/examples/key-naming/` to see the translation file with mixed naming conventions and the config file.

</details>

### Custom Pattern Examples

```yaml
validator-settings:
  KeyNamingConventionValidator:
    # Only lowercase letters and numbers
    custom_pattern: '/^[a-z0-9]+$/'

    # Specific prefix requirement
    custom_pattern: '/^app\.[a-z][a-z0-9_]*$/'

    # Maximum length constraint
    custom_pattern: '/^[a-z][a-z0-9_]{0,29}$/'
```

::: warning
Dot notation (`user.name`) is typically used for nested structures. This validator focuses on flat key names.
:::

---

## MismatchValidator

Catches translation keys that exist in some language files but are missing from others.

**Result:** ERROR

### Example

**File: `buttons.en.yaml`**
```yaml
save: "Save"
cancel: "Cancel"
delete: "Delete"
edit: "Edit"
```

**File: `buttons.de.yaml`**
```yaml
save: "Speichern"
cancel: "Abbrechen"
edit: "Bearbeiten"
# 'delete' key missing!
```

**File: `buttons.fr.yaml`**
```yaml
save: "Sauvegarder"
cancel: "Annuler"
delete: "Supprimer"
duplicate: "Dupliquer"  # Extra key not in other files
```

### Console Output

```
buttons.de.yaml

  MismatchValidator
    - Error  the translation key `delete` is missing but present in other files
    - Error  the translation key `duplicate` is missing but present in other files

+-----------------+-----------------+-----------------+-----------------+
| Translation Key | buttons.de.yaml | buttons.en.yaml | buttons.fr.yaml |
+-----------------+-----------------+-----------------+-----------------+
| delete          |                 | Delete          | Supprimer       |
| duplicate       |                 |                 | Dupliquer       |
+-----------------+-----------------+-----------------+-----------------+

[ERROR] Language validation failed with errors.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/mismatch --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator" -v
```

**Example files:** Check `tests/Fixtures/examples/mismatch/` to see the English, German, and French files with missing translation keys.

</details>

::: tip
This is usually the most valuable validator - it catches incomplete translations that would show key names to users instead of proper text.
:::

---

## PlaceholderConsistencyValidator

Ensures placeholder patterns are consistent across languages so dynamic content works everywhere.

**Result:** WARNING

### Example

**File: `notifications.en.yaml`**
```yaml
welcome: "Welcome {username}!"
order: "Order #{order_id} for {amount}"
email: "Sent to {{email_address}}"
```

**File: `notifications.de.yaml`**
```yaml
welcome: "Willkommen {benutzername}!"     # Different placeholder name
order: "Bestellung #{order_id} für {sum}" # 'amount' became 'sum'
email: "Gesendet an {email_address}"      # Missing double braces
```

### Console Output

```
notifications.de.yaml

  PlaceholderConsistencyValidator
    - Warning placeholder inconsistency in key `welcome` - missing: {username}, extra: {benutzername}
    - Warning placeholder inconsistency in key `order` - missing: {amount}, extra: {sum}
    - Warning placeholder inconsistency in key `email` - extra: {{ email_address }}

+-----------------+----------------------------------+--------------------------------+
| Translation Key | notifications.de.yaml            | notifications.en.yaml          |
+-----------------+----------------------------------+--------------------------------+
| welcome         | Willkommen {benutzername}!       | Welcome {username}!            |
| order           | Bestellung #{order_id} für {sum} | Order #{order_id} for {amount} |
| email           | Gesendet an {email_address}      | Sent to {{email_address}}      |
+-----------------+----------------------------------+--------------------------------+

[WARNING] Language validation completed with warnings.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/placeholders --only "MoveElevator\\ComposerTranslationValidator\\Validator\\PlaceholderConsistencyValidator" -v
```

**Example files:** Check `tests/Fixtures/examples/placeholders/` to see the English and German files with inconsistent placeholder patterns.

</details>

::: danger
Mismatched placeholders will break variable substitution in your app, showing raw placeholder text to users.
:::

---

## XliffSchemaValidator

Validates XLIFF files against official XML schemas to ensure they're structurally correct.

**Result:** ERROR

::: warning
This validator only applies to XLIFF files (`.xlf`, `.xliff`).
:::

### Example

**File: `malformed.xlf`**
```xml
<xliff version="1.2">
  <file source-language="en" target-language="de">
    <body>
      <trans-unit id="test">
        <source>Hello</source>
      </trans-unit>
      <trans-unit>  <!-- Missing required 'id' attribute -->
        <source>World</source>
        <target>Welt</target>
      </trans-unit>
    </body>
  </file>
</xliff>
```

### Console Output

```
malformed.xlf

  XliffSchemaValidator
    - Error Element 'file': The attribute 'original' is required but missing. (Line: 3)
    - Error Element 'trans-unit': The attribute 'id' is required but missing. (Line: 9)

[ERROR] Language validation failed with errors.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/xliff-schema --only "MoveElevator\\ComposerTranslationValidator\\Validator\\XliffSchemaValidator"
```

**Example files:** Check `tests/Fixtures/examples/xliff-schema/` to see the malformed XLIFF file with schema violations.

</details>

::: danger
Malformed XLIFF files can crash translation tools or cause parsing errors in your application.
:::

---

## Running Specific Validators

### Run Only Selected Validators

```bash
composer validate-translations ./translations \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator"
```

### Run Multiple Validators

```bash
composer validate-translations ./translations \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator" \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator"
```

### Skip Validators

```bash
composer validate-translations ./translations \
  --skip "MoveElevator\\ComposerTranslationValidator\\Validator\\HtmlTagValidator"
```

### Focus on Critical Issues Only

```bash
composer validate-translations ./translations \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator" \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator" \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\XliffSchemaValidator"
```
