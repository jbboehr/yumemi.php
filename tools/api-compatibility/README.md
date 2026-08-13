# API Compatibility Tool

This isolated Composer project supplies Roave Backward Compatibility Check without constraining Yumemi's main PHP
8.2-8.5 development dependency matrix. The locked Roave 8.14 line runs on the PHP 8.2 development baseline; newer Roave
lines no longer support that PHP version.

Run the tool from the repository root:

```shell
composer check:bc
```

The command installs this project's locked dependencies, detects the latest stable Git tag, and compares it with
committed `HEAD`. It does not inspect uncommitted changes. See the project
[compatibility policy](../../docs/development/compatibility.md) for its scope and the treatment of findings.
