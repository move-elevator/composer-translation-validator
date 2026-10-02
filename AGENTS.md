# AGENTS.md

Guidance for coding agents working in this repository.

## Project overview

`move-elevator/composer-translation-validator` is a Composer plugin (and standalone CLI) that validates translation files (XLIFF, YAML, JSON, PHP). It provides the `validate-translations` command to find mismatches between language files, duplicate keys, schema issues and more.

- PHP: `~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0`
- Composer plugin API: `^1.0 || ^2.0`
- Symfony components (config, console, filesystem, translation, yaml): `^6.0 || ^7.0 || ^8.0`
- License: GPL-3.0-or-later

## Structure

```
src/
  Capability/      Composer command provider
  Command/         CLI commands and shared command behavior
  Config/          Configuration reading, factory, validation, defaults
  Enum/            Enums (naming conventions, locale match)
  FileDetector/    Detectors grouping related translation files into FileSet objects, plus registry
  Parser/          Parsers per format (Xliff, Yaml, Json, Php), plus registry and cache
  Result/          Issue, ValidationResult and CLI, JSON and GitHub renderers
  Service/         Orchestration of the validation pipeline
  Utility/         Helpers
  Validator/       Validators and registry
  Plugin.php       Composer plugin entry point
tests/src/         Tests mirroring src/, fixtures in tests/src/Fixtures/
bin/               Standalone executable
schema/            JSON schema for the configuration file
docs/              VitePress documentation site
```

Pipeline: Command, Collector, FileDetector, Parser, Validator, ResultRenderer. `ValidationOrchestrationService` coordinates it.

Adding components:
- Validator: extend `AbstractValidator`, implement `ValidatorInterface`, register in `ValidatorRegistry`.
- Parser: extend `AbstractParser`, implement `ParserInterface`, register in `ParserRegistry`.
- File detector: implement `DetectorInterface`, register in `FileDetectorRegistry`.

Configuration is auto-detected as `translation-validator.{php,json,yaml,yml}` in the project root. Validator defaults live in `src/Config/TranslationValidatorConfig.php`.

## Development commands

```bash
composer install
composer lint              # composer normalize, editorconfig and PHP-CS-Fixer (dry run)
composer fix               # apply all fixes
composer sca               # PHPStan
composer migration         # Rector
composer test              # PHPUnit without coverage
composer test:coverage     # PHPUnit with coverage (XDEBUG_MODE=coverage)
```

Documentation site (Node): `npm run docs:dev`, `npm run docs:build`, `npm run docs:preview`.

## Testing

- PHPUnit configuration: `phpunit.xml`, test directory `tests/src`.
- Single file: `vendor/bin/phpunit tests/src/Validator/MismatchValidatorTest.php`
- Single method: `vendor/bin/phpunit --filter testMethodName`
- Coverage reports are written to `.build/coverage/` (HTML in `.build/coverage/html/`).
- CI runs `composer test` on a matrix of PHP 8.2 to 8.5, Symfony 6 to 8 and Composer 2.2 to 2.9. A separate job runs `composer test:coverage` with Xdebug on PHP 8.4 and uploads to Coveralls.

## Code style and static analysis

- PHP-CS-Fixer with `konradmichalik/php-cs-fixer-preset` (`.php-cs-fixer.php`) and the doc block header fixer.
- PHPStan level 8 on `src` and `tests/src` with the Symfony and PHPUnit extensions (`phpstan.neon`).
- Rector (`rector.php`) for migrations.
- `.editorconfig` is enforced via `ec`. `composer.json` is normalized with `ergebnis/composer-normalize`.
- `composer-require-checker.json` is present for dependency checks.
- CI runs the CGL workflow (PHP 8.4) through a reusable workflow.

## Git workflow

- Commit format: `<type>: <description>`
- Types: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `perf`, `ci`
- No co-author trailers
- One commit per logical change
- Open a pull request with a description, ideally referencing an issue. All quality tools run on every pull request.
