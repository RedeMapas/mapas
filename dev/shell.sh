#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
CDIR=$( pwd )
cd $DIR

docker compose -f ../docker-compose-dev.yml exec mapas sh /var/www/scripts/shell.sh

cd $CDIR