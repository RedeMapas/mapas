<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->layout = 'entity';

$this->import('
    entity-table
    mc-card
    mc-icon
    mc-multiselect
    mc-select
    mc-status
    mc-tag-list
    v1-embed-tool
');

$entity = $this->controller->requestedEntity;
?>
<div class="opportunity-registration-table grid-12">
    <div v-if="!hideTitle" class="col-12">
        <h2 class="opportunity-status" v-if="phase.publishedRegistrations"><?= i::__("Os resultados já foram publicados") ?></h2>
        <h2 class="opportunity-status" v-if="!phase.publishedRegistrations && isPast()"><?= i::__("A fase já está encerrada") ?></h2>
        <h2 class="opportunity-status" v-if="isHappening()"><?= i::__("A fase está em andamento") ?></h2>
        <h2 class="opportunity-status" v-if="isFuture()"><?= i::__("A fase ainda não iniciou") ?></h2>
    </div>
    <template v-if="!isFuture()">
        <template v-if="!hideActions">
            <?php $this->applyTemplateHook('registration-list-actions', 'before', ['entity' => $entity]); ?>
            <div class="col-12 opportunity-registration-table__buttons">
                <?php $this->applyTemplateHook('registration-list-actions', 'begin', ['entity' => $entity]); ?>
                
                <?php $this->applyTemplateHook('registration-list-actions', 'end', ['entity' => $entity]); ?>
            </div>
            <?php $this->applyTemplateHook('registration-list-actions', 'after', ['entity' => $entity]); ?>
        </template>
        <div class="col-12"> 
            <entity-table controller="opportunity" endpoint="findRegistrations" type="registration" :query="query" :limit="100" :sort-options="sortOptions" :order="order" :select="select" :headers="headers" phase:="phase" required="number,options" :visible="visibleColumns" @clear-filters="clearFilters" @remove-filter="removeFilter($event)" show-index :hide-filters="hideFilters" :hide-sort="hideSort" :hide-actions='hideActions' :hide-header="hideHeader">
                <template #title>
                    <slot name="title"></slot>
                </template>
                
                <?php $this->applyTemplateHook('registration-list-actions-entity-table', 'before', ['entity' => $entity]); ?>
                <template v-if="!hideActions" #actions="{entities,filters}">
                    <div class="opportunity-registration-table__actions">
                        <h4 class="bold"><?= i::__('Ações:') ?></h4>
                        <div class="opportunity-registration-table__actions-buttons">
                        <?php $this->applyTemplateHook('registration-list-actions-entity-table', 'begin', ['entity' => $entity]); ?>
                            <mc-link :entity="phase" route="reportDrafts" class="button button--primarylight button--icon"><?= i::__("Baixar rascunhos") ?> <mc-icon name="download"></mc-icon></mc-link>
                            <mc-link :entity="phase" route="report" class="button button--primarylight button--icon"><?= i::__("Baixar lista de inscrições") ?> <mc-icon name="download"></mc-icon></mc-link>
                            <mc-export-spreadsheet :owner="phase" endpoint="registrations" :params="{entityType: 'registration', '@select': 'id,createTimestamp,sentTimestamp,status,subsite,consolidatedResult,number,proponentType,range,score,eligible,agentsData', '@order': 'id DESC', query}" group="registrations-spreadsheets"></mc-export-spreadsheet>
                        <?php $this->applyTemplateHook('registration-list-actions-entity-table', 'end', ['entity' => $entity]); ?>
                        </div>
                    </div>
                </template>
                <?php $this->applyTemplateHook('registration-list-actions-entity-table', 'after', ['entity' => $entity]); ?>

                <template #filters="{entities,filters}">
                    <div class="opportunity-registration-table__multiselects">
                        <mc-select v-if="statusEvaluationResult" class="col-5" :default-value="selectedAvaliation" @change-option="filterAvaliation($event,entities)" placeholder="<?= i::__("Resultado de avaliação") ?>">
                            <option v-for="(item,index) in statusEvaluationResult" :value="index">{{item}}</option>
                        </mc-select>
                        
                        <mc-multiselect class="col-2" :model="selectedStatus" :items="status" title="<?= i::esc_attr__('Status') ?>" @selected="filterByStatus(entities)" @removed="filterByStatus(entities)" hide-filter hide-button>
                            <template #default="{popover, setFilter}">
                                <div class="field">
                                    <input class="mc-multiselect--input" @keyup="setFilter($event.target.value)" @focus="popover.open()" placeholder="<?= i::esc_attr__('Status: ') ?>">
                                </div>
                            </template>
                        </mc-multiselect>
                        
                        <mc-multiselect v-if="categories" class="col-2" :model="selectedCategories" :items="categories" title="<?= i::esc_attr__('Categorias') ?>" @selected="filterByCategories(entities)" @removed="filterByCategories(entities)" hide-filter hide-button>
                            <template #default="{popover, setFilter}">
                                <div class="field">
                                    <input class="mc-multiselect--input" @keyup="setFilter($event.target.value)" @focus="popover.open()" placeholder="<?= i::esc_attr__('Categorias: ') ?>">
                                </div>
                            </template>
                        </mc-multiselect>

                        <mc-multiselect v-if="proponentTypes" class="col-2" :model="selectedProponentTypes" :items="proponentTypes" title="<?= i::esc_attr__('Tipos de proponente') ?>" @selected="filterByProponentTypes(entities)" @removed="filterByProponentTypes(entities)" hide-filter hide-button>
                            <template #default="{popover, setFilter}">
                                <div class="field">
                                    <input class="mc-multiselect--input" @keyup="setFilter($event.target.value)" @focus="popover.open()" placeholder="<?= i::esc_attr__('Tipos de proponente: ') ?>">
                                </div>
                            </template>
                        </mc-multiselect>

                        <mc-multiselect v-if="ranges" class="col-2" :model="selectedRanges" :items="ranges" title="<?= i::esc_attr__('Faixas') ?>" @selected="filterByRanges(entities)" @removed="filterByRanges(entities)" hide-filter hide-button>
                            <template #default="{popover, setFilter}">
                                <div class="field">
                                    <input class="mc-multiselect--input" @keyup="setFilter($event.target.value)" @focus="popover.open()" placeholder="<?= i::esc_attr__('Faixas:') ?>">
                                </div>
                            </template>
                        </mc-multiselect>
                    </div>
                </template>

                <template #attachments={entity}>
                    <a v-if="entity.files?.zipArchive?.url" :href="entity.files?.zipArchive?.url"><?= i::__('Anexo') ?></a>
                </template>

                <template #status="{entity}">
                    <mc-select v-if="!statusNotEditable" small :default-value="entity.status" @change-option="setStatus($event, entity)">
                        <mc-status v-for="item in statusDict" :value="item.value" :status-name="item.label"></mc-status>
                    </mc-select>
                    
                    <mc-status v-if="statusNotEditable" :value="getStatus(entity.status).status" :status-name="getStatus(entity.status).label"></mc-status>
                </template>

                <template #consolidatedResult="{entity}"> 
                    {{consolidatedResultToString(entity)}}
                </template>

                <template #agent="{entity}">
                    <a :href="entity.owner?.singleUrl">{{entity.owner?.name}}</a>
                </template>

                <template #number="{entity}">
                    <a :href="entity.singleUrl">{{entity.number}}</a>
                </template>

                <template #eligible="{entity}">
                    <span v-if="entity.eligible"><?= i::__('Sim') ?></span>
                    <span v-else> &nbsp; </span>
                </template>

            </entity-table>
        </div>
    </template>
</div>