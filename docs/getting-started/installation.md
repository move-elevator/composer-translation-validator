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

## GitHub Action

For GitHub Actions, use the bundled composite action instead of downloading the PHAR manually:

```yaml
- name: Validate translations
  uses: move-elevator/composer-translation-validator@1.6.0
  with:
    args: ./translations --recursive
```

Inputs:

| Input                | Description                                                          | Default  |
|----------------------|------------------------------------------------------------------------|----------|
| `version`            | Release tag to use (e.g. `1.6.0`). Defaults to the latest release.      | `latest` |
| `args`               | Arguments passed to the PHAR, e.g. paths and options.                   | `.`      |
| `working-directory`  | Directory the PHAR is executed in.                                     | `.`      |

The action downloads the PHAR for the pinned (or latest) release, verifies its checksum, and runs it. PHP must already be available on the runner, which is the case on GitHub-hosted runners; otherwise set it up beforehand with [`shivammathur/setup-php`](https://github.com/shivammathur/setup-php).

## GitLab CI Template

For GitLab CI, include the hosted template and extend its job:

```yaml
include:
  - remote: 'https://raw.githubusercontent.com/move-elevator/composer-translation-validator/main/.gitlab/ci/translation-validator.yml'

validate-translations:
  extends: .translation-validator
  variables:
    TRANSLATION_VALIDATOR_ARGS: "./translations --recursive"
```

The `TRANSLATION_VALIDATOR_VERSION` variable pins a release tag (defaults to `latest`), the same way the GitHub Action's `version` input does.

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
