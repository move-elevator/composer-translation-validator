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
- Schema validation available (XLIFF 1.2)
- Nested keys via ID dots: `user.greeting`

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
- Supports PHP expressions and constants
- No parsing overhead
- IDE autocomplete support

## Placeholder Formats

Different frameworks use different placeholder formats:

| Framework | Format | Example |
|-----------|--------|---------|
| Symfony | `%name%` | `Hello, %name%!` |
| Laravel | `:name` | `Hello, :name!` |
| Twig/Vue | `{{ name }}` or `{{name}}` | `Hello, {{name}}!` |
| ICU | `{name}` | `Hello, {name}!` |

The `PlaceholderConsistencyValidator` recognizes all these formats.

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
