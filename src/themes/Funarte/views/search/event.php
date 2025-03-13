<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    create-event
    mc-breadcrumb
    mc-tab
    mc-tabs 
    search 
    search-filter-event
    search-list-event 
    search-map-event 
');

$entities = [
    'registered' => [
        'description' => 'eventos cadastrados',
        'number' => '10',
        'icon' => 'docs',
    ],
    'organized' => [
        'description' => 'eventos realizados',
        'number' => '20',
        'icon' => 'dateWeek',
    ],
    'finished' => [
        'description' => 'eventos finalizadas',
        'number' => '15',
        'icon' => 'dateCheck',
    ],
    'seven_days' => [
        'description' => 'cadastrados nos últimos 7 dias',
        'number' => '00',
        'icon' => 'dateDay',
    ]
];

$this->breadcrumb = [
    ['label' => i::__('Inicio'), 'url' => $app->createUrl('site', 'index')],
    ['label' => i::__('Eventos'), 'url' => $app->createUrl('events')],
];
?>

<search page-title="<?php i::esc_attr_e('Eventos') ?>" entity-type="event"
    :initial-pseudo-query="{'event:term:linguagem':[],'event:term:linguagem':[], 'event:classificacaoEtaria': []}">
    <!-- <template v-if="global.auth.isLoggedIn" #create-button> -->
    <template #create-button>
        <create-event #default="{modal}">
            <button @click="modal.open()" class="button button--primary button--icon"
                style=" width: 250px; height: 48px; background-color:#2B74D9; border-color: #2B74D9; border-radius:8px;">
                <span><?= i::__('criar evento') ?></span>
            </button>
        </create-event>
    </template>
    <!-- </template> -->
    <template #default="{pseudoQuery, changeTab}">
        <div class="card-section">
            <?php foreach ($entities as $key => $entity): ?>
                <div class="card-container">
                    <div class="card-header">
                        <p class="event-quantity">
                            <?= i::__($entity['number']) ?>
                        </p>
                        <div class="event-icon">
                            <mc-icon name=<?= i::__($entity['icon']) ?>></mc-icon>
                        </div>
                    </div>
                    <p class="event-description">
                        <?= i::__($entity['description']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
        <mc-tabs @changed="changeTab($event)" class="search__tabs" sync-hash>
            <template #before-tablist>
                <label class="search__tabs--before">
                    <?= i::_e('Visualizar como:') ?>
                </label>
            </template>
            <mc-tab icon="list" label="<?php i::esc_attr_e('Lista') ?>" slug="list">
                <div class="search__tabs--list">
                    <search-list-event :pseudo-query="pseudoQuery"></search-list-event>
                </div>
            </mc-tab>
            <mc-tab icon="map" label="<?php i::esc_attr_e('Mapa') ?>" slug="map">
                <div class="search__tabs--map">
                    <search-map-event :pseudo-query="pseudoQuery" position="map"></search-map-event>
                </div>
            </mc-tab>
            <mc-tab icon="chart" label="<?php i::esc_attr_e('Indicadores') ?>" slug="chart">
                <div class="search__tabs--chart">
                    <search-map-event :pseudo-query="pseudoQuery" position="map"></search-map-event>
                </div>
            </mc-tab>
        </mc-tabs>
    </template>

</search>