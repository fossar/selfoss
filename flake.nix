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
            ] ++ (with php.packages; [
              composer
              psalm
              phpstan
            ]);
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
          devShell = mkDevShell pkgs "php";
        }
      );
}
