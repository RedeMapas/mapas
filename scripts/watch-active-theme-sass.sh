#!/bin/bash
THEMES_PATH="/var/www/src/themes"
THEME=${ACTIVE_THEME:-BaseV1}
ASSETS_PATH="$THEMES_PATH/$THEME/assets/css"

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
SASS_BIN="/var/www/src/node_scripts/node_modules/.bin/sass"
if [ ! -x "$SASS_BIN" ]; then
  SASS_BIN="$(command -v sass || true)"
fi
if [ -z "$SASS_BIN" ]; then
  echo "sass não encontrado (nem em /var/www/src/node_scripts/node_modules/.bin/sass nem no PATH)"
  exit 1
fi

$DIR/compile-sass.sh

"$SASS_BIN" --watch $ASSETS_PATH/sass/main.scss:$ASSETS_PATH/main.css
