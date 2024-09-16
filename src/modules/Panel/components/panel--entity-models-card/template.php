<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-avatar
    mc-icon
    mc-title
    panel--entity-actions
    opportunity-create-based-model 

');
?>
<article class="panel__row panel-entity-models-card col-6" v-if="showModel">
    <header class="panel-entity-models-card__header">
        <div class="left">
            <slot name="picture" :entity="entity">
                <mc-avatar :entity="entity" size="medium"></mc-avatar>
            </slot>
            <div class="panel-entity-models-card__header--info">
                <slot name="title" :entity="entity">
                    <a :href="entity.singleUrl" class="panel-entity-models-card__header--info-link">
                        <mc-title tag="h2" :shortLength="100" :longLength="110">
                            {{ entity.name }}
                        </mc-title>
                    </a>            
                </slot>
            </div>
        </div>
        <div class="right">
            <div class="panel-entity-models-card__header-actions">
                <slot name="header-actions" :entity="entity">
                    {{ entity.isModelPublic == 0 ? 'MEU MODELO' : 'MODELO PÚBLICO' }}
                </slot>
            </div>
        </div>
    </header>
    <main class="panel-entity-models-card__main">
        <span class="card-info"></span>
        <div class="card-desc">
            <div v-for="model in models" :key="model.id">
                <span v-if="model.id == entity.id">
                    <p>{{ model.descricao.substring(0, 150) }}</p>
                    <mc-icon name="project" class="icon-model"></mc-icon> 
                    <strong><?=i::__('Tipo de Oportunidade: ')?></strong>{{ entity.type.name }}
                    <br>
                    <mc-icon name="circle-checked" class="icon-model"></mc-icon>
                    <strong><?=i::__('Número de fases: ')?></strong>{{ model.numeroFases }}
                    <br>
                    <mc-icon name="date" class="icon-model"></mc-icon>
                    <strong><?=i::__('Tempo estimado: ')?></strong>{{ model.tempoEstimado }}
                    <br>
                    <mc-icon name="agent" class="icon-model"></mc-icon>
                    <strong><?=i::__('Tipo de agente: ')?></strong> {{ model.tipoAgente }}
                    <br><br>
                    <?php if($app->user->is('admin')): ?>
                    <label v-if="entity.currentUserPermissions?.modify">
                        <input type="checkbox" v-model="isModelPublic" />
                        <?= i::__("Modelo público") ?>
                    </label>
                    <br><br>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </main>
    <footer class="panel-entity-models-card__footer">
        <div class="panel-entity-models-card__footer-actions">
            <slot name="footer-actions">
                <div class="panel-entity-models-card__footer-actions left">
                    <slot name="entity-actions-left" :entity="entity">
                        <panel--entity-actions 
                            :entity="entity" 
                            @deleted="$emit('deleted', $event)"
                            :on-delete-remove-from-lists="onDeleteRemoveFromLists"
                            :buttons="leftButtons"
                        ></panel--entity-actions>
                    </slot>
                </div>
                <div class="panel-entity-models-card__footer-actions right">
                    <slot name="entity-actions-center" >
                    </slot>
                    <slot name="entity-actions-right" >
                        <div v-if="showModel && entity.status != -2 && entity.__objectType == 'opportunity' && entity.isModel == 1">
                            <opportunity-create-based-model :entity="entity" classes="col-12"></opportunity-create-based-model>
                        </div>
                    </slot>
                </div>
            </slot>
        </div>
    </footer>

</article>
