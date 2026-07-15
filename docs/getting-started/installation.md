# Installation

## Requirements

- PHP 8.2 or higher
- Composer 2.0 or higher

## Install via Composer

Add the plugin as a development dependency:

```bash
composer require --dev move-elevator/composer-translation-validator
```

The plugin will be automatically registered with Composer and the `validate-translations` command becomes available.

This is the recommended way to use the tool inside a Composer/PHP project.

## Standalone PHAR

For CI pipelines or non-Composer projects, a standalone PHAR is available. It runs as its own process with all dependencies bundled, so it never conflicts with your project's dependencies.

Download the latest PHAR (and its checksum) from the [GitHub releases page](https://github.com/move-elevator/composer-translation-validator/releases):

```bash
curl -L -o composer-translation-validator.phar \
  https://github.com/move-elevator/composer-translation-validator/releases/latest/download/composer-translation-validator.phar
curl -L -o composer-translation-validator.phar.sha256 \
  https://github.com/move-elevator/composer-translation-validator/releases/latest/download/composer-translation-validator.phar.sha256

# Verify the download
sha256sum -c composer-translation-validator.phar.sha256

chmod +x composer-translation-validator.phar
```

Run it directly against your translation folders:

```bash
./composer-translation-validator.phar ./translations --recursive
```

The PHAR accepts the same arguments and options as the `validate-translations` command.

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
