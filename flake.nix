{
  description = "jbboehr/yumemi";

  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs/nixos-26.05";
    flake-utils = {
      url = "github:numtide/flake-utils";
    };
    nix-github-actions = {
      url = "github:nix-community/nix-github-actions";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    pre-commit-hooks = {
      url = "github:cachix/pre-commit-hooks.nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    treefmt-nix = {
      url = "github:numtide/treefmt-nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    gitignore = {
      url = "github:hercules-ci/gitignore.nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    agent-badge = {
      url = "github:jbboehr/agent-badge.ts/master";
      inputs.flake-utils.follows = "flake-utils";
      inputs.gitignore.follows = "gitignore";
      inputs.nixpkgs.follows = "nixpkgs";
    };
    php-perfidious = {
      url = "github:jbboehr/php-perfidious";
      flake = false;
    };
  };

  outputs =
    {
      self,
      nixpkgs,
      flake-utils,
      nix-github-actions,
      pre-commit-hooks,
      treefmt-nix,
      gitignore,
      agent-badge,
      php-perfidious,
    }:
    flake-utils.lib.eachDefaultSystem (
      system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
        lib = pkgs.lib;
        php-unwrapped = pkgs.php82;
        perfidious = pkgs.callPackage "${php-perfidious}/nix/derivation.nix" {
          php = php-unwrapped;
          src = php-perfidious;
          buildPecl = pkgs.callPackage "${nixpkgs}/pkgs/build-support/php/build-pecl.nix" {
            php = php-unwrapped;
          };
          valgrindSupport = false;
        };
        buildEnv =
          php:
          php.buildEnv {
            extraConfig = "memory_limit = 2G";
            extensions =
              {
                enabled,
                all,
              }:
              enabled ++ [ all.pcov ];
          };
        php = php-unwrapped.buildEnv {
          extraConfig = "memory_limit = 2G";
          extensions =
            {
              enabled,
              all,
            }:
            enabled ++ [ all.pcov ] ++ lib.optional pkgs.stdenv.isLinux perfidious;
        };
        php-xdebug = php-unwrapped.buildEnv {
          extraConfig = ''
            memory_limit = 2G
            xdebug.mode = off
          '';
          extensions =
            {
              enabled,
              all,
            }:
            enabled ++ [ all.xdebug ];
        };
        php82 = buildEnv pkgs.php82;
        php83 = buildEnv pkgs.php83;
        php84 = buildEnv pkgs.php84;
        php85 = buildEnv pkgs.php85;
        src = gitignore.lib.gitignoreSource ./.;

        composerSource = pkgs.runCommand "yumemi-composer-source" { } ''
          mkdir -p "$out"
          cp ${./composer.json} "$out/composer.json"
          cp ${./composer.lock} "$out/composer.lock"
        '';
        vendorHash = "sha256-8kCsDDRvB79yUV0itQAOM8w4mPQImkAYtfc/qjbKjbA=";
        composerRepository = php-unwrapped.mkComposerRepository {
          pname = "yumemi-dependencies";
          version = "0";
          src = composerSource;
          composerNoDev = false;
          composerNoPlugins = true;
          composerNoScripts = true;
          inherit vendorHash;
        };

        consumerComposerSource = pkgs.runCommand "yumemi-consumer-composer-source" { } ''
          mkdir -p "$out"
          cp ${./tests/Consumer/dependencies/composer.json} "$out/composer.json"
          cp ${./tests/Consumer/dependencies/composer.lock} "$out/composer.lock"
        '';
        consumerVendorHash = "sha256-xo5EOIis31HSsET18NJ9v7png9kLo9ooEiO5yp5ejfs=";
        consumerComposerRepository = php-unwrapped.mkComposerRepository {
          pname = "yumemi-consumer-dependencies";
          version = "0";
          src = consumerComposerSource;
          composerNoDev = false;
          composerNoPlugins = true;
          composerNoScripts = true;
          vendorHash = consumerVendorHash;
        };

        mkPhpCheck =
          {
            name,
            command,
            php ? php82,
            extraNativeBuildInputs ? [ ],
            repository ? composerRepository,
            lockFile ? ./composer.lock,
            needsGit ? false,
            recordFailure ? false,
            installResult ? "",
          }:
          pkgs.stdenvNoCC.mkDerivation {
            pname = "yumemi-check-${name}";
            version = "0";

            inherit src;
            composerRepository = repository;
            composerLock = lockFile;
            composerNoDev = false;
            composerNoPlugins = true;
            composerNoScripts = true;

            nativeBuildInputs = [
              php
              php.packages.composer-local-repo-plugin
              php.composerHooks.composerInstallHook
            ]
            ++ extraNativeBuildInputs
            ++ lib.optional needsGit pkgs.git;

            preConfigure = ''
              export HOME="$TMPDIR/home"
              export COMPOSER_CACHE_DIR="$TMPDIR/composer-cache"
              export COMPOSER_DISABLE_NETWORK=1
              mkdir -p "$HOME" "$COMPOSER_CACHE_DIR"
            '';
            postPatch = lib.optionalString needsGit ''
              git init --quiet
              git config user.email nix-build@example.invalid
              git config user.name "Nix build"
              git add --all
              git commit --quiet --message baseline
            '';
            buildPhase = ''
              runHook preBuild
              runHook postBuild
            '';
            doCheck = false;
            preInstall = ''
              composerInstallInstallHook() {
                setComposerRootVersion
                setComposerEnvVariables
                composer \
                  --no-interaction \
                  --no-progress \
                  ''${composerNoDev:+--no-dev} \
                  ''${composerNoPlugins:+--no-plugins} \
                  ''${composerNoScripts:+--no-scripts} \
                  install
              }
            '';
            installPhase = ''
              runHook preInstall

              patchShebangs vendor/bin
              export PATH="$PWD/vendor/bin:$PATH"
              mkdir -p "$out"

              set +e
              (
                set -e
                ${command}
              ) 2>&1 | tee "$out/check.log"
              checkStatus="''${PIPESTATUS[0]}"
              set -e
              printf '%s\n' "$checkStatus" > "$out/status"

              ${installResult}

              runHook postInstall

              ${lib.optionalString (!recordFailure) ''
                if [[ "$checkStatus" -ne 0 ]]; then
                  exit "$checkStatus"
                fi
              ''}
            '';
          };

        mkCheckGate =
          {
            name,
            result,
          }:
          pkgs.runCommand "yumemi-check-${name}" { } ''
            status="$(cat ${result}/status)"
            if [[ "$status" -ne 0 ]]; then
              cat ${result}/check.log >&2
              exit "$status"
            fi

            mkdir -p "$out"
            cp -R ${result}/. "$out/"
          '';

        mkPhpunitCheck =
          version: php:
          let
            name = "phpunit-php${version}";
            reports = mkPhpCheck {
              name = "${name}-reports";
              inherit php;
              recordFailure = true;
              command = ''
                mkdir -p build/test-results
                composer test -- --colors=never --log-junit build/test-results/phpunit.xml
              '';
              installResult = ''
                if [[ -f build/test-results/phpunit.xml ]]; then
                  cp build/test-results/phpunit.xml "$out/phpunit.xml"
                fi
              '';
            };
          in
          {
            check = mkCheckGate {
              inherit name;
              result = reports;
            };
            inherit reports;
          };

        phpunit82 = mkPhpunitCheck "82" php82;
        phpunit83 = mkPhpunitCheck "83" php83;
        phpunit84 = mkPhpunitCheck "84" php84;
        phpunit85 = mkPhpunitCheck "85" php85;
        phpunitReports = {
          phpunit-php82-reports = phpunit82.reports;
          phpunit-php83-reports = phpunit83.reports;
          phpunit-php84-reports = phpunit84.reports;
          phpunit-php85-reports = phpunit85.reports;
        };

        mutation-runtime-reports = mkPhpCheck {
          name = "mutation-runtime-reports";
          needsGit = true;
          recordFailure = true;
          command = ''
            substituteInPlace phpunit.xml.dist \
              --replace-fail 'https://schema.phpunit.de/11.5/phpunit.xsd' 'vendor/phpunit/phpunit/phpunit.xsd'
            COMPOSER_PROCESS_TIMEOUT=0 composer infection:ci
          '';
          installResult = ''
            for report in infection.log infection-summary.log; do
              if [[ -f "$report" ]]; then
                cp "$report" "$out/"
              fi
            done
          '';
        };
        mutation-phpstan-reports = mkPhpCheck {
          name = "mutation-phpstan-reports";
          needsGit = true;
          recordFailure = true;
          command = ''
            substituteInPlace phpunit.xml.dist \
              --replace-fail 'https://schema.phpunit.de/11.5/phpunit.xsd' 'vendor/phpunit/phpunit/phpunit.xsd'
            COMPOSER_PROCESS_TIMEOUT=0 composer infection:phpstan:ci
          '';
          installResult = ''
            for report in infection-phpstan.log infection-phpstan-summary.log; do
              if [[ -f "$report" ]]; then
                cp "$report" "$out/"
              fi
            done
          '';
        };
        mutation-runtime = mkCheckGate {
          name = "mutation-runtime";
          result = mutation-runtime-reports;
        };
        mutation-phpstan = mkCheckGate {
          name = "mutation-phpstan";
          result = mutation-phpstan-reports;
        };

        mkDevShell =
          php:
          pkgs.mkShell {
            buildInputs = with pkgs; [
              actionlint
              agent-badge.packages.${system}.default
              bison
              mdbook
              php
              php.packages.composer
              pre-commit
              treefmt.config.build.wrapper
              udunits
              units
            ];
            shellHook = ''
              ${pre-commit-check.shellHook}
              export PATH="$PWD/vendor/bin:$PATH"
              export GNU_UNITS_DEFINITIONS=${pkgs.units}/share/units/definitions.units
              export UDUNITS_XML_DIR=${pkgs.udunits}/share/udunits
            '';
          };

        treefmt = treefmt-nix.lib.evalModule pkgs {
          projectRootFile = "flake.nix";
          settings.global.excludes = [
            "docs/pages/assets/heliogenesis/**"
            "tests/Compatibility/fixtures/**/json/**"
            "tests/Compatibility/fixtures/**/manifest.json"
            "tests/Compatibility/fixtures/**/serialized/**"
          ];
          programs.nixfmt = {
            enable = true;
            package = pkgs.nixfmt;
          };
          programs.prettier = {
            enable = true;
            settings = {
              proseWrap = "always";
              printWidth = 120;
              overrides = [
                {
                  files = "LICENSE.md";
                  options.proseWrap = "preserve";
                }
              ];
            };
          };
        };

        pre-commit-check = pre-commit-hooks.lib.${system}.run {
          inherit src;
          hooks = {
            actionlint.enable = true;
            shellcheck.enable = true;
          };
        };

        normalGithubMatrix = nix-github-actions.lib.mkGithubMatrix {
          checks = lib.getAttrs [ "x86_64-linux" ] self.checks;
          attrPrefix = "checks";
        };
        normalGithubEntries = map (
          entry:
          entry
          // lib.optionalAttrs (builtins.hasAttr "${entry.name}-reports" phpunitReports) {
            reportKind = "phpunit";
            reportAttr = "packages.${entry.system}.\"${entry.name}-reports\"";
          }
        ) normalGithubMatrix.matrix.include;
        mutationGithubMatrix = nix-github-actions.lib.mkGithubMatrix {
          checks = {
            x86_64-linux = {
              inherit mutation-runtime mutation-phpstan;
            };
          };
          attrPrefix = "packages";
        };
        mutationGithubEntries = map (
          entry:
          entry
          // {
            reportKind = "mutation";
            reportAttr = "packages.${entry.system}.\"${entry.name}-reports\"";
          }
        ) mutationGithubMatrix.matrix.include;
        githubMatrix = {
          include = normalGithubEntries ++ mutationGithubEntries;
        };
        githubMatrixScript = pkgs.writeShellScript "yumemi-github-actions-matrix" ''
          printf '%s\n' ${lib.escapeShellArg (builtins.toJSON githubMatrix)}
        '';
      in
      rec {
        checks = {
          phpunit-php82 = phpunit82.check;
          phpunit-php83 = phpunit83.check;
          phpunit-php84 = phpunit84.check;
          phpunit-php85 = phpunit85.check;

          phpstan = mkPhpCheck {
            name = "phpstan";
            command = "composer analyse -- --error-format=raw";
          };
          php-cs-fixer = mkPhpCheck {
            name = "php-cs-fixer";
            command = "composer cs";
          };
          composer-validate =
            pkgs.runCommand "yumemi-composer-validate"
              {
                nativeBuildInputs = [
                  php82
                  php82.packages.composer
                  pkgs.gnumake
                ];
              }
              ''
                composer validate --strict ${src}/composer.json
                for manifest in \
                  ${src}/tests/Consumer/automatic/composer.json \
                  ${src}/tests/Consumer/manual/composer.json \
                  ${src}/tests/Consumer/phpgeo/composer.json \
                  ${src}/tests/Consumer/dependencies/composer.json; do
                  composer validate --no-check-publish "$manifest"
                done
                composer --working-dir=${src} test:consumer:locks
                touch "$out"
              '';
          php-lint =
            pkgs.runCommand "yumemi-php-lint"
              {
                nativeBuildInputs = [
                  php82
                  pkgs.findutils
                ];
              }
              ''
                find ${src} -type f -name '*.php' -print0 \
                  | xargs -0 -n 1 php -l
                touch "$out"
              '';
          benchmark-smoke = mkPhpCheck {
            name = "benchmark-smoke";
            command = "composer benchmark:smoke";
          };
          consumer-archive = mkPhpCheck {
            name = "consumer-archive";
            extraNativeBuildInputs = [
              pkgs.gnumake
              pkgs.gnutar
            ];
            command = ''
              patchShebangs tests/Consumer/run
              export COMPOSER_ROOT_VERSION=dev-consumer
              export YUMEMI_CONSUMER_COMPOSER_REPOSITORY=${consumerComposerRepository}
              COMPOSER_PROCESS_TIMEOUT=0 composer test:consumer:archive
            '';
          };
          generated-artifacts = mkPhpCheck {
            name = "generated-artifacts";
            extraNativeBuildInputs = [
              pkgs.bison
              pkgs.gnumake
              pkgs.udunits
            ];
            command = ''
              cp src/Parser/Parser.php "$TMPDIR/Parser.php"
              composer generate-parser
              cmp "$TMPDIR/Parser.php" src/Parser/Parser.php

              export UDUNITS2_BIN=${pkgs.udunits}/bin/udunits2
              export UDUNITS2_XML=${pkgs.udunits}/share/udunits/udunits2.xml
              composer test:udunits2
            '';
          };
          documentation = mkPhpCheck {
            name = "documentation";
            extraNativeBuildInputs = [
              pkgs.gnumake
              pkgs.mdbook
            ];
            command = "composer docs:check";
            installResult = ''
              if [[ -d build/docs ]]; then
                cp -R build/docs "$out/book"
              fi
            '';
          };
          inherit pre-commit-check;
          formatting = treefmt.config.build.check src;
        };

        packages = phpunitReports // {
          inherit
            mutation-runtime
            mutation-runtime-reports
            mutation-phpstan
            mutation-phpstan-reports
            ;
          mutation = pkgs.linkFarm "yumemi-mutation" [
            {
              name = "runtime";
              path = mutation-runtime;
            }
            {
              name = "phpstan";
              path = mutation-phpstan;
            }
          ];
        };

        apps.github-actions-matrix = {
          type = "app";
          program = "${githubMatrixScript}";
          meta.description = "Print the nix-github-actions validation matrix";
        };

        devShells = {
          default = mkDevShell php;
          xdebug = mkDevShell php-xdebug;
        };

        formatter = treefmt.config.build.wrapper;
      }
    );
}
