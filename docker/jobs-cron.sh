#!/bin/bash
echo 'Inicializando CRON dos jobs'

# Verifica se a tabela db_update existe usando PHP
if ! php -r "
\$pdo = new PDO('pgsql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASSWORD'));
\$result = \$pdo->query(\"SELECT to_regclass('public.db_update')\")->fetchColumn();
if (!\$result) {
    exit(1);
}
"; then
    echo "Tabela 'db_update' não encontrada. Baixando e executando o SQL para criar a tabela..."

    # Baixa o SQL da URL fornecida
    curl -o /tmp/dump.sql https://raw.githubusercontent.com/RedeMapas/mapas/refs/heads/develop/dev/db/dump.sql

    # Executa o SQL no banco de dados
    php -r "
    \$pdo = new PDO('pgsql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASSWORD'));
    \$sql = file_get_contents('/tmp/dump.sql');
    \$pdo->exec(\$sql);
    echo 'SQL executado com sucesso.' . PHP_EOL;
    "
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
        /app/scripts/execute-job.sh &
    fi

    if [ -z "$JOBS_INTERVAL" ]; then
        sleep 1
    else
        sleep $JOBS_INTERVAL
    fi
done
