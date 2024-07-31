{ }:

let pkgs = import (fetchTarball "https://github.com/NixOS/nixpkgs/archive/5148520bfab61f99fd25fb9ff7bfbb50dad3c9db.tar.gz") { overlays = [ (import (builtins.fetchTarball "https://github.com/railwayapp/nix-npm-overlay/archive/main.tar.gz")) ]; };
in with pkgs;
  let
    APPEND_LIBRARY_PATH = "${lib.makeLibraryPath [ libmysqlclient php82Extensions.gd php82Extensions.intl php82Extensions.pdo_pgsql php82Extensions.zip ] }";
    myLibraries = writeText "libraries" ''
      export LD_LIBRARY_PATH="${APPEND_LIBRARY_PATH}:$LD_LIBRARY_PATH"
      
    '';
  in
    buildEnv {
      name = "5148520bfab61f99fd25fb9ff7bfbb50dad3c9db-env";
      paths = [
        (runCommand "5148520bfab61f99fd25fb9ff7bfbb50dad3c9db-env" { } ''
          mkdir -p $out/etc/profile.d
          cp ${myLibraries} $out/etc/profile.d/5148520bfab61f99fd25fb9ff7bfbb50dad3c9db-env.sh
        '')
        (php82.withExtensions (pe: pe.enabled ++ [pe.all.gd pe.all.intl pe.all.pdo_pgsql pe.all.zip])) libmysqlclient nginx nodejs_18 npm-9_x php82Extensions.gd php82Extensions.intl php82Extensions.pdo_pgsql php82Extensions.zip php82Packages.composer
      ];
    }
