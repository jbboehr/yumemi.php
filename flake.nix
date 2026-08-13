{
  description = "jbboehr/yumemi";

  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs/nixos-26.05";
    flake-utils = {
      url = "github:numtide/flake-utils";
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
              enabled ++ [ all.pcov ] ++ pkgs.lib.optional pkgs.stdenv.isLinux perfidious;
          };
        php = buildEnv php-unwrapped;
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
        src = gitignore.lib.gitignoreSource ./.;
        generated-artifacts = php-unwrapped.buildComposerProject2 (finalAttrs: {
          pname = "yumemi-generated-artifacts";
          version = "0";

          inherit src;

          composerNoDev = false;
          vendorHash = "sha256-QOBPL0i0UMB90rl96vy+iHm8z/7z1Zr4FLV3t1nG/KM=";

          nativeCheckInputs = [
            pkgs.bison
            pkgs.udunits
          ];
          checkPhase = ''
            runHook preCheck

            cp src/Parser/Parser.php "$TMPDIR/Parser.php"
            make --always-make src/Parser/Parser.php
            cmp "$TMPDIR/Parser.php" src/Parser/Parser.php

            export HOME="$TMPDIR"
            export UDUNITS2_BIN=${pkgs.udunits}/bin/udunits2
            export UDUNITS2_XML=${pkgs.udunits}/share/udunits/udunits2.xml
            php vendor/bin/phpunit --group udunits2 --no-coverage --colors=never

            runHook postCheck
          '';
          installPhase = ''
            mkdir -p "$out"
            touch "$out/passed"
          '';
        });

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
            treefmt = {
              enable = true;
              package = treefmt.config.build.wrapper;
              require_serial = true;
            };
          };
        };
      in
      rec {
        checks = {
          inherit generated-artifacts pre-commit-check;
          documentation =
            pkgs.runCommand "yumemi-documentation"
              {
                nativeBuildInputs = [
                  pkgs.mdbook
                  php-unwrapped
                ];
              }
              ''
                mdbook build ${src}/docs --dest-dir "$out"
                php ${src}/tests/Documentation/check-generated-links.php "$out"
              '';
          formatting = treefmt.config.build.check self;
        };

        devShells = {
          default = mkDevShell php;
          xdebug = mkDevShell php-xdebug;
        };

        formatter = treefmt.config.build.wrapper;
      }
    );
}
