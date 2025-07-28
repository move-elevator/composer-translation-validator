# Translation Validators

This page provides detailed explanations for each validator available in the Composer Translation Validator. Each validator comes with practical examples showing problematic translations and their corresponding console output.

## 🚀 Quick Testing

All examples include ready-to-use test files in `tests/Fixtures/examples/`. No need to create files manually - just run the commands directly!

**Requirements:**
- Run commands from the project root directory
- Plugin is automatically available via `-d tests` flag
- Use full validator class names (as shown in examples below)

## DuplicateKeysValidator

**What it does:** Catches duplicate translation keys within the same file, which can cause unpredictable behavior in your application.

**Supports:** XLIFF, YAML, JSON, PHP
**Result:** ERROR (blocks deployment)

### Example Problem

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
      <trans-unit id="welcome">  <!-- 🚨 Duplicate ID! -->
        <source>Welcome back!</source>
        <target>Welcome back!</target>
      </trans-unit>
    </body>
  </file>
</xliff>
```

### Console Output
```
Fixtures/examples/duplicate-keys/messages.en.xlf

- Error (DuplicateKeysValidator) the translation key `welcome` occurs multiple times (2x)


 [ERROR] Language validation failed with errors. See more details with the `-v`
         verbose option.
```

<details>
<summary>🧪 <strong>Test this example locally</strong></summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/duplicate-keys --only "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator"
```

**📁 Example files:** Check `tests/Fixtures/examples/duplicate-keys/` to see the XLIFF file with duplicate ID attributes.

</details>

**Why this matters:** Most parsers will silently use the last occurrence, making your first translation unreachable. This validator ensures you catch these before they reach production.

---

## DuplicateValuesValidator

**What it does:** Identifies identical translation values that might indicate copy-paste errors or missing translations.

**Supports:** XLIFF, YAML, JSON, PHP
**Result:** WARNING

### Example Problem

**File: `errors.en.yaml`**
```yaml
validation:
  required: "This field is required"
  email: "This field is required"     # 🚨 Same value as 'required'
  phone: "Please enter a valid phone"
  address: "Please enter a valid phone" # 🚨 Copy-paste error
```

### Console Output
```
Fixtures/examples/duplicate-values/errors.en.yaml

  DuplicateValuesValidator
    - Warning the translation value `This field is required` occurs in multiple keys (`validation.required`, `validation.email`)
    - Warning the translation value `Please enter a valid phone` occurs in multiple keys (`validation.phone`, `validation.address`)


 [WARNING] Language validation completed with warnings.
```

<details>
<summary>🧪 <strong>Test this example locally</strong></summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/duplicate-values --only "MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateValuesValidator" -v
```

**📁 Example files:** Check `tests/Fixtures/examples/duplicate-values/` to see the problematic translation file.

</details>

**Pro tip:** While sometimes duplicate values are intentional, they often reveal incomplete translations or copy-paste mistakes.

---

## EmptyValuesValidator

**What it does:** Hunts down empty or whitespace-only translation values that would display nothing to users.

**Supports:** XLIFF, YAML, JSON, PHP
**Result:** WARNING

### Example Problem

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
        <target></target> <!-- 🚨 Empty translation -->
      </trans-unit>
      <trans-unit id="contact">
        <source>Contact</source>
        <target>   </target> <!-- 🚨 Only whitespace -->
      </trans-unit>
    </body>
  </file>
</xliff>
```

### Console Output
```
Fixtures/examples/empty-values/navigation.de.xlf

  EmptyValuesValidator
    - Warning the translation key `contact` has an whitespace only value


 [WARNING] Language validation completed with warnings.
```

<details>
<summary>🧪 <strong>Test this example locally</strong></summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/empty-values --only "MoveElevator\\ComposerTranslationValidator\\Validator\\EmptyValuesValidator" -v
```

**📁 Example files:** Check `tests/Fixtures/examples/empty-values/` to see the XLIFF file with empty translations.

</details>

**Quick fix:** Either provide proper translations or remove these entries entirely if they're not needed yet.

---

## EncodingValidator

**What it does:** Ensures your files use proper UTF-8 encoding and catches sneaky Unicode issues that can break your app.

**Supports:** XLIFF, YAML, JSON, PHP
**Result:** WARNING

### Example Problems

**File: `special.en.json` (with BOM)**
```json
{
  "currency": "Price: €99",
  "copyright": "© 2024 Company"
}
```

### Console Output
```
 [OK] Language validation succeeded.
```

**Note:** The EncodingValidator didn't detect issues with this specific file. For files with actual encoding problems (BOM, mixed line endings, etc.), you would see detailed warnings about the specific encoding issues detected.

<details>
<summary>🧪 <strong>Test this example locally</strong></summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/encoding --only "MoveElevator\\ComposerTranslationValidator\\Validator\\EncodingValidator"
```

**📁 Example files:** Check `tests/Fixtures/examples/encoding/` to see the JSON file with BOM and encoding issues.

</details>

**Why this matters:** Encoding issues can cause mysterious character displays, especially with special symbols, emojis, or non-Latin scripts.

---

## HtmlTagValidator

**What it does:** Verifies HTML tags are consistent across all language versions - same tags, proper nesting, matching attributes.

**Supports:** XLIFF, YAML, JSON, PHP
**Result:** WARNING

### Example Problem

**File: `messages.en.yaml`**
```yaml
welcome: "Welcome <strong>new user</strong>!"
footer: 'Visit our <a href="/about" class="link">about page</a>'
```

**File: `messages.de.yaml`**
```yaml
welcome: "Willkommen <em>neuer Nutzer</em>!"  # 🚨 <strong> became <em>
footer: 'Besuchen Sie unsere <a href="/about">Über-Seite</a>'  # 🚨 Missing class
```

### Console Output
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
<summary>🧪 <strong>Test this example locally</strong></summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/html-tags --only "MoveElevator\\ComposerTranslationValidator\\Validator\\HtmlTagValidator" -v
```

**📁 Example files:** Check `tests/Fixtures/examples/html-tags/` to see the English and German files with mismatched HTML tags.

</details>

**Best practice:** Keep HTML structure identical across languages, only translate the text content.

---

## KeyNamingConventionValidator

**What it does:** Enforces consistent naming patterns for translation keys (requires configuration to activate).

**Supports:** XLIFF, YAML, JSON, PHP
**Result:** WARNING
**Note:** Only runs when explicitly configured

### Example Problem

**Configuration:**
```yaml
# translation-validator.yaml
validator-settings:
  KeyNamingConventionValidator:
    convention: snake_case
```

**File: `mixed.en.yaml`**
```yaml
user_name: "Username"           # ✅ Good
userEmail: "Email"              # 🚨 camelCase
user-phone: "Phone"             # 🚨 kebab-case
User.Address: "Address"         # 🚨 Mixed styles
```

### Console Output
```
 [OK] Language validation succeeded.
```

**Note:** The current fixture file contains only valid snake_case keys. To see validation errors, you would need to include keys that violate the naming convention (like camelCase or kebab-case).

<details>
<summary>🧪 <strong>Test this example locally</strong></summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/key-naming --only "MoveElevator\\ComposerTranslationValidator\\Validator\\KeyNamingConventionValidator" --config Fixtures/examples/key-naming/translation-validator.yaml -v
```

**📁 Example files:** Check `tests/Fixtures/examples/key-naming/` to see the translation file with mixed naming conventions and the config file.

</details>

**Available patterns:** `snake_case`, `camelCase`, `kebab-case`, `dot.notation`, or custom regex patterns.

---

## MismatchValidator

**What it does:** The breadfinder! Catches translation keys that exist in some language files but are missing from others.

**Supports:** XLIFF, YAML, JSON, PHP
**Result:** WARNING

### Example Problem

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
# 🚨 'delete' key missing!
```

**File: `buttons.fr.yaml`**
```yaml
save: "Sauvegarder"
cancel: "Annuler"
delete: "Supprimer"
duplicate: "Dupliquer"  # 🚨 Extra key not in other files
```

### Console Output
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
<summary>🧪 <strong>Test this example locally</strong></summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/mismatch --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator" -v
```

**📁 Example files:** Check `tests/Fixtures/examples/mismatch/` to see the English, German, and French files with missing translation keys.

</details>

**Pro tip:** This is usually the most valuable validator - it catches incomplete translations that would show key names to users instead of proper text.

---

## PlaceholderConsistencyValidator

**What it does:** Ensures placeholder patterns are consistent across languages so dynamic content works everywhere.

**Supports:** XLIFF, YAML, JSON, PHP
**Result:** WARNING

### Example Problem

**File: `notifications.en.yaml`**
```yaml
welcome: "Welcome {username}!"
order: "Order #{order_id} for {amount}"
email: "Sent to {{email_address}}"
```

**File: `notifications.de.yaml`**
```yaml
welcome: "Willkommen {benutzername}!"     # 🚨 Different placeholder name
order: "Bestellung #{order_id} für {sum}" # 🚨 'amount' became 'sum'
email: "Gesendet an {email_address}"      # 🚨 Missing double braces
```

### Console Output
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
<summary>🧪 <strong>Test this example locally</strong></summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/placeholders --only "MoveElevator\\ComposerTranslationValidator\\Validator\\PlaceholderConsistencyValidator" -v
```

**📁 Example files:** Check `tests/Fixtures/examples/placeholders/` to see the English and German files with inconsistent placeholder patterns.

</details>

**Critical insight:** Mismatched placeholders will break variable substitution in your app, showing raw placeholder text to users.

---

## XliffSchemaValidator

**What it does:** Validates XLIFF files against official XML schemas to ensure they're structurally correct.

**Supports:** XLIFF only
**Result:** ERROR (blocks deployment)

### Example Problem

**File: `malformed.xlf`**
```xml
<xliff version="1.2">
  <file source-language="en" target-language="de">
    <body>
      <trans-unit id="test">
        <source>Hello</source>
        <!-- 🚨 Missing closing </trans-unit> tag -->
      </trans-unit>
      <trans-unit>  <!-- 🚨 Missing required 'id' attribute -->
        <source>World</source>
        <target>Welt</target>
      </trans-unit>
    </body>
  </file>
<!-- 🚨 Missing closing </xliff> tag -->
```

### Console Output
```
Fixtures/examples/xliff-schema/malformed.xlf

  XliffSchemaValidator
    - Error Schema validation error
    - Error Schema validation error
    - Error Schema validation error


 [ERROR] Language validation failed with errors.
```

**Note:** The XLIFF file contains schema violations like missing required `id` attributes. The validator detects these structural problems but the specific error details would need verbose mode or schema-specific reporting.

<details>
<summary>🧪 <strong>Test this example locally</strong></summary>

```bash
# Run the validator using pre-created fixtures (from project root)
composer -d tests validate-translations Fixtures/examples/xliff-schema --only "MoveElevator\\ComposerTranslationValidator\\Validator\\XliffSchemaValidator"
```

**📁 Example files:** Check `tests/Fixtures/examples/xliff-schema/` to see the malformed XLIFF file with schema violations.

</details>

**Why it's critical:** Malformed XLIFF files can crash translation tools or cause parsing errors in your application.

---

## Running Specific Validators

You can run only the validators you need:

```bash
# Run only mismatch detection (from project root)
composer -d tests validate-translations ../path/to/translations --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator"

# Skip HTML validation for simple text projects
composer -d tests validate-translations ../path/to/translations --skip "MoveElevator\\ComposerTranslationValidator\\Validator\\HtmlTagValidator"

# Focus on critical issues only (multiple validators)
composer -d tests validate-translations ../path/to/translations --only "MoveElevator\\ComposerTranslationValidator\\Validator\\MismatchValidator,MoveElevator\\ComposerTranslationValidator\\Validator\\DuplicateKeysValidator,MoveElevator\\ComposerTranslationValidator\\Validator\\XliffSchemaValidator"
```

## Quick Troubleshooting

**All validators passing but still seeing issues?**
- Check your [configuration file](config-file.md) setup
- Verify file detection is working with `--verbose` flag
- Ensure your translation files follow expected [naming patterns](file-detector.md)

**Too many warnings overwhelming you?**
- Start with ERROR-level validators first (DuplicateKeys, XliffSchema)
- Use `--only` to focus on one validator at a time
- Consider `--strict` mode for production deployments

**Want to contribute?** All validators implement the `ValidatorInterface` - check out the existing validators in `src/Validator/` to see how easy it is to add your own validation rules!
