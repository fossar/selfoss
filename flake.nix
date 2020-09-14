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
    utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
      in {
        devShell =
          let
            php = pkgs.php.withExtensions ({ enabled, all }: with all; enabled ++ [
              imagick
            ]);
          in
            pkgs.mkShell {
              nativeBuildInputs = [
                php
                pkgs.zola
                pkgs.nodejs_latest
              ] ++ (with php.packages; [
                composer
                psalm
                phpstan
              ]);
            };
      }
    );
}
