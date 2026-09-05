#!/usr/bin/env bash

# Copyright (c) anno Domini nostri Jesu Christi MMXXVI, John Boehr & contributors
# SPDX-License-Identifier: AGPL-3.0-only WITH romic-exception

set -euo pipefail

script_dir=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
cd "$script_dir/.."

generation_dir=$(mktemp -d)
trap 'rm -f -- "$generation_dir"/*; rmdir -- "$generation_dir"' EXIT

cp vendor/mrsuh/php-bison-skeleton/src/{c-like.m4,lalr1.php,php.m4,php-skel.m4} "$generation_dir/"
cp src/Parser/grammar.y "$generation_dir/"
patch --batch --fuzz=0 -d "$generation_dir" -p1 < "$script_dir/php-bison-lac.patch"
(
    cd "$generation_dir"
    LC_ALL=C bison \
        --file-prefix-map="Parser.php=src/Parser/Parser.php" \
        --file-prefix-map="grammar.y=src/Parser/grammar.y" \
        -S ./php-skel.m4 -o Parser.php grammar.y
)
sed 's/__DOLLAR__/$/g' "$generation_dir/Parser.php" > src/Parser/Parser.php
