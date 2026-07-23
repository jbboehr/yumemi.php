.DEFAULT: all
.PHONY: all clean

all: src/Parser/Parser.php

clean:
	rm -f src/Parser/Parser.php

src/Parser/Parser.php: src/Parser/grammar.y vendor/mrsuh/php-bison-skeleton/src/php-skel.m4
	bison -S vendor/mrsuh/php-bison-skeleton/src/php-skel.m4 -o src/Parser/Parser.php src/Parser/grammar.y
	sed -i 's/__DOLLAR__/$$/g' src/Parser/Parser.php
