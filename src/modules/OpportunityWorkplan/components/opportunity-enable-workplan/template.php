<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-tag-list
');
?>
<div class="opportunity-enable-workplan">
    <h4 class="bold opportunity-enable-workplan__title"><?= i::__('Plano de trabalho') ?></h4>
    <h6><?= $this->text('header-description', i::__('Configurar parâmetros do plano de trabalho')) ?></h6>
    <div class="opportunity-enable-workplan__content">
        <div class="field col-12">
            <div class="field__group">
                <label class="field__checkbox">
                    <input type="checkbox" v-model="entity.enableWorkplan" @click="autoSave()" /><?= i::__("Habilitar plano de trabalho") ?>
                </label>
            </div>
        </div>

        <div v-if="entity.enableWorkplan">
            <div id="data-project" class="opportunity-enable-workplan__block col-12">
                <h4 class="bold opportunity-enable-workplan__title"><?= i::__('Dados do projeto') ?></h4>
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" v-model="entity.workplan_dataProjectlimitMaximumDurationOfProjects" @click="autoSave()" /><?= i::__("Limitar a duração máxima dos projetos") ?>
                        </label>
                    </div>

                    <div class="field__group">
                        <label class="field__group">
                            <?php i::_e('Duração máxima (meses):') ?>
                        </label>
                        <input type="number" :disabled="!entity.workplan_dataProjectlimitMaximumDurationOfProjects" v-model="entity.workplan_dataProjectmaximumDurationInMonths" @change="autoSave()">
                    </div>
                </div>
            </div>
            <div id="data-metas" class="opportunity-enable-workplan__block  col-12">
                <h4 class="bold opportunity-enable-workplan__title"><?= i::__('Metas') ?></h4>
                <h6><?= $this->text('header-description', i::__('As metas são constituídas por uma ou mais entregas')) ?></h6>
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" v-model="entity.workplan_metaInformTheStageOfCulturalMaking" @click="autoSave()" /><?= i::__("Informar a etapa do fazer cultural") ?>
                        </label>
                    </div>

                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" v-model="entity.workplan_metaInformTheValueGoals" @click="autoSave()" /><?= i::__("Informar o valor da meta") ?>
                        </label>
                    </div>

                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" v-model="entity.workplan_metaLimitNumberOfGoals" @click="autoSave()" /><?= i::__("Limitar número de metas") ?>
                        </label>
                    </div>

                    <div class="field__group">
                        <label>
                            <?php i::_e('Limitar número de metas:') ?>
                        </label>
                        <input type="number" :disabled="!entity.workplan_metaLimitNumberOfGoals" v-model="entity.workplan_metaMaximumNumberOfGoals" @change="autoSave()">
                    </div>
                </div>
            </div>
            <div id="data-delivery" class="opportunity-enable-workplan__block  col-12">
                <h4 class="bold opportunity-enable-workplan__title"><?= i::__('Entregas da meta') ?></h4>
                <h6><?= $this->text('header-description', i::__('As entregas são evidências (arquivos ou links) que comprovam a conclusão da meta.')) ?></h6>
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" v-model="entity.workplan_deliveryReportTheDeliveriesLinkedToTheGoals" @click="autoSave()" /><?= i::__("Informar as entregas vinculadas à meta") ?>
                        </label>
                    </div>

                    <div v-if="entity.workplan_deliveryReportTheDeliveriesLinkedToTheGoals">                    
                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_deliveryLimitNumberOfDeliveries" @click="autoSave()" /><?= i::__("Limitar número de entregas") ?>
                            </label>
                        </div>

                        <div class="field__group">
                            <label>
                                <?php i::_e('Número máximo de entregas:') ?>
                            </label>
                            <input type="number" :disabled="!entity.workplan_deliveryLimitNumberOfDeliveries" v-model="entity.workplan_deliveryMaximumNumberOfDeliveries" @change="autoSave()">
                        </div>

                        <div class="field">
                            <label> <?php i::_e('Informar tipo de entrega') ?></label>
                            <mc-multiselect :model="entity.workplan_monitoringInformDeliveryType" title="<?php i::_e('Selecione as áreas de atuação') ?>" :items="workplan_monitoringInformDeliveryTypeList" hide-filter hide-button>
                                <template #default="{setFilter, popover}">
                                    <input class="mc-multiselect--input" @keyup="setFilter($event.target.value)" @focus="popover.open()" placeholder="<?= i::esc_attr__('Selecione as entregas aceitas no edital') ?>">
                                </template>
                            </mc-multiselect>
                            <mc-tag-list editable :tags="entity.workplan_monitoringInformDeliveryType" classes="opportunity__background opportunity__color"></mc-tag-list>
                        </div>
                    </div>
                </div>

                <div v-if="entity.workplan_deliveryReportTheDeliveriesLinkedToTheGoals" id="data-registration" class="opportunity-enable-workplan__block  col-12">
                    <h4 class="bold opportunity-enable-workplan__title"><?= i::__('Inscrição') ?></h4>
                    <h6><?= $this->text('header-description', i::__('As informações que forem marcadas abaixo serão exigidas dos agentes no momento de inscrição na oportunidade.')) ?></h6>
                    <div class="field col-12">
                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_registrationReportTheNumberOfParticipants" @click="autoSave()" /><?= i::__("Informar a quantidade estimada de público") ?>
                            </label>
                        </div>
                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_registrationInformCulturalArtisticSegment" @click="autoSave()" /><?= i::__("Informar segmento artístico cultural") ?>
                            </label>
                        </div>
                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_registrationReportExpectedRenevue" @click="autoSave()" /><?= i::__("Informar receita prevista") ?>
                            </label>
                        </div>
                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_registrationInformActionPAAR" @click="autoSave()" /><?= i::__("Informar a ação orçamentária (PAAR)") ?>
                            </label>
                        </div>
                    </div>
                </div>
                <div v-if="entity.workplan_deliveryReportTheDeliveriesLinkedToTheGoals" id="data-monitoring" class="opportunity-enable-workplan__block  col-12">
                    <h4 class="bold opportunity-enable-workplan__title"><?= i::__('Monitoramento') ?></h4>
                    <h6><?= $this->text('header-description', i::__('As informações que forem marcadas abaixo serão exigidas dos agentes no momento de monitoramento da oportunidade.')) ?></h6>
                    <div class="field col-12">
                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_monitoringInformTheFormOfAvailability" @click="autoSave()" /><?= i::__("Informar forma de disponibilização") ?>
                            </label>
                        </div>
                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_monitoringEnterDeliverySubtype" @click="autoSave()" /><?= i::__("Informar subtipo de entrega") ?>
                            </label>
                        </div>
                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_monitoringInformAccessibilityMeasures" @click="autoSave()" /><?= i::__("Informar as medidas de acessibilidade") ?>
                            </label>
                        </div>
                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_monitoringInformThePriorityTerritories" @click="autoSave()" /><?= i::__("Informar os territórios prioritários") ?>
                            </label>
                        </div>

                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_monitoringProvideTheProfileOfParticipants" @click="autoSave()" /><?= i::__("Informar o perfil do público") ?>
                            </label>
                        </div>

                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_monitoringInformThePriorityAudience" @click="autoSave()" /><?= i::__("Informar o público prioritário") ?>
                            </label>
                        </div>

                        <div class="field__group">
                            <label class="field__checkbox">
                                <input type="checkbox" v-model="entity.workplan_monitoringReportExecutedRevenue" @click="autoSave()" /><?= i::__("Informar receita executada") ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<div class="config-phase__line col-12"></div>