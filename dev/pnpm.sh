#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
CDIR=$( pwd )
cd $DIR

docker compose -f ../docker-compose-dev.yml exec -w /var/www/html/protected mapas bash -c "pnpm $*"

cd $CDIR
