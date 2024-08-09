<?php

use MapasCulturais\i;

$this->layout = 'entity';

$this->import('
    confirm-before-exit
    entity-actions
    entity-admins
    entity-cover
    entity-field
    entity-files-list
    entity-gallery
    entity-gallery-video
    entity-header
    entity-links
    entity-location
    entity-owner
    entity-profile
    entity-related-agents
    entity-social-media
    entity-terms
    entity-status
    mc-breadcrumb
    mc-card
    mc-accordion
    mc-tabs
    mc-tab
    mc-avatar
    mc-confirm-button
    mc-relation-card
    select-entity
');

$label = $this->isRequestedEntityMine() ? i::__('Meus agentes') : i::__('Agentes');
$this->breadcrumb = [
    ['label' => i::__('Painel'), 'url' => $app->createUrl('panel', 'index')],
    ['label' => $label, 'url' => $app->createUrl('panel', 'agents')],
    ['label' => $entity->name, 'url' => $app->createUrl('agent', 'edit', [$entity->id])],
];
?>

<div class="main-app">
    <mc-breadcrumb></mc-breadcrumb>
    <entity-header :entity="entity" editable></entity-header>

    <mc-tabs class="tabs" sync-hash>
        <?php $this->applyTemplateHook('tabs','begin') ?>
        <mc-tab label="<?= i::_e('Meu perfil') ?>" slug="info"> <!-- mudar o slug -->
            <mc-container class="container">
                <label class="entity-cards__label"><?php i::_e("Campos marcados com <span style='color:red;'>*</span> são de preenchimento obrigatório.") ?></label>
            </mc-container>
            <mc-container class="edit--profile">
                <mc-accordion class="accordion-edit--profile">
                    <entity-status :entity="entity"></entity-status>
                    <!-- <mc-card class="feature__full"> -->
                        <template #title>
                            <h3><?php i::_e("Informações de Apresentação") ?></h3>
                        </template>
                        <template #content>
                            <div class="grid-12 black-content">
                                <p class="col-12 mc-card__card_info"><?php i::_e("Os dados inseridos abaixo serão exibidos para todos os usuários") ?></p>
                                <entity-cover :entity="entity" classes="col-12"></entity-cover>
                                <!-- <div class="col-12 grid-12"> -->
                                    <!-- <div class="entity-profile"> -->
                                        <div class="flex-container edit--agent">
                                            <entity-profile class="profile--edit--agent" :entity="entity" :label="false"></entity-profile>
                                        </div>
                                    <!-- </div> -->
                                <!-- </div> -->
                                
                                <div class="col-12 popover__area">
                                    <div class="flex-container question">
                                        <?php $this->applyTemplateHook('entity-info','begin') ?>
                                        <mc-popover openside="down-right" classes="popover name">
                                                <p class="popover__content">Esse será o seu <strong>nome público</strong><br> na plataforma: todas as pessoas<br> irão ver esse nome quando<br> acessarem o seu perfil.</p>
                                                <template #button="popover">
                                                    <a @click="popover.toggle()"><mc-icon name="question" class="popover__avatar__name"></mc-icon></a>
                                                </template>
                                        </mc-popover>
                                        <entity-field :entity="entity" classes="entity-terms__edit-agent" prop="name" label="<?php i::_e('Nome de perfil') ?>"></entity-field>
                                    </div>
                                </div>
                                
                                <?php $this->applyTemplateHook('entity-info','end') ?>
                                <?php $this->applyTemplateHook('edit1-entity-info-taxonomie-area','before') ?>
                                
                                <div class="col-12 popover__area">
                                    <div class="flex-container question">
                                        <div>
                                            <entity-terms :entity="entity" taxonomy="area" editable classes="entity-terms__edit-agent" title="<?php i::_e('Área(s) de atuação'); ?>"></entity-terms>
                                        </div>
                                        <mc-popover openside="down-right" class="popover">
                                                <p class="popover__content">São as suas áreas de atuação<br> profissional e/ou de conhecimentos<br> especializados, sejam eles teóricos<br> e/ou técnicos.</p>
                                                <template #button="popover">
                                                    <a @click="popover.toggle()"><mc-icon name="question" class="popover__avatar__area"></mc-icon></a>
                                                </template>
                                        </mc-popover>
                                    </div>
                                </div>
                                
                                <?php $this->applyTemplateHook('edit1-entity-info-taxonomie-area','after') ?>

                                <div class="col-12 popover__area">
                                    <div class="flex-container question">
                                        <entity-terms :entity="entity" taxonomy="funcao" editable classes="entity-terms__edit-agent" title="<?php i::_e('Função(ões) na cultura'); ?>"></entity-terms>
                                        <mc-popover openside="down-right" class="popover__funcao">
                                            <p class="popover__content">
                                                São funções que você exerce<br> no cenário cultural.
                                            </p>
                                            <template #button="popover">
                                                <a @click="popover.toggle()">
                                                <mc-icon name="question" class="popover__avatar__funcao"></mc-icon>
                                                </a>
                                            </template>
                                        </mc-popover>
                                    </div>
                                </div>

                                <div class="col-12 popover__area">
                                    <div class="flex-container question">
                                        <entity-terms :entity="entity" taxonomy="tag" classes="entity-terms__edit-agent" title="Tags" editable></entity-terms>
                                        <mc-popover openside="down-right" class="popover">
                                            <p class="popover__content">São tags que identificam seus<br> interesses, seus valores.</p>
                                            <template #button="popover">
                                                <a @click="popover.toggle()"><mc-icon name="question" class="popover__avatar__tag"></mc-icon></a>
                                            </template>
                                        </mc-popover>
                                    </div>
                                </div>
                
                                <?php $this->applyTemplateHook('edit1-entity-info-shortDescription','before') ?>
                                <entity-field :entity="entity" classes="col-12 entity-terms__edit-agent" prop="shortDescription" label="<?php i::_e('Descrição curta') ?>"></entity-field>
                                <?php $this->applyTemplateHook('edit1-entity-info-shortDescription','after') ?>
                                <entity-field :entity="entity" classes="col-12 entity-terms__edit-agent" prop="longDescription" editable></entity-field>
                
                                <div class="col-12 black-content">Site</div>
                                <?php $this->applyTemplateHook('edit1-entity-info-site','before') ?>
                                <entity-field :entity="entity" classes="col-6 sm:col-12" prop="site"></entity-field>
                                <entity-field :entity="entity" classes="col-6 sm:col-12" prop="siteDescription"></entity-field>
                                <?php $this->applyTemplateHook('edit1-entity-info-site','after') ?>
                                <entity-field :entity="entity" classes="col-6 sm:col-12" prop="emailPublico" label="<?= i::__('E-mail público') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-6 sm:col-12" prop="telefonePublico" label="<?= i::__('Telefone público (com DDD)') ?>"></entity-field>
                            </div>
                        </template>
                    <!-- </mc-card> -->
                </mc-accordion>
            </mc-container>
            <mc-container class="edit--profile">
                <mc-accordion class="accordion-edit--profile">
                    <!-- <mc-card class="feature__full"> -->
                        <template #title>
                            <h3 class="bold"><?php i::_e("Dados Pessoais"); ?></h3>
                
                        </template>
                        <template #content>
                            <div class="grid-12 black-content">
                                <p class="col-12 mc-card__card_info"><?php i::_e("Os dados inseridos abaixo <strong>não</strong> serão exibidos publicamente, exceto os casos em que forem selecionadas as opções ”Mostrar no perfil”."); ?></p>
                                <h4 class="col-12"><?php i::_e("<strong>Dados de pessoa física</strong>"); ?></h4>
                                <entity-field :entity="entity" classes="col-6 sm:col-12" prop="nomeSocial" label="<?= i::__('Nome Social') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-6 sm:col-12" prop="nomeCompleto" label="<?= i::__('Nome Completo') ?>"></entity-field>
                                <entity-field v-if="global.auth.is('admin')" :entity="entity" prop="type" @change="entity.save(true).then(() => global.reload())" classes="col-12 sm:col-12"></entity-field>
                                <entity-field placeholder="123.456.789-10" :entity="entity" classes="col-4 sm:col-12" prop="cpf"></entity-field>
                                <!-- <entity-field :entity="entity" classes="col-12" prop="cnpj" label="<?= i::__('MEI (CNPJ do MEI)') ?>"></entity-field> -->
                                <entity-field :entity="entity" classes="col-4 sm:col-12" prop="emailPrivado" label="<?= i::__('E-mail pessoal') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-4 sm:col-12" prop="telefone1" label="<?= i::__('Telefone pessoal (com DDD)') ?>"></entity-field>
                                <!-- <entity-field :entity="entity" classes="col-6 sm:col-12" prop="telefone2" label="<?= i::__('Telefone privado 2 com DDD') ?>"></entity-field> -->
                                <div class="divider col-12"></div>
                                <div class="flex-container endereco col-12">
                                    <mc-popover openside="down-right" class="popover__funcao">
                                        <p class="popover__content">
                                            Endereço residencial<br> do agente individual.
                                        </p>
                                        <template #button="popover">
                                            <a @click="popover.toggle()">
                                            <mc-icon name="question" class="popover__avatar__funcao"></mc-icon>
                                            </a>
                                        </template>
                                    </mc-popover>
                                    <entity-location :entity="entity" classes="col-12 sm:col-12" editable></entity-location>
                                </div>
                                <div class="flex-container col-12 itinerante">
                                    <mc-popover openside="down-right" class="popover__funcao">
                                        <p class="popover__content">
                                            Agentes itinerantes são agentes que<br> estão em constante deslocamento e/ou<br> não estabelecem uma residência fixa.<br> São exemplos: pessoas em situação<br> de rua, pessoas que atuam no circo,<br> pessoas nômades, pessoas que estão<br> imigrando, entre outros.
                                        </p>
                                        <template #button="popover">
                                            <a @click="popover.toggle()">
                                            <mc-icon name="question" class="popover__avatar__funcao"></mc-icon>
                                            </a>
                                        </template>
                                    </mc-popover>
                                    <entity-field :entity="entity" type="radio" classes="col-6 sm:col-12" prop="agenteItinerante" label="<?= i::__('É agente itinerante?') ?>"></entity-field>
                                </div>
                                
                            </div>
                            <div class="divider divider--data"></div>
                
                            <div class="grid-12 black-content">
                                <h4 class="col-12"><?php i::_e("<strong>Dados bancários</strong>"); ?></h4>
                                <entity-field placeholder="Selecione" :entity="entity" classes="col-6 sm:col-12" prop="payment_bank_account_type"></entity-field>
                                <entity-field placeholder="Selecione" :entity="entity" classes="col-6 sm:col-12" prop="payment_bank_number"></entity-field>
                                <entity-field placeholder="Digite" :entity="entity" classes="col-3 sm:col-12" prop="payment_bank_account_number"></entity-field>
                                <entity-field placeholder="Digite" :entity="entity" classes="col-3 sm:col-12" prop="payment_bank_dv_account_number"></entity-field>
                                <entity-field placeholder="Digite" :entity="entity" classes="col-3 sm:col-12" prop="payment_bank_branch"></entity-field>
                                <entity-field placeholder="Digite" :entity="entity" classes="col-3 sm:col-12" prop="payment_bank_dv_branch"></entity-field>
                            </div>
                        </template>
                    <!-- </mc-card> -->
                </mc-accordion>
            </mc-container>
            <mc-container class="edit--profile">
                <mc-accordion class="accordion-edit--profile">
                    <!-- <mc-card class="feature__full"> -->
                        <template #title>
                            <h3 class="bold"><?php i::_e("Dados sensíveis"); ?></h3>
                        </template>
                        <template #content>
                            <div class="grid-12 black-content">
                                <p class="col-12 mc-card__card_info"><?php i::_e("Os campos em que não forem selecionadas a opção ”Ocultar do perfil” serão exibidos para todos os usuários da plataforma."); ?></p>
                                <h4 class="col-12"><?php i::_e("<strong>Dados do perfil</strong>"); ?></h4>
                                <entity-field :entity="entity" classes="col-4 sm:col-12" prop="dataDeNascimento" label="<?= i::__('Data de Nascimento') ?>"></entity-field>
                                <!-- <div class="field col-6">
                                    <label>{{entity.$PROPERTIES['idoso'].label}}</label>
                                    <input type="text" disabled :value="entity.idoso ? 'Sim' : 'Não'" />
                                </div>     -->
                                <entity-field :entity="entity" classes="col-4 sm:col-12" prop="genero" label="<?= i::__('Gênero') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-4 sm:col-12" prop="orientacaoSexual" label="<?= i::__('Orientação Sexual') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-6 sm:col-12" prop="raca" label="<?= i::__('Raça/Cor') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-6 sm:col-12" prop="escolaridade" label="<?= i::__('Escolaridade') ?>"></entity-field>
                
                                <entity-field :entity="entity" classes="col-12" prop="pessoaDeficiente" class="pcd col-12" label="<?= i::__('Pessoa com Deficiência') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-12" prop="comunidadesTradicional" label="<?= i::__('Pertence a comunidade tradicional') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-12" prop="comunidadesTradicionalOutros" label="<?= i::__('Não encontrou sua comunidade Tradicional') ?>"></entity-field>
                            </div>
                        </template>
                    <!-- </mc-card> -->
                </mc-accordion>
            </mc-container>
            <mc-container class="edit--profile">
                <mc-accordion class="accordion-edit--profile left--placeholder">
                    <!-- <mc-card class="feature__full"> -->
                        <template #title>
                            <h3 class="bold"><?php i::_e("Redes sociais"); ?></h3>
                        </template>
                        <template #content>
                            <div class="grid-12 black-content">
                                <p class="col-12 mc-card__card_info"><?php i::_e("Os dados inseridos abaixo serão exibidos para todos os usuários da plataforma."); ?></p>
                                <entity-social-media :entity="entity" editable alwaysshowicons></entity-social-media>
                            </div>
                        </template>
                    <!-- </mc-card> -->
                </mc-accordion>
            </mc-container>
            <mc-container class="edit--profile">
                <mc-accordion class="accordion-edit--profile">
                    <!-- <mc-card class="feature__full"> -->
                        <template #title>
                            <h3><?php i::_e("Anexos"); ?></h3>
                        </template>
                        <template #content>
                            <div class="grid-12 black-content">
                                <p class="col-12 mc-card__card_info"><?php i::_e("Os dados inseridos abaixo serão exibidos para todos os usuários da plataforma."); ?></p>
                                <entity-files-list :entity="entity" classes="col-12" group="downloads" title="<?php i::_e('Arquivos para download'); ?>" editable></entity-files-list>
                                <entity-links :entity="entity" classes="col-12" title="<?php i::_e('Links'); ?>" editable></entity-links>
                                <entity-gallery-video :entity="entity" classes="col-12" title="<?php i::_e('Vídeos') ?>" editable></entity-gallery-video>
                                <entity-gallery :entity="entity" classes="col-12" title="<?php i::_e('Imagens') ?>" editable></entity-gallery>
                            </div>
                        </template>
                    <!-- </mc-card> -->
                    <!-- <aside>
                        <mc-card>
                            <template #content>
                                <div class="grid-12">
                                    <entity-admins :entity="entity" classes="col-12" editable></entity-admins>
                                    <entity-related-agents :entity="entity" classes="col-12" editable></entity-related-agents>
                                    <entity-owner :entity="entity" classes="col-12" title="Publicado por" editable></entity-owner>
                                </div>
                            </template>
                        </mc-card>
                    </aside> -->
                </mc-accordion>
            </mc-container>
        </mc-tab>
        <?php $this->applyTemplateHook('tabs','end') ?>
    </mc-tabs>
    
    <entity-actions :entity="entity" editable></entity-actions>
</div>
<confirm-before-exit :entity="entity"></confirm-before-exit>