{
  description = "selfoss feed reader and aggregator";

  inputs = {
    flake-compat = {
      url = "github:edolstra/flake-compat";
      flake = false;
    };

    nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";

    utils.url = "github:numtide/flake-utils";
  };

  outputs = { self, flake-compat, nixpkgs, utils }:
    let
      matrix.php = "php";

      mkDevShell = pkgs: phpPackage:
        let
          php = pkgs.${phpPackage}.withExtensions ({ enabled, all }: with all; enabled ++ [
            imagick
          ]);

          python = pkgs.python3.withPackages (pp: with pp; [
            requests
            bcrypt
          ]);
        in
          pkgs.mkShell {
            nativeBuildInputs = [
              php
              pkgs.zola
              pkgs.nodejs_latest
              python
              pkgs.jq
            ] ++ (with php.packages; [
              composer
              psalm
              phpstan
            ]);

            LOCALE_ARCHIVE = "${pkgs.glibcLocales}/lib/locale/locale-archive";
          };
    in
      utils.lib.eachDefaultSystem (system:
        let
          pkgs = import nixpkgs.outPath {
            inherit system;
            overlays = [
              (import ./utils/nix/phps.nix nixpkgs.outPath)
            ];
          };
        in {
          packages = {
            inherit (pkgs) php56 php70 php71 php72;
          };
          devShell = mkDevShell pkgs matrix.php;
        }
      );
}
