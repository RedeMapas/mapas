<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-icon
    mc-loading
');
?>
<div class="opportunity-evaluations-list" v-if="showList()">
    <div :class="['opportunity-evaluations-list__container', 'isOpen']">
        <button class="act-button" @click="toggleMenu()">
            <label class="label">{{textButton }}</label>
        </button>

        <div class="find">
            <div class="content">
                <input type="text" v-model="keywords" @input="timeOutFind()" @keyup.enter="timeOutFind(0)" class="label-evaluation__search">
            </div>
            <div class="label-evaluation">
                <div class="label-evaluation__check">
                    <label class="label-evaluation__check--label">
                        <input type="checkbox" v-model="pending" class="label-evaluation__check--pending">
                        <?= i::__('Mostrar somente pendentes') ?>
                    </label>
                </div>
            </div>
        </div>
        <mc-loading :condition="loading"><?= i::__('carregando...') ?></mc-loading>
        <ul v-if="!loading" class="evaluation-list">
            <li v-for="evaluation in evaluations" :key="evaluation.registrationId" :class="[{'evaluation-list__card--modify': entity.id == evaluation.registrationid}, 'evaluation-list__card']">
                <div class="evaluation-list__content">
                    <a :href="evaluation.url" class="link">
                        <div class="card-header">
                            <span class="card-header__name">{{evaluation.registrationNumber}}</span>
                        </div>
                        <div class="card-content">
                            <div v-if="evaluation.agentname" class="card-content__middle">
                                <mc-icon name='agent-1'></mc-icon>

                                <span class="value">
                                    <strong>{{evaluation.agentname}}</strong>
                                </span>
                            </div>
                            <div class="card-content__middle">
                                <span class="subscribe"><?= i::__('Data da inscrição') ?></span>
                                <span class="value">
                                    <strong>{{evaluation.registrationSentTimestamp.date()}} {{evaluation.registrationSentTimestamp.time()}}</strong>
                                </span>
                            </div>
                        </div>
                        <div class="card-state">
                            <span class="state"><?= i::__('Resultado de avaliação') ?></span>
                            <span :class="verifyState(evaluation)" class="card-state__info">
                                <mc-icon  name="circle"></mc-icon>
                                <h5 class="bold" v-if="evaluation.resultString">{{evaluation.resultString}}</h5>
                                <h5 class="bold" v-if="!evaluation.resultString"> <?= i::__('Pendente') ?></h5>
                            </span>
                            <mc-link route="registration/evaluation/" :params="[evaluation.registrationId]" icon="arrowPoint-right" right-icon class="button button--primary-outline"><?= i::__('Acessar') ?></mc-link>
                        </div>
                    </a>
                </div>
            </li>
        </ul>
    </div>
</div>