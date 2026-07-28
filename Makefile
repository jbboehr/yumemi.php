.DEFAULT: all
.PHONY: all clean generate-catalog

UDUNITS_XML_FILES := \
	$(UDUNITS_XML_DIR)/udunits2-prefixes.xml \
	$(UDUNITS_XML_DIR)/udunits2-base.xml \
	$(UDUNITS_XML_DIR)/udunits2-derived.xml \
	$(UDUNITS_XML_DIR)/udunits2-accepted.xml \
	$(UDUNITS_XML_DIR)/udunits2-common.xml

all: src/Parser/Parser.php

clean:
	rm -f src/Parser/Parser.php

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
