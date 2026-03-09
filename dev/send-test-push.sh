#!/bin/bash

set -euo pipefail

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
CDIR="$( pwd )"

cd "$DIR/.."

docker compose exec mapas bash /var/www/scripts/send-test-push.sh "$@"

cd "$CDIR"
