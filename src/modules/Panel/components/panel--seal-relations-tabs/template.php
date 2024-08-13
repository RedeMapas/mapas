<?php

/**
 * @var \MapasCulturais\Themes\BaseV2\Theme $this
 * @var \MapasCulturais\App $app
 */

use MapasCulturais\i;

$this->import('
    mc-breadcrumb
    mc-container
    mc-tabs
    mc-tab
    mc-confirm-button
    mc-icon
');
?>

<mc-tabs>
    <mc-tab :label="validSealsLabel" slug="valid">
            <div class="seals-relations-background">
                <p>Esses são os selos que foram atribuídos à você e estão atualmente válidos. Esses selos são apresentados publicamente na plataforma.</p>
                <hr>

                <div class="seals-relations-item">
                    <div class="seals-relations-item-content">
                        <template v-if="validSeals && validSeals.length">
                            <template v-for="seal in validSeals" :key="seal.sealRelationId">
                                <slot :seal="seal">
                                    <div class="seal-relation-content">
                                        <div class="seals-relations-item-image">
                                            <img v-if="seal?.files?.avatar?.transformations?.avatarSmall?.url" :src="seal.files.avatar.transformations.avatarSmall.url" alt="Seal Image">
                                            <mc-icon v-else name="seal" alt="Placeholder Image">
                                        </div>
                                        <div class="seal-text-cont">
                                            <h3>{{seal.name}}</h3>
                                            <!-- <input type="checkbox" id="show-on-profile" name="show-on-profile" />
                                            <label for="show-on-profile">Mostrar no perfil</label> -->
                                            <p style="margin-bottom: 3%;"><b>Criador do selo: </b><span class="seal-creator">{{seal.sealRelationFullData.seal.owner.name}}</span></p>
                                            <p><b>Selo atribuido por: </b><span class="seal-creator">{{seal.sealRelationFullData.agent.name}}</span></p>
                                            <p><b>Descrição curta: </b>{{seal.sealRelationFullData.seal.shortDescription}}</p>
                                            <div class="seal-dates">    
                                                <p><mc-icon name="date"></mc-icon> Data de recebimento do selo: {{ formatReceivedDate(seal) }}</p>
                                                <p><mc-icon name="date"></mc-icon> Validade do selo: {{ formatValidDate(seal) }}</p>
                                            </div>
                                                <div class="seal-footer">
                                            <div class="icon">
                                                <mc-confirm-button @confirm="removeSeal(seal)">
                                                    <template #button="modal">
                                                    <button class="button" @click="modal.open()"><mc-icon name="trash"></mc-icon>Excluir selo</button>
                                                    </template>
                                                    <template #message="message">
                                                        <?php i::_e('Remover selo?') ?>
                                                    </template>
                                                </mc-confirm-button>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </slot>
                                <hr>
                            </template>
                        </template>
                        <template v-else>
                            <p>No seals available.</p>
                        </template>
                    </div>
                </div>
            </div>
    </mc-tab>

    <mc-tab :label="expiredSealsLabel" slug="expired"><mc-container>
            <div class="seals-relations-background">
                <p>Esses são os selos que foram atribuídos à você e estão atualmente válidos. Esses selos são apresentados publicamente na plataforma.</p>
                <hr>

                <div class="seals-relations-item">
                    <div class="seals-relations-item-content">
                        <template v-if="expiredSeals && expiredSeals.length">
                            <template v-for="seal in expiredSeals" :key="seal.sealRelationId">
                                <slot :seal="seal">
                                    <div class="seal-relation-content">
                                        <div class="seals-relations-item-image"style="display:inline-block;vertical-align:top;">
                                            <img v-if="seal?.files?.avatar?.transformations?.avatarSmall?.url" :src="seal.files.avatar.transformations.avatarSmall.url" alt="Seal Image">
                                            <img v-else src="https://via.placeholder.com/150" alt="Placeholder Image">
                                        </div>
                                        <div class="seal-text-cont">
                                            <h3>{{seal.name}}</h3>
                                            <!-- <input type="checkbox" id="show-on-profile" name="show-on-profile" />
                                            <label for="show-on-profile">Mostrar no perfil</label> -->
                                            <p style="margin-bottom: 3%;"><b>Criador do selo: </b><span class="seal-creator">{{seal.sealRelationFullData.seal.owner.name}}</span></p>
                                            <p><b>Selo atribuido por: </b><span class="seal-creator">{{seal.sealRelationFullData.agent.name}}</span></p>
                                            <p><b>Descrição curta: </b>{{seal.sealRelationFullData.seal.shortDescription}}</p>
                                            <div class="seal-dates">    
                                                <p><mc-icon name="date"></mc-icon> Data de recebimento do selo: {{ formatReceivedDate(seal) }}</p>
                                                <p><mc-icon name="date"></mc-icon> Validade do selo: {{ formatValidDate(seal) }}</p>
                                            </div>
                                        <div class="seal-footer">
                                            <div class="icon">
                                                <mc-confirm-button @confirm="removeSeal(seal)">
                                                    <template #button="modal">
                                                    <button class="button" @click="modal.open()"><mc-icon name="trash"></mc-icon>Excluir selo</button>
                                                    </template>
                                                    <template #message="message">
                                                        <?php i::_e('Remover selo?') ?>
                                                    </template>
                                                </mc-confirm-button>
                                            </div>
                                        </div>
                                    </div>
                                </slot>
                                <hr>
                            </template>
                        </template>
                        <template v-else>
                            <p>No seals available.</p>
                        </template>
                    </div>
                </div>
            </div>
        </mc-container></mc-tab>
</mc-tabs>