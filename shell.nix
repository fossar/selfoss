let
  lock = builtins.fromJSON (builtins.readFile ./flake.lock);
  flake-compat = fetchTarball {
    url = "https://github.com/edolstra/flake-compat/archive/${lock.nodes.flake-compat.locked.rev}.tar.gz";
    sha256 = lock.nodes.flake-compat.locked.narHash;
  };
  self = import flake-compat {
    src = ./.;
  };

  devShells = self.shellNix.outputs.devShells.${builtins.currentSystem};
in
# Add rest of the devShells for the current system so that we can easily access them with nix-shell.
devShells.default // devShells
