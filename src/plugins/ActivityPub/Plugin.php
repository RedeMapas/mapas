<?php
declare(strict_types=1);

namespace ActivityPub;

use MapasCulturais\App;

class Plugin extends \MapasCulturais\Plugin
{
    public function _init(): void
    {
        $app = App::i();

        if (!($app->config['activitypub.enabled'] ?? false)) {
            return;
        }

        // Hooks e middleware serão adicionados na Task 6
    }

    public function register(): void
    {
        $app = App::i();

        if (!($app->config['activitypub.enabled'] ?? false)) {
            return;
        }

        // Controller e job registrados na Task 6
    }
}
