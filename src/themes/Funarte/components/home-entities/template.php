<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-link
');
?>
<div class="home-entities">
    
    <div class="home-entities__content">
        <div class="home-entities__content--cards">
            <div v-if="global.enabledEntities.opportunities" class="card">
                <div class="tag">
                    <div class="icon">
                        <i class="fas fa-bell"></i> <!-- Ícone de notificação -->
                    </div>
                </div>
                <div class="right">
                    <div class="header">
                        <h3>Oportunidades</h3>
                        <a href="#">ver todas ></a>
                    </div>
                    <p><?= $this->text('opportunities', i::__('Aqui você pode fazer sua inscrição nos editais e oportunidades do Ministério da Cultura (Minc), bem como acompanhar as inscrições em andamento. Nesse espaço, você também pode acessar outras oportunidades da cultura; tais como, oficinas, prêmios e concursos; criar uma oportunidade e divulgá-la para outros agentes culturais.')) ?></p>
                    <mc-link route="search/opportunities" class="button button--icon button--sm opportunity__color">
                        <?= i::__('Ver todos')?>
                        <mc-icon name="access"></mc-icon>
                    </mc-link>
                </div>
            </div>

            <div v-if="global.enabledEntities.events" class="card">
                <div class="tag">
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i> <!-- Ícone de evento -->
                    </div>
                </div>
                <div class="right">
                    <div class="header">
                        <h3>Eventos</h3>
                        <a href="#">ver todos ></a>
                    </div>
                    <p><?= $this->text('eventText', i::__('Você pode pesquisar eventos culturais cadastrados na plataforma filtrando por região, área da cultura, etc. Você também pode incluir seus eventos culturais na plataforma e divulgá-los gratuitamente.')) ?></p>
                    <mc-link route="search/events" class="button button--icon button--sm event__color">
                        <?= i::__('Ver todos')?>
                        <mc-icon name="access"></mc-icon>
                    </mc-link>
                </div>
            </div>

            <div v-if="global.enabledEntities.spaces" class="card">
                <div class="tag">
                    <div class="icon">
                        <i class="fas fa-building"></i> <!-- Ícone de espaço -->
                    </div>
                </div>
                <div class="right">
                    <div class="header">
                        <h3>Espaços</h3>
                        <a href="#">ver todos ></a>
                    </div>
                    <p><?= $this->text('spaces', i::__('Aqui você pode cadastrar seus espaços culturais e colaborar com o Mapa da Cultura! Além disso, você pode pesquisar por espaços culturais cadastrados na sua região; tais como teatros, bibliotecas, centros culturais e outros.')) ?></p>
                    <mc-link route="search/spaces" class="button button--icon button--sm space__color">
                        <?= i::__('Ver todos')?>
                        <mc-icon name="access"></mc-icon>
                    </mc-link>
                </div>
            </div>

            <div v-if="global.enabledEntities.agents" class="card">
                <div class="tag">
                    <div class="icon">
                        <i class="fas fa-users"></i> <!-- Ícone de agente -->
                    </div>
                </div>
                <div class="right">
                    <div class="header">
                        <h3>Agentes</h3>
                        <a href="#">ver todos ></a>
                    </div>
                    <p><?= $this->text('agents', i::__('Neste espaço, é possível buscar e conhecer os agentes culturais cadastrados no Mapa da Cultura. Explore a diversidade de artistas, produtores, grupos, coletivos, bandas, instituições, que fazem parte da cultura! Participe e seja protagonista da cultura brasileira!')) ?></p>
                    <mc-link route="search/agents" class="button button--icon button--sm agent__color">
                        <?= i::__('Ver todos')?>
                        <mc-icon name="access"></mc-icon>
                    </mc-link>
                </div>
            </div>

            <div v-if="global.enabledEntities.projects" class="card">
                <div class="tag">
                    <div class="icon">
                        <i class="fas fa-project-diagram"></i> <!-- Ícone de projeto -->
                    </div>
                </div>
                <div class="right">
                    <div class="header">
                        <h3>Projetos</h3>
                        <a href="#">ver todos ></a>
                    </div>
                    <p><?= $this->text('projects', i::__('Aqui você encontra projetos culturais cadastrados pelos agentes culturais usuários da plataforma Mapa da Cultura.')) ?></p>
                    <mc-link route="search/projects" class="button button--icon button--sm project__color">
                        <?= i::__('Ver todos')?>
                        <mc-icon name="access"></mc-icon>
                    </mc-link>
                </div>
            </div>
        </div>
    </div>
</div>
