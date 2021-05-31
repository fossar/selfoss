{
  description = "selfoss feed reader and aggregator";

  inputs = {
    # Tool for downloading Composer dependencies using Nix.
    c4.url = "github:fossar/composition-c4";

    # Shim to make flake.nix work with stable Nix.
    flake-compat = {
      url = "github:edolstra/flake-compat";
      flake = false;
    };

    # Repository with software packages.
    nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";

    napalm = {
      url = "github:nmattia/napalm";
      inputs.nixpkgs.follows = "nixpkgs";
    };

    utils.url = "github:numtide/flake-utils";

    # Package expression for old PHP versions.
    phps.url = "github:fossar/nix-phps";
  };

  outputs = { self, c4, flake-compat, napalm, nixpkgs, phps, utils }:
    let
      # Configure the development shell here (e.g. for CI).

      # By default, we use the default PHP version from Nixpkgs.
      matrix.phpPackage = "php";
    in
      # For each supported platform,
      utils.lib.eachDefaultSystem (system:
        let
          # Get Nixpkgs packages for current platform.
          pkgs = import nixpkgs {
            inherit system;
            overlays = [
              # Include c4 tool.
              c4.overlay

              # Include napalm tool.
              napalm.overlay
            ];
          };

          # Create a PHP package from the selected PHP package, with some extra extensions enabled.
          php = phps.packages.${system}.${matrix.phpPackage}.withExtensions ({ enabled, all }: with all; enabled ++ [
            imagick
          ]);

          # Create a Python package with some extra packages installed.
          python = pkgs.python3.withPackages (pp: with pp; [
            # For integration tests.
            bcrypt
            requests
          ]);
        in {
          # Expose shell environment for development.
          devShell = pkgs.mkShell {
            nativeBuildInputs = [
              # Composer and PHP for back-end.
              php
              php.packages.composer

              # Back-end code validation.
              php.packages.psalm
              php.packages.phpstan

              # npm for front-end.
              pkgs.nodejs_latest

              # For building zip archive.
              pkgs.jq

              # For building zip archive and integration tests.
              python

              # Website generator.
              pkgs.zola
            ];

            # node-gyp wants some locales, let’s make them available through an environment variable.
            LOCALE_ARCHIVE = "${pkgs.glibcLocales}/lib/locale/locale-archive";
          };

          packages = {
            selfoss-docker = pkgs.callPackage ./utils/docker.nix {
              inherit (nixpkgs.lib) nixosSystem;
              targetPlatform = system;
            };

            selfoss =
              let
                filteredSrc = builtins.path {
                  path = ./.;
                  filter =
                    path:
                    type:
                    !builtins.elem (builtins.baseNameOf path) [
                      # These should not be part of the source code for packages built by Nix.
                      # Otherwise, iterating on Nix files will trigger a rebuild all the time since the source will have changed.
                      "flake.nix"
                      "flake.lock"
                      "utils"
                      # CI changes should not affect it either.
                      ".github"
                    ];
                    # Unfortunately, it still triggers a rebuild since any change will cause the flake to be re-cloned.
                    # https://github.com/NixOS/nix/issues/3732
                };

                # Due to Nix bug, we cannot actually use the output directly and need to copy it to a new derivation.
                # https://github.com/NixOS/nix/issues/3234
                src = pkgs.runCommand "selfoss-src" {} "cp -r '${filteredSrc}' $out";
              in
              pkgs.callPackage ./utils/selfoss.nix rec {
                inherit src;
              };
          };
        }
      );
}
