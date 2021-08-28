{
  coreutils,
  bash,
  util-linux,
  dockerTools,
  unzip,
  lib,
  nixosSystem,
  targetPlatform,
}:

# We are going to abuse the NixOS module for Apache to create configuration files.
# Then we will extract them from the instantiated system and add them to the image.

let
  # Configuration for NixOS system.
  systemConfiguration = { config, lib, pkgs, ... }: {
    services.httpd = {
      enable = true;

      # TODO: make this overridable? Or hidden?
      adminAddr = "admin@selfoss";

      # TODO: is root okay?
      user = "root";
      group = "root";

      virtualHosts."selfoss" = {
        documentRoot = "/var/www";
        locations."/" = {
          index = "index.php index.html";
        };
      };

      phpPackage = pkgs.php;
      enablePHP = true;
    };
  };

  # Instantiate the NixOS configuration.
  system = nixosSystem {
    system = targetPlatform;

    modules = [
      systemConfiguration
    ];
  };

  apacheHttpd = system.config.services.httpd.package;

in
dockerTools.buildLayeredImage {
  name = "selfoss";
  tag = "latest";

  contents = [
    apacheHttpd

    # TODO: remove, only for debugging
    coreutils
    bash
    util-linux
  ];

  # Cargo-culted from https://sandervanderburg.blogspot.com/2020/07/on-using-nix-and-docker-as-deployment.html
  maxLayers = 100;

  extraCommands = ''
    mkdir -p var/log/httpd var/cache/httpd var/www etc/httpd
    ${selfoss} -d var/www
    cp ${system.config.environment.etc."httpd/httpd.conf".source} etc/httpd/httpd.conf
  '';

  config = {
    Cmd = [ "${apacheHttpd}/bin/apachectl" "-D" "FOREGROUND" ];
    Expose = {
      "80/tcp" = {};
    };
  };
}

# TODO: add devenv image
# NOTE: Can be run:
# nix build -L .#selfoss-docker
# docker load < result
# docker run -p 8080:80/tcp -it selfoss:latest
