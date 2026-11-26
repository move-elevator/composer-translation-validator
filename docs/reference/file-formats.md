# File Formats

The Composer Translation Validator supports multiple translation file formats commonly used in PHP frameworks and applications.

## XLIFF (XML Localization Interchange File Format)

- **Extensions**: `.xlf`, `.xliff`
- **Frameworks**: TYPO3 CMS, Symfony (optional)

### Structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="de" original="messages">
    <body>
      <trans-unit id="welcome">
        <source>Welcome</source>
        <target>Willkommen</target>
      </trans-unit>
      <trans-unit id="user.greeting">
        <source>Hello, %name%!</source>
        <target>Hallo, %name%!</target>
      </trans-unit>
    </body>
  </file>
</xliff>
```

### Key Features

- XML-based industry standard format
- Supports translation metadata and states
- Schema validation available (XLIFF 1.2 and 2.0)
- Target-language consistency check between filename locale and declared `target-language`/`trgLang`
- Keys are the `id` values used as flat strings, `user.greeting` is one key and does not nest
- XLIFF 1.2 and 2.x are supported
- Files with a `DOCTYPE` declaration are rejected

### XLIFF 2.x

XLIFF 2.x uses `<unit>` with `<segment>`, and `srcLang` and `trgLang` on the root element.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="2.0" xmlns="urn:oasis:names:tc:xliff:document:2.0" srcLang="en" trgLang="de">
  <file id="messages">
    <unit id="welcome">
      <segment>
        <source>Welcome</source>
        <target>Willkommen</target>
      </segment>
    </unit>
  </file>
</xliff>
```

## YAML (YAML Ain't Markup Language)

- **Extensions**: `.yaml`, `.yml`
- **Frameworks**: Symfony (primary), Laravel (supported)

### Structure

```yaml
welcome: "Welcome to our application"
user:
  greeting: "Hello, %name%!"
  logout: "Logout"
navigation:
  home: "Home"
  about: "About Us"
```

### Key Features

- Human-readable format
- Native nested key support
- Clean syntax without quotes (optional)
- Supports multiline strings
- A flat key with dots (`app.title: Hello`) resolves to the same key as nested `app: {title: Hello}`
- An empty file counts as having no keys

## JSON (JavaScript Object Notation)

- **Extensions**: `.json`
- **Frameworks**: Laravel, Symfony, Vue.js, React

### Structure

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

### Key Features

- Universal data interchange format
- Native nested key support
- Wide tool support
- Frontend framework compatible
- A flat key with dots (`"app.title": "Hello"`) resolves to the same key as nested `"app": {"title": "Hello"}`
- The top level must be an object, a list or scalar throws an error

## PHP Arrays

- **Extensions**: `.php`
- **Frameworks**: Laravel (primary), Symfony (supported)

### Structure

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

### Key Features

- Native PHP format
- IDE autocomplete support
- A flat key with dots (`'app.title' => 'Hello'`) resolves to the same key as nested `'app' => ['title' => 'Hello']`

The validator parses the file into a syntax tree and never executes it, because running translation files from a repository would run the code in them. The file must `return` an array literal with a string or integer key for every entry. Values can be strings, integers, floats, `true`, `false`, `null`, negative numbers and nested arrays. Other constants, function calls and expressions throw an error.

In YAML, JSON and PHP, if a key exists both flat and nested, both entries appear in the key list and the later definition provides the value.

## Placeholder Formats

Different frameworks use different placeholder formats:

| Framework | Format | Example |
|-----------|--------|---------|
| Symfony | `%name%` | `Hello, %name%!` |
| Laravel | `:name` | `Hello, :name!` |
| Twig/Vue | `{{ name }}` or `{{name}}` | `Hello, {{name}}!` |
| ICU | `{name}` | `Hello, {name}!` |
| printf | `%s`, `%d`, `%1$s` | `Hello, %s!` |

The `PlaceholderConsistencyValidator` recognizes exactly these formats. Names consist of letters, digits and underscores and start with a letter or underscore. Twig placeholders allow spaces inside the braces. printf placeholders recognize the conversions `s`, `d`, `c`, `o`, `x`, `X`, `e`, `E`, `f`, `F`, `g`, `G`, `a` and `A`, with an optional position such as `1$`.

## Framework-Specific Patterns

### TYPO3 CMS

- **Format**: XLIFF
- **Location**: `Resources/Private/Language/`
- **Naming**: `locallang.xlf`, `de.locallang.xlf`

### Symfony

- **Format**: YAML (primary), XLIFF, PHP
- **Location**: `translations/`
- **Naming**: `messages.en.yaml`, `validators.de.yaml`

### Laravel

- **Format**: PHP Arrays, JSON
- **Location**: `resources/lang/` (≤8) or `lang/` (≥9)
- **Naming**: `en/messages.php`, `de/messages.php`

## See Also

- [File Detection](/reference/file-detection) - How files are grouped
- [Validators](/reference/validators) - Available validation rules
