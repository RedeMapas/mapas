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
    entity-status
    entity-terms
    mc-breadcrumb
    mc-card
    mc-container
    mc-tabs
    mc-tab
    mc-accordion
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
        <mc-tab label="<?= i::_e('Meu perfil') ?>" slug="info">
            <mc-container class="edit--profile">
                <mc-accordion class="accordion-edit--profile">
                    <entity-status :entity="entity"></entity-status>
                    <!-- <mc-card class="feature"> -->
                        <template #title>
                            <label><?php i::_e("Informações de Apresentação") ?></label>
                            <p><?php i::_e("Os dados inseridos abaixo serão exibidos para todos os usuários") ?></p>
                        </template>
                        <template #content>
                            <div class="left">
                                <div class="grid-12 v-bottom black-content">
                                    <entity-cover :entity="entity" classes="col-12"></entity-cover>
                                    
                                    <div class="col-12 grid-12">
                                        <?php $this->applyTemplateHook('entity-info','begin') ?>
                                        <div class="flex-container edit--agent">
                                            <entity-profile class="profile--edit--agent" :entity="entity" :label="false"></entity-profile>
                                        </div>
                                        <div class="col-12 grid-12 v-bottom">
                                            <entity-field :entity="entity" classes="col-12" prop="name" label="<?php i::_e('Nome do Agente') ?>"></entity-field>
                                        </div>
                                        <?php $this->applyTemplateHook('entity-info','end') ?>
                                    </div>

                                    <entity-field :entity="entity" classes="col-12" prop="shortDescription"></entity-field>
                                    <entity-field :entity="entity" classes="col-12" prop="site"></entity-field>
                                </div>
                            </div>
                            <div class="divider"></div>
                            <div class="right">
                                <div class="grid-12 black-content">
                                    <?php $this->applyTemplateHook('edit2-entity-info-taxonomie-area','before') ?>
                                    <entity-terms :entity="entity" taxonomy="area" editable classes="col-12" title="<?php i::_e('Área de atuação'); ?>"></entity-terms>
                                    <?php $this->applyTemplateHook('edit2-entity-info-taxonomie-area','after') ?>

                                    <entity-social-media :entity="entity" editable alwaysshowicons></entity-social-media>
                                </div>
                            </div>
                        </template>
                    <!-- </mc-card> -->
                </mc-accordion>
            </mc-container>
            <main>
                <mc-container class="edit--profile">
                    <mc-accordion class="accordion-edit--profile">
                        <template #title>
                            <label><?php i::_e("Dados do Agente Coletivo"); ?></label>
                            <p class="data-subtitle"><?php i::_e("Os dados inseridos abaixo serão registrados apenas no sistemas e não serão exibidos publicamente") ?></p>
                        </template>
                        <template #content>
                            <div class="grid-12 black-content">
                                <entity-field :entity="entity" classes="col-4 sm:col-12" prop="name" label="<?php i::_e('Nome fantasia ou razão social') ?>"></entity-field>
                                <entity-field v-if="global.auth.is('admin')" :entity="entity" prop="type" @change="entity.save(true).then(() => global.reload())" classes="col-4"></entity-field>
                                <entity-field :entity="entity" classes="col-4" prop="cnpj" label="CNPJ"></entity-field>
                                <entity-field :entity="entity" classes="col-4" prop="dataDeNascimento" label="<?= i::__('Data de fundação') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-4" prop="emailPrivado" label="<?= i::__('E-mail privado ') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-4" prop="telefonePublico" label="<?= i::__('Telefone público com DDD') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-4" prop="emailPublico" label="<?= i::__('E-mail público') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-4 sm:col-12" prop="telefone1" label="<?= i::__('Telefone privado 1 com DDD') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-4 sm:col-12" prop="telefone2" label="<?= i::__('Telefone privado 2 com DDD') ?>"></entity-field>
                                <div class="col-12 divider"></div>
                                <entity-location :entity="entity" classes="col-12" editable></entity-location>
                            </div>
                        </template>
                    </mc-accordion>
                </mc-container>
                <mc-container class="edit--profile">
                    <mc-accordion class="accordion-edit--profile">
                        <template #title>
                            <label><?php i::_e("informações públicas"); ?></label>
                            <p><?php i::_e("Os dados inseridos abaixo assim como as informações de apresentação também são exibidos publicamente"); ?></p>
                        </template>
                        <template #content>
                            <div class="grid-12 black-content">
                                <entity-field :entity="entity" classes="col-12" prop="longDescription" editable></entity-field>
                                <entity-files-list :entity="entity" classes="col-12" group="downloads" title="Adicionar arquivos para download" editable></entity-files-list>
                                <entity-links :entity="entity" classes="col-12" title="<?php i::_e('Adicionar links'); ?>" editable></entity-links>
                                <entity-gallery-video title="<?php i::_e('Adicionar vídeos') ?>" :entity="entity" classes="col-12" editable></entity-gallery-video>
                                <entity-gallery title="<?php i::_e('Adicionar fotos na galeria') ?>" :entity="entity" classes="col-12" editable></entity-gallery>
                            </div>
                        </template>
                    </mc-accordion>
                </mc-container>
            </main>
            <aside>
                <mc-contaianer class="edit--profile">
                    <mc-accordion class="accordion-edit--profile">
                        <template #title>
                            <label><?php i::_e("XXXXX"); ?></label>
                            <p><?php i::_e("Sem nome e sem descrição"); ?></p>
                        </template>
                        <template #content>
                            <div class="grid-12 black-content">
                                <entity-admins :entity="entity" classes="col-12" editable></entity-admins>
                                <entity-terms :entity="entity" taxonomy="tag" classes="col-12" title="Tags" editable></entity-terms>
                                <entity-related-agents :entity="entity" classes="col-12" editable></entity-related-agents>
                                <entity-owner :entity="entity" title="Publicado por" classes="col-12" editable></entity-owner>
                            </div>
                        </template>
                    </mc-accordion>
                </mc-card>
            </aside>
    </mc-tab>
    <?php $this->applyTemplateHook('tabs','end') ?>
</mc-tabs>

<entity-actions :entity="entity" editable></entity-actions>
</div>
<confirm-before-exit :entity="entity"></confirm-before-exit>