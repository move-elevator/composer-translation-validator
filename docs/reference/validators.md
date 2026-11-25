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

```text
messages.en.xlf

- Error (DuplicateKeysValidator) the translation key `welcome` occurs multiple times (2x)

[ERROR] Language validation failed with errors. See more details with the `-v` verbose option.
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
This validator is disabled by default to reduce noise, as duplicate values are often intentional (e.g., "OK", "Cancel"). The default `skip` list contains this validator and `KeyNamingConventionValidator`. A non-empty `skip` setting replaces that list, so it enables both validators unless it names them. An empty `skip` list runs all validators.
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

```text
errors.en.yaml

  DuplicateValuesValidator
    - Warning the translation value `This field is required` occurs in multiple keys (`validation.required`, `validation.email`)
    - Warning the translation value `Please enter a valid phone` occurs in multiple keys (`validation.phone`, `validation.address`)

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

```text
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

The validator runs these checks per file:

- **Valid UTF-8:** Reports `File is not valid UTF-8 encoded` and skips the remaining checks for that file. Parsers can reject such a file before the validator runs.
- **Byte Order Mark (BOM):** Reports a UTF-8 BOM at the start of the file.
- **Invisible characters:** Reports zero-width characters, word joiners, direction marks, soft hyphens and control characters, and names the characters found. A BOM is the character U+FEFF, so a file with a BOM also reports `Zero-width no-break space`.
- **Unicode normalization:** Reports content that is not in normalization form NFC (for example `e` followed by a combining accent instead of `é`). This check needs the PHP `intl` extension.

::: info
The JSON parser rejects a file with a BOM before the validator runs, so the example below uses YAML.
:::

### Example

**File: `special.en.yaml` (with BOM)**
```yaml
currency: "Price: €99"
copyright: "© 2024 Company"
```

### Console Output

```text
special.en.yaml

  EncodingValidator
    - Warning encoding issue: File contains UTF-8 Byte Order Mark (BOM)
    - Warning encoding issue: File contains invisible characters: Zero-width no-break space

[WARNING] Language validation completed with warnings.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/encoding --only "MoveElevator\\ComposerTranslationValidator\\Validator\\EncodingValidator" -v
```

**Example files:** `tests/Fixtures/examples/encoding/` contains `special.en.json` without a BOM, so the command above reports success. To reproduce the output, create a YAML file that starts with a BOM and run the validator on its directory:

```bash
mkdir encoding-bom
printf '\xEF\xBB\xBFcurrency: "Price: €99"\ncopyright: "© 2024 Company"\n' > encoding-bom/special.en.yaml
composer validate-translations encoding-bom --only "MoveElevator\\ComposerTranslationValidator\\Validator\\EncodingValidator" -v
```

</details>

::: info
Encoding issues can cause mysterious character displays, especially with special symbols, emojis or non-Latin scripts.
:::

---

## HtmlTagValidator

Verifies HTML tags are consistent across all language versions: same tags, proper nesting and equal `class` values. The first file is the reference. Other attributes are not compared, and a `class` difference is reported only when both values define a `class`.

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
footer: 'Besuchen Sie unsere <a href="/about">Über-Seite</a>'  # No class: not reported
```

### Console Output

```text
messages.de.yaml

  HtmlTagValidator
    - Warning HTML tag inconsistency in translation key `welcome` - File 'messages.en.yaml' is missing HTML tags: <em>; File 'messages.en.yaml' has extra HTML tags: <strong>

+-----------------+-----------------------------------+------------------------------------+
| Translation Key | messages.de.yaml                  | messages.en.yaml                   |
+-----------------+-----------------------------------+------------------------------------+
| welcome         | Willkommen <em>neuer Nutzer</em>! | Welcome <strong>new user</strong>! |
+-----------------+-----------------------------------+------------------------------------+

messages.en.yaml

  HtmlTagValidator
    - Warning HTML tag inconsistency in translation key `welcome` - File 'messages.en.yaml' is missing HTML tags: <em>; File 'messages.en.yaml' has extra HTML tags: <strong>

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

The message names the file that differs from the reference, and the issue appears under every file that contains the key. The first file is the reference, here `messages.de.yaml` (the first table column), so the message reads "missing `<em>`, extra `<strong>`" for `messages.en.yaml`.

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

```text
large-file.en.yaml

  KeyCountValidator
    - Warning File contains 339 translation keys, which exceeds the threshold of 300 keys

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
composer -d tests validate-translations Fixtures/examples/key-count --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyCountValidator" -v

# Test with custom configuration (threshold: 100)
composer -d tests validate-translations Fixtures/examples/key-count --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyCountValidator" -v --config Fixtures/examples/key-count/translation-validator.yaml
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

# 10 levels - EXCEEDS threshold
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

```text
deeply-nested.en.yaml

  KeyDepthValidator
    - Warning Found 1 translation key with nesting depth exceeding threshold of 8

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
composer -d tests validate-translations Fixtures/examples/key-depth --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyDepthValidator" -v

# Test with custom configuration (threshold: 5)
composer -d tests validate-translations Fixtures/examples/key-depth --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyDepthValidator" -v --config Fixtures/examples/key-depth/translation-validator.yaml
```

**Example files:** The fixture `tests/Fixtures/examples/key-depth/deeply-nested.en.yaml` contains more deeply nested keys than the excerpt above, so the commands report 4 keys (threshold 8) and 5 keys (threshold 5).

</details>

::: info
The validator recognizes multiple separators (`.`, `_`, `-`, `:`) and calculates the maximum depth for keys using mixed separators.
:::

---

## KeyNamingConventionValidator

Enforces consistent naming patterns for translation keys.

**Result:** WARNING

::: info Opt-In Validator
This validator is disabled by default, as auto-detected naming conventions often produce false positives on mixed but intentional key styles. Enable it when you want to enforce a convention. The default `skip` list contains this validator and `DuplicateValuesValidator`. A non-empty `skip` setting replaces that list, so it enables both validators unless it names them. An empty `skip` list runs all validators.
:::

::: tip
Without a `convention` or `custom_pattern` setting, the validator runs in auto-detect mode: it determines the predominant convention per file and reports keys that deviate from it. Configure a specific convention for strict enforcement.
:::

### Enable via CLI

```bash
composer validate-translations translations/ \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyNamingConventionValidator"
```

### Enable via Configuration

```yaml
paths:
  - translations/
skip: []  # Empty skip list enables all validators
```

### Supported Conventions

| Convention | Example |
|------------|---------|
| `snake_case` | `user_name`, `form_submit` |
| `camelCase` | `userName`, `formSubmit` |
| `kebab-case` | `user-name`, `form-submit` |
| `PascalCase` | `UserName`, `FormSubmit` |

Set `custom_pattern` instead of `convention` to match keys against your own regex. When both are set, `custom_pattern` wins. The built-in conventions check each dot-separated segment of a key on its own, while `custom_pattern` matches the whole key.

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

```text
mixed.en.yaml

  KeyNamingConventionValidator
    - Warning key naming convention violation: `userEmail` does not follow the configured snake_case convention. Suggested: `user_email`
    - Warning key naming convention violation: `user-phone` does not follow the configured snake_case convention. Suggested: `user_phone`
    - Warning key naming convention violation: `User.Address` does not follow the configured snake_case convention. Suggested: `user.address`

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

### Auto-Detect Mode

Run the validator on the same file without a `convention` setting:

```bash
composer -d tests validate-translations Fixtures/examples/key-naming --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyNamingConventionValidator" -v
```

```text
mixed.en.yaml

  KeyNamingConventionValidator
    - Warning key naming inconsistency: `user_name` uses snake_case convention, but this file predominantly uses PascalCase. Consider: `UserName`
      Tip: Configure a specific naming convention in a configuration file to avoid inconsistencies. Available conventions: snake_case, camelCase, kebab-case, PascalCase.
    - Warning key naming inconsistency: `userEmail` uses camelCase convention, but this file predominantly uses PascalCase. Consider: `UserEmail`
    - Warning key naming inconsistency: `user-phone` uses kebab-case convention, but this file predominantly uses PascalCase. Consider: `UserPhone`

[WARNING] Language validation completed with warnings.
```

The tip appears once per file. The detected dominant convention can differ from the one you intend, so configure `convention` for reliable results.

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
The built-in conventions split a key at dots and check each segment on its own, so `user.first_name` passes `snake_case`. Only `custom_pattern` sees the whole key including its dots.
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

```text
buttons.de.yaml

  MismatchValidator
    - Error  the translation key `edit` is missing from other translation files (`buttons.en.yaml`, `buttons.fr.yaml`)
    - Error  the translation key `delete` is missing but present in other translation files (`buttons.en.yaml`, `buttons.fr.yaml`)
    - Error  the translation key `duplicate` is missing but present in other translation files (`buttons.en.yaml`, `buttons.fr.yaml`)

+-----------------+-----------------+-----------------+-----------------+
| Translation Key | buttons.de.yaml | buttons.en.yaml | buttons.fr.yaml |
+-----------------+-----------------+-----------------+-----------------+
| edit            | Bearbeiten      | Edit            |                 |
| delete          |                 | Delete          | Supprimer       |
| duplicate       |                 |                 | Dupliquer       |
+-----------------+-----------------+-----------------+-----------------+

buttons.en.yaml

  MismatchValidator
    - Error  the translation key `edit` is missing from other translation files (`buttons.de.yaml`, `buttons.fr.yaml`)
    - Error  the translation key `delete` is missing from other translation files (`buttons.de.yaml`, `buttons.fr.yaml`)
    - Error  the translation key `duplicate` is missing but present in other translation files (`buttons.de.yaml`, `buttons.fr.yaml`)

+-----------------+-----------------+-----------------+-----------------+
| Translation Key | buttons.en.yaml | buttons.de.yaml | buttons.fr.yaml |
+-----------------+-----------------+-----------------+-----------------+
| edit            | Edit            | Bearbeiten      |                 |
| delete          | Delete          |                 | Supprimer       |
| duplicate       |                 |                 | Dupliquer       |
+-----------------+-----------------+-----------------+-----------------+

buttons.fr.yaml

  MismatchValidator
    - Error  the translation key `edit` is missing but present in other translation files (`buttons.de.yaml`, `buttons.en.yaml`)
    - Error  the translation key `delete` is missing from other translation files (`buttons.de.yaml`, `buttons.en.yaml`)
    - Error  the translation key `duplicate` is missing from other translation files (`buttons.de.yaml`, `buttons.en.yaml`)

+-----------------+-----------------+-----------------+-----------------+
| Translation Key | buttons.fr.yaml | buttons.de.yaml | buttons.en.yaml |
+-----------------+-----------------+-----------------+-----------------+
| edit            |                 | Bearbeiten      | Edit            |
| delete          | Supprimer       |                 | Delete          |
| duplicate       | Dupliquer       |                 |                 |
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

The issue message has two variants. "is missing but present in other translation files" means the file lacks the key. "is missing from other translation files" means the file has the key and at least one other file lacks it. Every file that takes part in the mismatch gets its own issues.

::: tip
This is usually the most valuable validator. It catches incomplete translations that would show key names to users instead of proper text.
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

```text
notifications.de.yaml

  PlaceholderConsistencyValidator
    - Warning placeholder inconsistency in translation key `welcome` - File 'notifications.en.yaml' is missing placeholders: {benutzername}; File 'notifications.en.yaml' has extra placeholders: {username}
    - Warning placeholder inconsistency in translation key `order` - File 'notifications.en.yaml' is missing placeholders: {sum}; File 'notifications.en.yaml' has extra placeholders: {amount}
    - Warning placeholder inconsistency in translation key `email` - File 'notifications.en.yaml' has extra placeholders: {{ email_address }}

+-----------------+----------------------------------+--------------------------------+
| Translation Key | notifications.de.yaml            | notifications.en.yaml          |
+-----------------+----------------------------------+--------------------------------+
| welcome         | Willkommen {benutzername}!       | Welcome {username}!            |
| order           | Bestellung #{order_id} für {sum} | Order #{order_id} for {amount} |
| email           | Gesendet an {email_address}      | Sent to {{email_address}}      |
+-----------------+----------------------------------+--------------------------------+

notifications.en.yaml

  PlaceholderConsistencyValidator
    - Warning placeholder inconsistency in translation key `welcome` - File 'notifications.en.yaml' is missing placeholders: {benutzername}; File 'notifications.en.yaml' has extra placeholders: {username}
    - Warning placeholder inconsistency in translation key `order` - File 'notifications.en.yaml' is missing placeholders: {sum}; File 'notifications.en.yaml' has extra placeholders: {amount}
    - Warning placeholder inconsistency in translation key `email` - File 'notifications.en.yaml' has extra placeholders: {{ email_address }}

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

The first file (the first table column, here `notifications.de.yaml`) is the reference. Each message names another file and lists the placeholders that file lacks compared to the reference ("is missing") and the ones it has in addition ("has extra"), so the English file is "missing" `{benutzername}` and has `{username}` extra. The same message appears under every file. The validator reads `{{email_address}}` as both `{email_address}` and `{{ email_address }}`, which is why the `email` key reports `{{ email_address }}` as extra.

::: danger
Mismatched placeholders will break variable substitution in your app, showing raw placeholder text to users.
:::

---

## XliffSchemaValidator

Validates XLIFF files against official XML schemas to ensure they're structurally correct.

**Result:** ERROR for schema errors, WARNING for libxml warnings and region mismatches. Each issue carries its own level.

::: warning
This validator only applies to XLIFF files (`.xlf`, `.xliff`).
:::

### Example

**File: `malformed.xlf`**
```xml
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="de" datatype="plaintext">
    <body>
      <trans-unit id="test">
        <source>Hello</source>
        <target>Hallo</target>
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

```text
malformed.xlf

  XliffSchemaValidator
    - Error Element '{urn:oasis:names:tc:xliff:document:1.2}file': The attribute 'original' is required but missing. (Line: 3) (Code: 1868)
    - Error Element '{urn:oasis:names:tc:xliff:document:1.2}trans-unit': The attribute 'id' is required but missing. (Line: 9) (Code: 1868)
    - Error Element '{urn:oasis:names:tc:xliff:document:1.2}trans-unit': Not all fields of key identity-constraint '{urn:oasis:names:tc:xliff:document:1.2}K_unit_id' evaluate to a node. (Line: 9) (Code: 1877)

[ERROR] Language validation failed with errors.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/xliff-schema --only "MoveElevator\\ComposerTranslationValidator\\Validator\\XliffSchemaValidator" -v
```

**Example files:** Check `tests/Fixtures/examples/xliff-schema/` to see the malformed XLIFF file with schema violations.

</details>

### Target Language Consistency

When the filename encodes a locale (e.g. `de_DE.locallang.xlf` or `messages.de_DE.xlf`), the declared `target-language` (XLIFF 1.x) or `trgLang` (XLIFF 2.x) is checked against it:

- **Missing attribute** (e.g. filename `de` and no `target-language` on `<file>`) → **ERROR**: `Missing "target-language" attribute on <file> node; expected "de" based on filename`
- **Base language mismatch** (e.g. filename `de` vs. `target-language="fr"`) → **ERROR**
- **Region mismatch** (e.g. filename `de_DE` vs. `target-language="de-AT"`) → **WARNING**
- A missing region on either side is not reported (e.g. filename `de_DE` with `target-language="de"` is fine).

The validator skips the schema check when no schema exists for the declared XLIFF version and logs a notice such as `Skipping XliffSchemaValidator: No support implemented for loading XLIFF version "3.0".` The locale check still runs for these files.

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

Pass several validators as a single comma-separated value:

```bash
composer validate-translations ./translations \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator,MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator"
```

### Skip Validators

```bash
composer validate-translations ./translations \
  --skip "MoveElevator\\ComposerTranslationValidator\\Validator\\HtmlTagValidator"
```

### Focus on Critical Issues Only

```bash
composer validate-translations ./translations \
  --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator,MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator,MoveElevator\\ComposerTranslationValidator\\Validator\\XliffSchemaValidator"
```
