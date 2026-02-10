#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
CDIR=$( pwd )
cd $DIR

BUILD="0"
DOWN="0"

for i in "$@"
do
case $i in
    -b|--build)
            BUILD="1"
	    shift
    ;;
    -d|--down)
            DOWN="1"
	    shift
    ;;
    -h|--help)
    	    echo "
	run-tests.sh [-b] [-u] [-d] [-s=25]

    -b=  | --build      builda a imagem Docker
	-d=  | --down       executa o docker compose -f ../docker-compose-dev.yml down antes do docker compose -f ../docker-compose-dev.yml run
    -h=  | --help       Imprime este texto de ajuda
		    "
    	    exit
    ;;
esac
done

if [ $BUILD = "1" ]; then
   docker compose -f ../docker-compose-dev.yml build
fi

if [ $DOWN = "1" ]; then
   docker compose -f ../docker-compose-dev.yml down
fi

docker compose -f ../docker-compose-dev.yml run --service-ports mapas 

docker compose -f ../docker-compose-dev.yml down --remove-orphans
cd $CDIR
