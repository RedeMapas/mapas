<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
?>
<div class="stepper-evaluations">
    <div class="stepper-evaluations__title"><label><?= i::__('Avaliações abertas e disponíveis') ?></label></div>
    <div class="line"></div>
    <div v-for="item in phases">
        <div class="card-evaluation" v-if="item.currentUserPermissions.evaluateOnTime || item.currentUserPermissions['@control']">
            <div class="card_evaluation__content">
                <h3 class="card-evaluation__content--title">{{item.name}}</h3>
                <div class="card-evaluation__content--items">
                    <div class="phase">
                        <div class="phase__title"><label class="phase__title--title"><?= i::__('Tipo') ?>: </label><span class="item">{{evaluationTypes[item.type]}}</span></div>
                    </div>
                    <div class="period"><label class="period__label"> <?= i::__('PERÍODO DE AVALIAÇÃO') ?>: </label><span class="period__content">{{item.evaluationFrom.date('numeric year')}} <?= i::__('até') ?> {{item.evaluationTo.date('numeric year')}} as {{item.evaluationTo.time('long year')}}</span></div>
                </div>
            </div>
            <div class="btn">
                <mc-link route="opportunity/opportunityEvaluations" :params="[item.opportunity.id]" class="button button--primary evaluation-button"> <?= i::__('Avaliar') ?><mc-icon name="arrow-right-ios"></mc-icon></mc-link>
            </div>
        </div>
    </div>
    <h3 class="stepper-evaluations__title secondTitle"><?= i::__('Avaliações Encerradas') ?></h3>
    <div class="line"></div>
    <div class="out-evalution"><?= i::__('Você ainda não tem avaliações encerradas.') ?></div>
</div>