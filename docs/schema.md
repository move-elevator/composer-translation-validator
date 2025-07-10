# Schema Configuration

### `paths`
* **Type**: `array<string>`
* **Default**: `[]`
* **Description**: Array of directory paths to scan for translation files

### `validators`
* **Type**: `array<string>`
* **Default**: `[]` (uses all available validators)
* **Description**: Array of validator class names to use for validation

### `file-detectors`
* **Type**: `array<string>`
* **Default**: `[]` (uses default file detector)
* **Description**: Array of file detector class names to use for file grouping

### `only`
* **Type**: `array<string>`
* **Default**: `[]`
* **Description**: Array of validator class names to run exclusively (overrides `validators`)

### `skip`
* **Type**: `array<string>`
* **Default**: `[]`
* **Description**: Array of validator class names to skip

### `exclude`
* **Type**: `array<string>`
* **Default**: `[]`
* **Description**: Array of glob patterns for files/directories to exclude from validation

### `strict`
* **Type**: `boolean`
* **Default**: `false`
* **Description**: Whether to treat warnings as errors

### `dry-run`
* **Type**: `boolean`
* **Default**: `false`
* **Description**: Whether to run in dry-run mode (no errors thrown)

### `format`
* **Type**: `string`
* **Default**: `cli`
* **Options**: `cli`, `json`
* **Description**: Output format for validation results

### `verbose`
* **Type**: `boolean`
* **Default**: `false`
* **Description**: Whether to enable verbose output
