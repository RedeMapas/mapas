<?php
declare(strict_types=1);

namespace ActivityPub;

use ActivityPub\Controllers\ActivityPub as ActivityPubController;
use ActivityPub\Jobs\RecordActivity;
use ActivityPub\Middleware\ActivityPubMiddleware;
use MapasCulturais\App;
use MapasCulturais\Entity;

class Plugin extends \MapasCulturais\Plugin
{
    public function _init(): void
    {
        $app = App::i();

        if (!($app->config['activitypub.enabled'] ?? false)) {
            return;
        }

        $plugin = $this;

        // Middleware intercepta rotas ActivityPub antes do catch-all do RoutesManager
        $app->slim->add(new ActivityPubMiddleware());

        // Criações
        $app->hook('entity(Event).insert:after',       function() use ($plugin) { $plugin->dispatch('Create', $this); });
        $app->hook('entity(Space).insert:after',       function() use ($plugin) { $plugin->dispatch('Create', $this); });
        $app->hook('entity(Project).insert:after',     function() use ($plugin) { $plugin->dispatch('Create', $this); });
        $app->hook('entity(Opportunity).insert:after', function() use ($plugin) { $plugin->dispatch('Create', $this); });

        // Atualizações
        $app->hook('entity(Event).update:after',       function() use ($plugin) { $plugin->dispatch('Update', $this); });
        $app->hook('entity(Space).update:after',       function() use ($plugin) { $plugin->dispatch('Update', $this); });
        $app->hook('entity(Project).update:after',     function() use ($plugin) { $plugin->dispatch('Update', $this); });
        $app->hook('entity(Opportunity).update:after', function() use ($plugin) { $plugin->dispatch('Update', $this); });

        // Ações relacionais
        $app->hook('entity(Registration).insert:after',  function() use ($plugin) { $plugin->dispatch('Announce', $this); });
        $app->hook('entity(AgentRelation).insert:after', function() use ($plugin) { $plugin->dispatch('Add', $this); });
    }

    public function register(): void
    {
        $app = App::i();

        if (!($app->config['activitypub.enabled'] ?? false)) {
            return;
        }

        $app->registerController('activitypub', ActivityPubController::class);

        // Guard defensivo: testes reutilizam App e registerJobType lança exceção se já registrado
        if (!$app->getRegisteredJobType(RecordActivity::SLUG)) {
            $app->registerJobType(new RecordActivity());
        }
    }

    public function dispatch(string $activityType, Entity $entity): void
    {
        $app         = App::i();
        $entityClass = get_class($entity);
        $entityId    = (int) $entity->id;

        if (!$entityId) {
            return;
        }

        $app->enqueueJob(RecordActivity::SLUG, [
            'activityType' => $activityType,
            'entityClass'  => $entityClass,
            'entityId'     => $entityId,
        ]);
    }
}
