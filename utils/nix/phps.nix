nixpkgs:

# These are older versions of PHP removed from Nixpkgs.

final: prev:

let
  _args = { inherit (prev) callPackage lib stdenv nixosTests; };

  generic = (import "${nixpkgs}/pkgs/development/interpreters/php/generic.nix") _args;

  base56 = prev.callPackage generic (_args // {
    version = "5.6.40";
    sha256 = "/9Al00YjVTqy9/2Psh0Mnm+fow3FZcoDode3YwI/ugA=";
  });

  base70 = prev.callPackage generic (_args // {
    version = "7.0.33";
    sha256 = "STPqdCmKG6BGsCRv43cUFchN+4eDliAbVstTM6vobwc=";
  });

  base71 = prev.callPackage generic (_args // {
    version = "7.1.33";
    sha256 = "laXl8uK3mzdrc3qC2WgskYkeYCifokGDRjoqyhWPT0s=";
  });

  base72 = prev.callPackage generic (_args // {
    version = "7.2.34";
    sha256 = "DlgW1miiuxSspozvjEMEML2Gw8UjP2xCfRpUqsEnq88=";
  });

  fixDom = dom: dom.overrideAttrs (attrs: {
    patches = attrs.patches or [] ++ [
      # Fix tests with libxml2 2.9.10
      (prev.fetchpatch {
        url = "https://github.com/php/php-src/commit/e29922f054639a934f3077190729007896ae244c.patch";
        sha256 = "zC2QE6snAhhA7ItXgrc80WlDVczTlZEzgZsD7AS+gtw=";
      })
    ];
  });

  fixOpenssl = openssl: openssl.overrideAttrs (attrs: {
    # PHP 5.6 requires openssl 1.0. It is insecure but that should not matter in an isolated test environment.
    buildInputs = map (p: if p == prev.openssl then prev.openssl_1_0_2.overrideAttrs (attrs: { meta = attrs.meta // { knownVulnerabilities = []; }; }) else p) attrs.buildInputs or [];
  });

  fixZlib = zlib: zlib.overrideAttrs (attrs: {
    # The patch do not apply to PHP 7’s zlib.
    patches = if prev.lib.versionOlder zlib.version "7.1" then [] else attrs.patches;
  });

  fixOpcache = opcache: opcache.overrideAttrs (attrs: {
    # The patch do not apply to PHP 5’s opcache.
    patches = if prev.lib.versionOlder opcache.version "7.0" then [] else attrs.patches;
  });

  # Replace mysqlnd dependency by our fixed one.
  fixMysqlndDep = ext: ext.overrideAttrs (attrs:
    let
      origDeps = attrs.internalDeps or [];
      mysqlnds = builtins.filter (p: p.extensionName == "mysqlnd") origDeps;
    in {
      internalDeps = map (p: if p.extensionName == "mysqlnd" then fixMysqlnd p else p) origDeps;
      preConfigure =
        prev.lib.pipe (attrs.preConfigure or "") [
          # We need to discard string context because replaceStrings does not seem to update it.
          builtins.unsafeDiscardStringContext

          # Fix mysqlnd references.
          (builtins.replaceStrings (map (p: "${p.dev}") mysqlnds) (map (p: "${(fixMysqlnd p).dev}") mysqlnds))

          # Re-introduce original context.
          (builtins.replaceStrings (map (p: "${p.dev}") origDeps) (map (p: "${p.dev}") origDeps))
        ];
    }
  );

  fixMysqlnd = mysqlnd: mysqlnd.overrideAttrs (attrs: {
    postPatch = attrs.postPatch or "" + "\n" + ''
      ln -s $PWD/../../ext/ $PWD
    '';
  });

  fixRl = rl: rl.overrideAttrs (attrs: {
    patches = attrs.patches or [] ++ [
      # Fix readline build
      (prev.fetchpatch {
        url = "https://github.com/php/php-src/commit/1ea58b6e78355437b79fb7b1f287ba6688fb1c57.patch";
        sha256 = "Lh2h07lKkAXpyBGqgLDNXeiOocksARTYIysLWMon694=";
      })
    ];
  });
  fixIntl = intl: intl.overrideAttrs (attrs: {
    doCheck = false;
    patches = attrs.patches or [] ++ prev.lib.optionals (prev.lib.versionOlder intl.version "7.1") [
      # Fix build with newer ICU.
      (prev.fetchpatch {
        url = "https://github.com/php/php-src/commit/8d35a423838eb462cd39ee535c5d003073cc5f22.patch";
        sha256 = if prev.lib.versionOlder intl.version "7.0" then "8v0k6zaE5w4yCopCVa470TMozAXyK4fQelr+KuVnAv4=" else "NO3EY5z1LFWKor9c/9rJo1rpigG5x8W3Uj5+xAOwm+g=";
        postFetch = ''
          patch "$out" < ${if prev.lib.versionOlder intl.version "7.0" then ./intl-icu-patch-5.6-compat.patch else ./intl-icu-patch-7.0-compat.patch}
        '';
      })
    ];
  });

in {
  php56 = base56.withExtensions ({ all, ... }: with all; ([
    bcmath calendar curl ctype (fixDom dom) exif fileinfo filter ftp gd
    gettext gmp hash iconv (fixIntl intl) json ldap mbstring (fixMysqlndDep mysqli) (fixMysqlnd mysqlnd) (fixOpcache opcache)
    (fixOpenssl openssl) pcntl pdo (fixMysqlndDep pdo_mysql) pdo_odbc pdo_pgsql pdo_sqlite pgsql
    posix (fixRl readline) session simplexml sockets soap sqlite3
    tokenizer xmlreader xmlwriter zip (fixZlib zlib)
  ] ++ prev.lib.optionals (!prev.stdenv.isDarwin) [ imap ]));

  php70 = base70.withExtensions ({ all, ... }: with all; ([
    bcmath calendar curl ctype (fixDom dom) exif fileinfo filter ftp gd
    gettext gmp hash iconv (fixIntl intl) json ldap mbstring (fixMysqlndDep mysqli) (fixMysqlnd mysqlnd) opcache
    (fixOpenssl openssl) pcntl pdo (fixMysqlndDep pdo_mysql) pdo_odbc pdo_pgsql pdo_sqlite pgsql
    posix (fixRl readline) session simplexml sockets soap sqlite3
    tokenizer xmlreader xmlwriter zip (fixZlib zlib)
  ] ++ prev.lib.optionals (!prev.stdenv.isDarwin) [ imap ]));

  php71 = base71.withExtensions ({ all, ... }: with all; ([
    bcmath calendar curl ctype (fixDom dom) exif fileinfo filter ftp gd
    gettext gmp hash iconv (fixIntl intl) json ldap mbstring mysqli mysqlnd opcache
    openssl pcntl pdo pdo_mysql pdo_odbc pdo_pgsql pdo_sqlite pgsql
    posix (fixRl readline) session simplexml sockets soap sqlite3
    tokenizer xmlreader xmlwriter zip zlib
  ] ++ prev.lib.optionals (!prev.stdenv.isDarwin) [ imap ]));

  php72 = base72.withExtensions ({ all, ... }: with all; ([
    bcmath calendar curl ctype dom exif fileinfo filter ftp gd
    gettext gmp hash iconv intl json ldap mbstring mysqli mysqlnd opcache
    openssl pcntl pdo pdo_mysql pdo_odbc pdo_pgsql pdo_sqlite pgsql
    posix readline session simplexml sockets soap sodium sqlite3
    tokenizer xmlreader xmlwriter zip zlib
  ] ++ prev.lib.optionals (!prev.stdenv.isDarwin) [ imap ]));
}
