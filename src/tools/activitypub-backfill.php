<?php
declare(strict_types=1);

set_time_limit(0);
ini_set('memory_limit', '2048M');

$options = getopt('', [
    'dry-run',
    'entity:',
    'agent-id:',
    'include-registrations',
    'help',
]);

if (isset($options['help'])) {
    echo "Usage: php src/tools/activitypub-backfill.php [--dry-run] [--entity=event|space|project|opportunity|registration|all] [--agent-id=123] [--include-registrations]\n";
    exit(0);
}

require __DIR__ . '/../../public/bootstrap.php';

use ActivityPub\Backfill;
use MapasCulturais\App;

$app = App::i();

try {
    $backfill = new Backfill($app);
    $report = $backfill->run($options);
} catch (\Throwable $e) {
    fwrite(STDERR, "[activitypub-backfill] " . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo sprintf("ActivityPub backfill (%s)\n", $report['dryRun'] ? 'dry-run' : 'enqueue');
foreach ($report['totals'] as $label => $count) {
    $enqueued = $report['enqueued'][$label] ?? 0;
    echo sprintf("- %s: %d elegiveis, %d enfileirados\n", $label, $count, $enqueued);
}

$app->em->getConnection()->close();
