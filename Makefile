.DEFAULT: all
.PHONY: all clean coverage-branch docs docs-check docs-serve generate-catalog test-consumer test-consumer-archive \
	test-consumer-illuminate-cache test-consumer-illuminate-cache-archive

BRANCH_COVERAGE_OUTPUT ?= coverage/branch
BRANCH_COVERAGE_SOURCE ?= src/Number
BRANCH_COVERAGE_TESTS ?=
BRANCH_COVERAGE_XDEBUG_ERROR := Xdebug is not loaded; enter nix develop .\#xdebug.
ILLUMINATE_CACHE_MAJOR ?= 12

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
		--path-coverage \
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

test-consumer:
	tests/Consumer/run source

test-consumer-archive:
	tests/Consumer/run archive

test-consumer-illuminate-cache:
	tests/Consumer/run source illuminate-cache $(ILLUMINATE_CACHE_MAJOR)

test-consumer-illuminate-cache-archive:
	tests/Consumer/run archive illuminate-cache $(ILLUMINATE_CACHE_MAJOR)

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
