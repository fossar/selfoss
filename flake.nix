{
  description = "selfoss feed reader and aggregator";

  inputs = {
    # Shim to make flake.nix work with stable Nix.
    flake-compat = {
      url = "github:edolstra/flake-compat";
      flake = false;
    };

    # Package expression for old PHP versions.
    phps.url = "github:fossar/nix-phps";
  };

  outputs = { self, phps, ... }:
    let
      # nixpkgs is a repository with software packages and some utilities.
      # From simplicity, we inherit it from the phps flake.
      inherit (phps.inputs) nixpkgs utils;

      # Configure the development shell here (e.g. for CI).

      # By default, we use the default PHP version from Nixpkgs.
      matrix.phpPackage = "php";

      # We install all storage backends by default.
      matrix.storage = "all";
    in
    # For each supported platform,
    utils.lib.eachDefaultSystem (system:
      let
        # Get Nixpkgs packages for current platform.
        pkgs = nixpkgs.legacyPackages.${system};

        inherit (pkgs) lib;

        mergeAttribute =
          l:
          r:
          if builtins.isAttrs l && builtins.isAttrs r then
            l // r
          else if builtins.isList l && builtins.isList r then
            l ++ r
          else
            throw "Unsupported combination of types: ${builtins.typeOf l} and ${builtins.typeOf r}";

        mergeEnvs = lib.fold (lib.mergeAttrsWithFunc mergeAttribute) {};

        # Create a PHP package from the selected PHP package, with some extra extensions enabled.
        php = phps.packages.${system}.${matrix.phpPackage}.withExtensions ({ enabled, all }: with all; enabled ++ [
          imagick
          tidy
        ]);

        # Create a Python package with some extra packages installed.
        python = pkgs.python3.withPackages (pp: with pp; [
          # For integration tests.
          bcrypt
          requests
        ]);

        # Database servers for testing.
        dbServers = {
          mysql = {
            nativeBuildInputs = [ pkgs.mariadb ];
          };
          postgresql = {
            nativeBuildInputs = [ pkgs.postgresql ];
          };
          sqlite = { };
          all = mergeEnvs (builtins.attrValues (builtins.removeAttrs dbServers [ "all" ]));
        };

        languageEnv = {
          nativeBuildInputs = [
            # Composer and PHP for back-end.
            php
            php.packages.composer

            # npm for front-end.
            pkgs.nodejs_latest
          ];

          env = {
            # node-gyp wants some locales, let’s make them available through an environment variable.
            LOCALE_ARCHIVE = "${pkgs.glibcLocales}/lib/locale/locale-archive";
          };
        };

        developmentSupport = {
          nativeBuildInputs = [
            # PHP LSP
            pkgs.phpactor
          ];
        };

        qaTools = {
          nativeBuildInputs = [
            # Back-end code validation.
            php.packages.phpstan

            # For building zip archive and integration tests.
            python

            # Python code linting.
            pkgs.black
          ];
        };

        websiteTools = {
          nativeBuildInputs = [
            # Website generator.
            pkgs.zola
          ];
        };
      in
      {
        # Expose shell environment for development.
        devShells = {
          default = pkgs.mkShell (
            mergeEnvs [
              languageEnv
              developmentSupport
              qaTools
              websiteTools
              dbServers.${matrix.storage}
            ]
          );

          ci = pkgs.mkShell (
            mergeEnvs [
              languageEnv
              qaTools
              websiteTools
              dbServers.${matrix.storage}
            ]
          );

          website = pkgs.mkShell (
            mergeEnvs [
              websiteTools
            ]
          );
        };
      }
    );
}
