<?php

declare(strict_types=1);

use App\Kernel;

include dirname(__DIR__).'/vendor/autoload.php';

require_once 'bootstrap.php';

(new Kernel)->execute();

$app->run();
