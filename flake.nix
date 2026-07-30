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
  };

  outputs =
    {
      self,
      nixpkgs,
      flake-utils,
      pre-commit-hooks,
      treefmt-nix,
      gitignore,
    }:
    flake-utils.lib.eachDefaultSystem (
      system:
      let
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
        pkgs = nixpkgs.legacyPackages.${system};
        php = buildEnv pkgs.php82;
        src = gitignore.lib.gitignoreSource ./.;

        treefmt = treefmt-nix.lib.evalModule pkgs {
          projectRootFile = "flake.nix";
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
          inherit pre-commit-check;
          documentation = pkgs.runCommand "yumemi-documentation" { nativeBuildInputs = [ pkgs.mdbook ]; } ''
            mdbook build ${src}/docs --dest-dir "$out"
          '';
          formatting = treefmt.config.build.check self;
        };

        devShells.default = pkgs.mkShell {
          buildInputs = with pkgs; [
            actionlint
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

        formatter = treefmt.config.build.wrapper;
      }
    );
}
