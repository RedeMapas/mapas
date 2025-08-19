#!/bin/bash
echo 'Inicializando CRON do pcache'

# Verifica se a tabela db_update existe usando PHP
if ! php -r "
\$pdo = new PDO('pgsql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASSWORD'));
\$result = \$pdo->query(\"SELECT to_regclass('public.db_update')\")->fetchColumn();
if (!\$result) {
    echo 'Erro: A tabela db_update não existe.' . PHP_EOL;
    exit(1);
}
"; then
    exit 1
fi

NUM_CORES=$(grep -c ^processor /proc/cpuinfo)

if [ -z "$NUM_PROCESSES" ]; then
    NUM_PROCESSES=$NUM_CORES
fi

bash_pid=$$

while [ true ]; do
    children=`ps -eo ppid | grep -w $bash_pid`
    NUM_CHILDREN=`echo $children | wc -w`

    if [ $NUM_PROCESSES -ge $NUM_CHILDREN ]; then
        /app/scripts/recreate-pending-pcache.sh &
    fi

    if [ -z "$PENDING_PCACHE_RECREATION_INTERVAL" ]; then
        sleep 1
    else
        sleep $PENDING_PCACHE_RECREATION_INTERVAL
    fi
done
