.DEFAULT: all
.PHONY: all clean coverage-branch coverage-branch-parallel docs docs-check docs-serve generate-catalog \
	probator-unit-parser test-consumer test-consumer-archive test-udunits2

BRANCH_COVERAGE_OUTPUT ?= coverage/branch
BRANCH_COVERAGE_SOURCE ?= src/Number
BRANCH_COVERAGE_TESTS ?=
BRANCH_COVERAGE_XDEBUG_ERROR := Xdebug is not loaded; enter nix develop .\#xdebug.
PROBATOR_OPTIONS ?=
PROBATOR_UNIT_PARSER_WORKDIR ?= tmp/probator/unit-expression
UDUNITS2_BIN ?= udunits2
UDUNITS2_XML ?= $(UDUNITS_XML_DIR)/udunits2.xml

UDUNITS_XML_FILES := \
	$(UDUNITS_XML_DIR)/udunits2-prefixes.xml \
	$(UDUNITS_XML_DIR)/udunits2-base.xml \
	$(UDUNITS_XML_DIR)/udunits2-derived.xml \
	$(UDUNITS_XML_DIR)/udunits2-accepted.xml \
	$(UDUNITS_XML_DIR)/udunits2-common.xml

all: src/Parser/Parser.php

clean:
	rm -f src/Parser/Parser.php

coverage-branch:
	@php -r 'if (!extension_loaded("xdebug")) { fwrite(STDERR, "$(BRANCH_COVERAGE_XDEBUG_ERROR)\n"); exit(1); }'
	@mkdir -p "$(BRANCH_COVERAGE_OUTPUT)"
	php -d xdebug.mode=coverage vendor/bin/phpunit \
		--configuration phpunit.branch.xml.dist \
		--coverage-filter "$(BRANCH_COVERAGE_SOURCE)" \
		--coverage-html "$(BRANCH_COVERAGE_OUTPUT)/html" \
		--coverage-text="$(BRANCH_COVERAGE_OUTPUT)/coverage.txt" \
		$(BRANCH_COVERAGE_TESTS)

coverage-branch-parallel:
	@php -r 'if (!extension_loaded("xdebug")) { fwrite(STDERR, "$(BRANCH_COVERAGE_XDEBUG_ERROR)\n"); exit(1); }'
	@mkdir -p "$(BRANCH_COVERAGE_OUTPUT)"
	php -d xdebug.mode=coverage vendor/bin/paratest \
		--functional \
		--configuration phpunit.branch.xml.dist \
		--passthru-php="'-d' 'xdebug.mode=coverage'" \
		--coverage-filter "$(BRANCH_COVERAGE_SOURCE)" \
		--coverage-html "$(BRANCH_COVERAGE_OUTPUT)/html" \
		--coverage-text="$(BRANCH_COVERAGE_OUTPUT)/coverage.txt" \
		$(BRANCH_COVERAGE_TESTS)

docs:
	mdbook build docs

docs-check: docs
	php tests/Documentation/check-generated-links.php build/docs

docs-serve:
	mdbook serve docs --hostname 127.0.0.1

probator-unit-parser:
	@mkdir -p "$(PROBATOR_UNIT_PARSER_WORKDIR)/corpus"
	@cp -n probator/corpus/unit-expression/*.txt "$(PROBATOR_UNIT_PARSER_WORKDIR)/corpus/"
	@cd "$(PROBATOR_UNIT_PARSER_WORKDIR)" && \
		"$(CURDIR)/vendor/bin/php-fuzzer" fuzz $(PROBATOR_OPTIONS) \
		"$(CURDIR)/probator/unit-expression.php" corpus

test-consumer:
	tests/Consumer/run source

test-consumer-archive:
	tests/Consumer/run archive

test-udunits2:
	@command -v "$(UDUNITS2_BIN)" >/dev/null || { \
		echo 'udunits2 is not available; enter the Nix dev shell or specify UDUNITS2_BIN.' >&2; \
		exit 1; \
	}
	@test -f "$(UDUNITS2_XML)" || { \
		echo 'The UDUNITS2 XML database is not available; enter the Nix dev shell or specify UDUNITS2_XML.' >&2; \
		exit 1; \
	}
	UDUNITS2_BIN="$$(command -v "$(UDUNITS2_BIN)")" UDUNITS2_XML="$(UDUNITS2_XML)" \
		php vendor/bin/phpunit --group udunits2 --no-coverage

ifneq ($(strip $(UDUNITS_XML_DIR)),)
generate-catalog: $(UDUNITS_XML_FILES)
endif

generate-catalog:
	@test -n "$(UDUNITS_XML_DIR)" || { \
		echo 'UDUNITS_XML_DIR is not set; enter the Nix dev shell or specify UDUNITS_XML_DIR.' >&2; \
		exit 1; \
	}
	bin/generate-udunits2-catalog data/udunits2.php $(UDUNITS_XML_FILES)

src/Parser/Parser.php: src/Parser/grammar.y vendor/mrsuh/php-bison-skeleton/src/php-skel.m4
	bison -S vendor/mrsuh/php-bison-skeleton/src/php-skel.m4 -o src/Parser/Parser.php src/Parser/grammar.y
	sed -i 's/__DOLLAR__/$$/g' src/Parser/Parser.php
