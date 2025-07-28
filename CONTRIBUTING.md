# Contributing

Thank you for considering contributing to this project! Every contribution is welcome and helps improve the quality of the project. To ensure a smooth process and maintain high code quality, please follow the steps below.

## Requirements

- PHP >= 8.2
- Composer >= 2.0

## Preparation

```bash
# Clone repository
git clone https://github.com/move-elevator/composer-translation-validator.git
cd composer-translation-validator

# Install dependencies
composer install
```

## Run linters

```bash
# All linters
composer lint

# Specific linters
composer lint:composer
composer lint:editorconfig
composer lint:php

# Fix all CGL issues
composer fix

# Fix specific CGL issues
composer fix:composer
composer fix:editorconfig
composer fix:php
```

## Run static code analysis

```bash
# All static code analyzers
composer sca

# Specific static code analyzers
composer sca:php
```

## Run tests

```bash
# All tests
composer test

# All tests with code coverage
composer test:coverage
```

### Test reports

Code coverage reports are saved in .build/coverage. You can open the latest HTML report as follows:

```bash
open .build/coverage/html/index.html
```

## Submit a pull request

After completing your work, **open a pull request** and provide a description of your changes. Ideally, your PR should reference an issue that explains the problem you are addressing.

All mentioned code quality tools will run automatically on every pull request for all supported PHP versions. For more details, see the relevant [workflows][1].

[1]: .github/workflows
