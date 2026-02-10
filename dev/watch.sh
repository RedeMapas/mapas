#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
CDIR=$( pwd )
cd $DIR

docker compose -f ../docker-compose-dev.yml exec -w /var/www/src mapas bash -c "pnpm install --recursive && pnpm run watch"

cd $CDIR
