# Installation

## Requirements

- PHP 8.1 or higher
- Composer 2.0 or higher

## Install via Composer

Add the plugin as a development dependency:

```bash
composer require --dev move-elevator/composer-translation-validator
```

The plugin will be automatically registered with Composer and the `validate-translations` command becomes available.

## Verify Installation

Check that the command is available:

```bash
composer validate-translations --help
```

You should see the command help output with all available options.

## Updating

Update to the latest version:

```bash
composer update move-elevator/composer-translation-validator
```

## Uninstalling

Remove the plugin:

```bash
composer remove move-elevator/composer-translation-validator
```
