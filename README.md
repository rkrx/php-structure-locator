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

## Usage Example

Find all attributes of class-methods with a specific name:
`//class/method/attribute[@name='NS\\MyAttribute']`

# Progress

- [x] Functions
  - [ ] Parameters
    - [ ] Attributes
    - [x] Type hint
  - [ ] Return type
  - [ ] PHPDoc annotations
- [x] Class definitions
  - [ ] Attributes
  - [ ] PHPDoc annotations
  - [x] Final mark
  - [x] Abstract mark
  - [x] Implementing Interfaces
  - [x] Extending class
  - [ ] Traits
  - [x] Methods
    - [x] Attributes
      - [x] Arguments 
    - [x] Visibility
    - [x] Static mark
    - [x] Final mark
    - [x] Abstract mark
    - [x] Constructor methods
    - [x] Parameters
      - [ ] Attributes
      - [x] Type hint
    - [x] Return type
