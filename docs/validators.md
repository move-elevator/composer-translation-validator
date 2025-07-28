# Translation Validators

This page provides detailed explanations for each validator available in the Composer Translation Validator. Each validator comes with practical examples showing problematic translations and their corresponding console output.

- [DuplicateKeysValidator](#duplicatekeysvalidator)
- [DuplicateValuesValidator](#duplicatevaluesvalidator)
- [EmptyValuesValidator](#emptyvaluesvalidator)
- [EncodingValidator](#encodingvalidator)
- [HtmlTagValidator](#htmltagvalidator)
- [KeyNamingConventionValidator](#keynamingconventionvalidator)
- [MismatchValidator](#mismatchvalidator)
- [PlaceholderConsistencyValidator](#placeholderconsistencyvalidator)
- [XliffSchemaValidator](#xliffschemavalidator)

> [!IMPORTANT]
> Validators differ in their result types.
> - ![error](https://img.shields.io/badge/ERROR-red) Some validators return an error when critical issues are found. An error indicates that the validation failed and the translation files may not be usable. Use a `-v` or `--verbose` option to see more details about these errors.
> - ![Warning](https://img.shields.io/badge/WARNING-yellow) Others validators return just return a warning. Therefore, just a warning indicates that the validation succeeded, but there are potential issues that should be addressed. Use the `-v` or `--verbose` option to see more details about these warnings and/or use the `--strict` option to treat warnings as errors.

## [`DuplicateKeysValidator`](../src/Validator/DuplicateKeysValidator.php)

Catches duplicate translation keys within the same file, which can cause unpredictable behavior in your application.

**Result:** ![Error](https://img.shields.io/badge/ERROR-red)

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

#### Console Output
```
Fixtures/examples/duplicate-keys/messages.en.xlf

- Error (DuplicateKeysValidator) the translation key `welcome` occurs multiple times (2x)


 [ERROR] Language validation failed with errors. See more details with the `-v`
         verbose option.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/duplicate-keys --only "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator"
```

**Example files:** Check `tests/Fixtures/examples/duplicate-keys/` to see the XLIFF file with duplicate ID attributes.

</details>

Most parsers will silently use the last occurrence, making your first translation unreachable. This validator ensures you catch these before they reach production.

> [!NOTE]
> In many formats, such errors are already caught by the IDEs or an interpreter. However, such problems can occur, especially with XLIFF files.

---

## [`DuplicateValuesValidator`](../src/Validator/DuplicateValuesValidator.php)

Identifies identical translation values that might indicate copy-paste errors or missing translations.

**Result:** ![Warning](https://img.shields.io/badge/WARNING-yellow)

### Example

**File: `errors.en.yaml`**
```yaml
validation:
  required: "This field is required"
  email: "This field is required"     # Same value as 'required'
  phone: "Please enter a valid phone"
  address: "Please enter a valid phone" # Copy-paste error
```

#### Console Output
```
Fixtures/examples/duplicate-values/errors.en.yaml

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

> [!TIP]
> While sometimes duplicate values are intentional, they often reveal incomplete translations or copy-paste mistakes.
> If you want to suppress these warnings, you can skip this validator using the `--skip` option or using the configuration file.
>
> Example command to skip this validator:
> ```bash
> composer validate-translations ./translations --skip "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateValuesValidator"
> ````
>
> or within your configuration file , e.g. `translation-validator.yaml`:
> ```yaml
> skip:
>   - MoveElevator\ComposerTranslationValidator\Validator\DuplicateKeysValidator
> ```

---

## [`EmptyValuesValidator`](../src/Validator/EmptyValuesValidator.php)

Hunts down empty or whitespace-only translation values that would display nothing to users.

**Result:** ![Warning](https://img.shields.io/badge/WARNING-yellow)

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

#### Console Output
```
Fixtures/examples/empty-values/navigation.de.xlf

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

> [!NOTE]
> Either provide proper translations or remove these entries entirely if they're not needed yet.

---

## [`EncodingValidator`](../src/Validator/EncodingValidator.php)

Ensures your files use proper UTF-8 encoding and catches sneaky Unicode issues that can break your app.

**Result:** ![Warning](https://img.shields.io/badge/WARNING-yellow)

### Example

**File: `special.en.json` (with BOM)**
```json
\xEF\xBB\xBF
{
  "currency": "Price: €99",
  "copyright": "© 2024 Company"
}
```

#### Console Output
```
special.en.json

EncodingValidator
  - Warning encoding issue: File contains UTF-8 Byte Order Mark (BOM)
  - Warning encoding issue: File contains invisible characters: Zero-width space, Zero-width no-break space

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

> [!NOTE]
> Encoding issues can cause mysterious character displays, especially with special symbols, emojis or non-Latin scripts.

---

## [`HtmlTagValidator`](../src/Validator/HtmlTagValidator.php)

Verifies HTML tags are consistent across all language versions: same tags, proper nesting, matching attributes.

**Result:** ![Warning](https://img.shields.io/badge/WARNING-yellow)

### Example

**File: `messages.en.yaml`**
```yaml
welcome: "Welcome <strong>new user</strong>!"
footer: 'Visit our <a href="/about" class="link">about page</a>'
```

**File: `messages.de.yaml`**
```yaml
welcome: "Willkommen <em>neuer Nutzer</em>!"  # <strong> became <em>
footer: 'Besuchen Sie unsere <a href="/about">Über-Seite</a>'  #  Missing class
```

#### Console Output
```
Fixtures/examples/html-tags/messages.de.yaml

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

---

## [`KeyNamingConventionValidator`](../src/Validator/KeyNamingConventionValidator.php)

Enforces consistent naming patterns for translation keys (requires configuration to activate).

**Result:** ![Warning](https://img.shields.io/badge/WARNING-yellow)

> [!NOTE]
> This validator requires a configuration file to specify a desired naming convention (e.g., `snake_case`, `camelCase`, etc.). If not configured, it try to detect the most common pattern used in your files and warns about inconsistencies.

### Example

**Configuration:**
```yaml
# translation-validator.yaml
validator-settings:
  KeyNamingConventionValidator:
    convention: snake_case
```

See the [configuration file documentation](config-file.md) for more details on how to set this up.

**File: `mixed.en.yaml`**
```yaml
user_name: "Username"
userEmail: "Email"              # camelCase
user-phone: "Phone"             # kebab-case
User.Address: "Address"         # Mixed styles
```

#### Console Output
```
Fixtures/examples/key-naming/mixed.en.yaml

  KeyNamingConventionValidator
    - Warning key naming convention violation: `userEmail` does not follow snake_case convention (suggestion: `user_email`)
    - Warning key naming convention violation: `user-phone` does not follow snake_case convention (suggestion: `user_phone`)
    - Warning key naming convention violation: `User.Address` does not follow snake_case convention (suggestion: `user.address`)

[WARNING] Language validation completed with warnings.
```

**Note:** The current fixture file contains only valid snake_case keys. To see validation errors, you would need to include keys that violate the naming convention (like camelCase or kebab-case).

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/key-naming --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyNamingConventionValidator" --config Fixtures/examples/key-naming/translation-validator.yaml -v
```

**Example files:** Check `tests/Fixtures/examples/key-naming/` to see the translation file with mixed naming conventions and the config file.

</details>

The following naming conventions are supported:

- `snake_case` - user_name, form_submit
- `camelCase` - userName, formSubmit
- `kebab-case` - user-name, form-submit
- `PascalCase` - UserName, FormSubmit
- `custom_pattern` - Define your own regex pattern

> [!NOTE]
> Dot notation (e.g., `user.name`, `form.submit`) is not supported by this validator, as it is typically used for nested structures rather than flat key names.

You can also define your own regex pattern using the `custom_pattern` option in the configuration file.

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

---

## [`MismatchValidator`](../src/Validator/MismatchValidator.php)

The breadfinder! Catches translation keys that exist in some language files but are missing from others.

**Result:** ![Warning](https://img.shields.io/badge/WARNING-yellow)

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

#### Console Output
```
Fixtures/examples/mismatch/buttons.de.yaml

  MismatchValidator
    - Warning  the translation key `edit` is missing from other translation files (`buttons.en.yaml`, `buttons.fr.yaml`)
    - Warning  the translation key `delete` is missing but present in other translation files (`buttons.en.yaml`, `buttons.fr.yaml`)
    - Warning  the translation key `duplicate` is missing but present in other translation files (`buttons.en.yaml`, `buttons.fr.yaml`)

+-----------------+-----------------+-----------------+-----------------+
| Translation Key | buttons.de.yaml | buttons.en.yaml | buttons.fr.yaml |
+-----------------+-----------------+-----------------+-----------------+
| edit            | Bearbeiten      | Edit            |                 |
| delete          |                 | Delete          | Supprimer       |
| duplicate       |                 |                 | Dupliquer       |
+-----------------+-----------------+-----------------+-----------------+


 [WARNING] Language validation completed with warnings.
```

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/mismatch --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator" -v
```

**Example files:** Check `tests/Fixtures/examples/mismatch/` to see the English, German, and French files with missing translation keys.

</details>

> [!TIP]
> This is usually the most valuable validator - it catches incomplete translations that would show key names to users instead of proper text.

---

## [`PlaceholderConsistencyValidator`](../src/Validator/PlaceholderConsistencyValidator.php)

Ensures placeholder patterns are consistent across languages so dynamic content works everywhere.

**Result:** ![Warning](https://img.shields.io/badge/WARNING-yellow)

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

#### Console Output
```
Fixtures/examples/placeholders/notifications.de.yaml

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

> [!WARNING]
> Mismatched placeholders will break variable substitution in your app, showing raw placeholder text to users.

---

## [`XliffSchemaValidator`](../src/Validator/XliffSchemaValidator.php)

Validates XLIFF files against official XML schemas to ensure they're structurally correct.

**Result:** ![Error](https://img.shields.io/badge/ERROR-red)

> [!IMPORTANT]
> This validator is only applicable to XLIFF files. It checks for schema compliance, missing required attributes and structural integrity.

### Example

**File: `malformed.xlf`**
```xml
<xliff version="1.2">
  <file source-language="en" target-language="de">
    <body>
      <trans-unit id="test">
        <source>Hello</source>
        <!-- Missing closing </trans-unit> tag -->
      </trans-unit>
      <trans-unit>  <!-- Missing required 'id' attribute -->
        <source>World</source>
        <target>Welt</target>
      </trans-unit>
    </body>
  </file>
<!-- Missing closing </xliff> tag -->
```

#### Console Output
```
tests/Fixtures/examples/xliff-schema/malformed.xlf

XliffSchemaValidator
  - Error Element '{urn:oasis:names:tc:xliff:document:1.2}file': The attribute 'original' is required but missing. (Line: 3) (Code: 1868)
  - Error Element '{urn:oasis:names:tc:xliff:document:1.2}trans-unit': The attribute 'id' is required but missing. (Line: 9) (Code: 1868)
  - Error Element '{urn:oasis:names:tc:xliff:document:1.2}trans-unit': Not all fields of key identity-constraint '{urn:oasis:names:tc:xliff:document:1.2}K_unit_id' evaluate to a node. (Line: 9) (Code:
1877)

[ERROR] Language validation failed with errors.
```

**Note:** The XLIFF file contains schema violations like missing required `id` attributes. The validator detects these structural problems but the specific error details would need verbose mode or schema-specific reporting.

<details>
<summary>Test this example locally</summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/xliff-schema --only "MoveElevator\\ComposerTranslationValidator\\Validator\\XliffSchemaValidator"
```

**Example files:** Check `tests/Fixtures/examples/xliff-schema/` to see the malformed XLIFF file with schema violations.

</details>

> [!IMPORTANT]
> Malformed XLIFF files can crash translation tools or cause parsing errors in your application.

---

## Running Specific Validators

You can run only the validators you need:

```bash
# Run only mismatch detection (from project root)
composer -d tests validate-translations ./translations --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator"

# Skip HTML validation for simple text projects
composer -d tests validate-translations ./translations --skip "MoveElevator\\ComposerTranslationValidator\\Validator\\HtmlTagValidator"

# Focus on critical issues only (multiple validators)
composer -d tests validate-translations ./translations --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator,MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator,MoveElevator\\ComposerTranslationValidator\\Validator\\XliffSchemaValidator"
```
