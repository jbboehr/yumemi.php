# The “Probator”

Yumemi has a manual, coverage-guided “probator” target for the unit-expression parser. It checks parser robustness, AST
canonical-form round trips, and runtime parser/formatter round trips.

Run it until interrupted:

```console
composer probator:unit-parser
```

For a bounded experiment, pass runner options through Make:

```console
make probator-unit-parser PROBATOR_OPTIONS='--max-runs=100000'
```

The Make target copies the committed seeds from `probator/corpus/unit-expression/` into an ignored, evolving corpus
under `tmp/probator/unit-expression/corpus/`. Crash inputs are written beside that working corpus. Preserve an input by
adding a focused deterministic test; do not rely on the mutable corpus as regression coverage.

Replay or minimize a crash from the ignored working directory with:

```console
cd tmp/probator/unit-expression
../../../vendor/bin/php-fuzzer run-single ../../../probator/unit-expression.php crash-HASH.txt
../../../vendor/bin/php-fuzzer minimize-crash ../../../probator/unit-expression.php crash-HASH.txt
```
