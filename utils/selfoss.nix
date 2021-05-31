{
  stdenv,
  napalm,
  runCommand,
  jq,
  lib,
  src,
  c4,
  php,
}:

let
  version = (lib.importJSON (src + "/package.json")).ver;

  client-src =
    runCommand "client-src" {
      nativeBuildInputs = [
        jq
      ];
    } ''
      cp -r "${src}/assets" "$out"
      chmod +w "$out"

      # napalm requires version for all packages.
      # And npm needs it to follow semver.
      jq '. += { "name": "selfoss-client", "version": "0.0.0+${version}" }' "$out/package-lock.json" > "$out/package-lock.json.tmp"
      mv "$out/package-lock.json.tmp" "$out/package-lock.json"

      jq '. += { "name": "selfoss-client", "version": "0.0.0+${version}" }' "$out/package.json" > "$out/package.json.tmp"
      mv "$out/package.json.tmp" "$out/package.json"
    '';

  stopNpmCallingHome = ''
    # Do not try to find npm in napalm-registry –
    # it is not there and checking will slow down the build.
    npm config set update-notifier false

    # Same for security auditing, it does not make sense in the sandbox.
    npm config set audit false
  '';

  client-assets = napalm.buildPackage "${client-src}" {
    npmCommands = [
      # Just download and unpack all the npm packages,
      # we need napalm to patch shebangs before we can run install scripts.
      "npm install --loglevel verbose --ignore-scripts"
      # Let’s install again, this time running scripts.
      "npm install --loglevel verbose"

      # napalm only patches shebangs for scripts in bin directories
      "patchShebangs node_modules/parcel/lib/bin.js"

      # Build the front-end.
      "npm run build"
    ];

    postConfigure = stopNpmCallingHome;

    installPhase = ''
      runHook preInstall

      mv ../public $out

      runHook postInstall
    '';
  };
in
stdenv.mkDerivation {
  pname = "selfoss";
  inherit version;

  inherit src;

  composerDeps = c4.fetchComposerDeps {
    inherit src;
  };

  nativeBuildInputs = [
    c4.composerSetupHook
    php.packages.composer
  ];

  buildPhase = ''
    runHook preBuild

    cp -r ${client-assets} public
    composer install --no-dev --optimize-autoloader

    runHook postBuild
  '';

  installPhase = ''
    runHook preInstall

    mkdir -p $out
    cp -r . $out

    runHook postInstall
  '';
}
