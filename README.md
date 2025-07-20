# PHP Code Indexer

A tool for indexing PHP code elements including functions, classes, methods, properties, parameters, PHPDoc annotations,
attributes and their relative file paths to enable efficient searching using `xpath`.

## Overview

The indexer performs the following steps:

1. Uses symfony/finder to locate PHP files
2. Detects which files have changed, been added or removed
3. Indexes the code elements in an XML data structure
4. Stores the index in an easily searchable XML file
5. Enables searching via XPath queries

## Command line Usage

```bash
php index.php --out index.xml --include 'src/{*,**/*}.php' --include 'tests/{*,**/*}.php'
```

### Explanation

| Parameter                   | Short        | Required | Description                                                            |
|-----------------------------|--------------|----------|------------------------------------------------------------------------|
| `--out <file>`              | `-o <file>`  | Yes      | Path to the output XML file for storing the index                      |
| `--include <dir>`           | `-i <file>`  | Yes      | Directory to include for indexing; can be used multiple times          |
| `--exclude <pattern>`       | `-e <file>`  | No       | Glob pattern to exclude files or folders from the included directories |
| `--working-directory <dir>` | `-w <file>`  | No       | Sets the base directory for all relative paths                         |

Notes:
- At least one `--include` is required.
- `--exclude` applies only within the scope of the specified `--include` paths.
- Patterns for `--exclude` support common glob syntax:
  - `*` matches any string.
  - `xyz/*.*` matches any file with an extension.
  - `**/XyzTest.php` matches directories recursively.
  - `XyzTest.{php,inc}` matches multiple file extensions.
  - `src/{*,**/*}.php` matches all `.php` files in the `src` directory and its subdirectories.
- If --working-directory is not set, the current working directory is used.

## Usage Example

Find all attributes of class-methods with a specific name:
`//class/method/attribute[@name='NS\\MyAttribute']`

First, index your PHP files:

```php
use PhpLocate\UpdateIndexService;
use Psr\Log\NullLogger;
use Symfony\Component\Finder\Finder;

$files = (new Finder())
    ->in(__DIR__ . '/src')
    ->name('*.php');

$service = new UpdateIndexService(new NullLogger());
$service->updateIndex(indexPath: __DIR__ . '/index.xml', files: $files);
```

Then, search the index using XPath:

```php
use PhpLocate\Index;

$index = Index::fromFile(__DIR__ . '/index.xml');
$path = $index->getFirstString("/files/file[class/method/attribute[@name='NS\\MyAttribute']]/@path");
echo $path;
```

# Progress

- [x] Functions
  - [x] Attributes
    - [x] Arguments
  - [x] Parameters
    - [x] Attributes
      - [ ] Arguments
    - [x] Type hint
  - [x] Return type
  - [ ] PHPDoc annotations
  - [x] Class definitions
    - [ ] Attributes
      - [ ] Arguments
    - [ ] PHPDoc annotations
    - [x] Final mark
    - [x] Abstract mark
    - [x] Implementing Interfaces
    - [x] Extending class
    - [x] Methods
      - [x] Attributes
        - [x] Arguments 
      - [x] Visibility
      - [x] Static mark
      - [x] Final mark
      - [x] Abstract mark
      - [x] Constructor methods
      - [x] Parameters
        - [x] Attributes
          - [ ] Arguments
        - [x] Type hint
      - [x] Return type
    - [ ] Traits (merging methods and properties into classes)
      - [ ] Attributes
        - [ ] Arguments
      - [ ] Constants ...
        - [ ] Attributes
          - [ ] Arguments
      - [ ] Properties ...
        - [ ] Attributes
          - [ ] Arguments
      - [ ] Methods ...
        - [ ] Attributes
          - [ ] Arguments