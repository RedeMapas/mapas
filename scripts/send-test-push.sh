#!/bin/bash

set -euo pipefail

TARGET="${1:-}"
MESSAGE="${2:-Teste push nativo via shell}"

if [[ -z "$TARGET" ]]; then
    echo "Usage: /var/www/scripts/send-test-push.sh <user-id|email> [message]"
    exit 1
fi

REQUEST_METHOD='CLI' \
REMOTE_ADDR='127.0.0.1' \
REQUEST_URI='/' \
HTTP_HOST='localhost' \
SERVER_NAME='127.0.0.1' \
SERVER_PORT='8000' \
php -r '
require "/var/www/public/bootstrap.php";

$target = $argv[1];
$message = $argv[2] ?? "Teste push nativo via shell";
$app = MapasCulturais\App::i();

$user = is_numeric($target)
    ? $app->repo("User")->find((int) $target)
    : $app->repo("User")->findOneBy(["email" => $target]);

if (!$user) {
    fwrite(STDERR, "User not found for target: {$target}\n");
    exit(2);
}

$app->disableAccessControl();

$notification = new MapasCulturais\Entities\Notification();
$notification->user = $user;
$notification->message = $message . " - " . date("Y-m-d H:i:s");
$notification->save(true);

$job = $app->enqueueJob(
    MapasCulturais\Themes\RedeMapas\Jobs\SendWebPushNotification::SLUG,
    ["notification" => $notification]
);

$app->enableAccessControl();

echo "user_id={$user->id}\n";
echo "notification_id={$notification->id}\n";
echo "job_id={$job->id}\n";
echo "job_slug=" . MapasCulturais\Themes\RedeMapas\Jobs\SendWebPushNotification::SLUG . "\n";
' "$TARGET" "$MESSAGE"
