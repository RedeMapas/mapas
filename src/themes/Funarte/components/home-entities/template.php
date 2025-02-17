<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('mc-link');

$entities = [
    'opportunities' => [
        'icon' => 'opportunity',
        'title' => 'Oportunidades',
        'route' => 'search/opportunities',
        'description' => 'Aqui você pode fazer sua inscrição nos editais e oportunidades do Ministério da Cultura (Minc), bem como acompanhar as inscrições em andamento. Nesse espaço, você também pode acessar outras oportunidades da cultura; tais como, oficinas, prêmios e concursos; criar uma oportunidade e divulgá-la para outros agentes culturais.'
    ],
    'events' => [
        'icon' => 'event',
        'title' => 'Eventos',
        'route' => 'search/events',
        'description' => 'Você pode pesquisar eventos culturais cadastrados na plataforma filtrando por região, área da cultura, etc. Você também pode incluir seus eventos culturais na plataforma e divulgá-los gratuitamente.'
    ],
    'spaces' => [
        'icon' => 'space',
        'title' => 'Espaços',
        'route' => 'search/spaces',
        'description' => 'Aqui você pode cadastrar seus espaços culturais e colaborar com o Mapa da Cultura! Além disso, você pode pesquisar por espaços culturais cadastrados na sua região; tais como teatros, bibliotecas, centros culturais e outros.'
    ],
    'agents' => [
        'icon' => 'agent-2',
        'title' => 'Agentes',
        'route' => 'search/agents',
        'description' => 'Neste espaço, é possível buscar e conhecer os agentes culturais cadastrados no Mapa da Cultura. Explore a diversidade de artistas, produtores, grupos, coletivos, bandas, instituições, que fazem parte da cultura! Participe e seja protagonista da cultura brasileira!'
    ],
    'projects' => [
        'icon' => 'project',
        'title' => 'Projetos',
        'route' => 'search/projects',
        'description' => 'Aqui você encontra projetos culturais cadastrados pelos agentes culturais usuários da plataforma Mapa da Cultura.'
    ]
];

?>

<div class="home-entities">
    <div class="home-entities__content">
        <div class="home-entities__content--cards">
            <?php foreach ($entities as $key => $entity) : ?>
                <div v-if="global.enabledEntities.<?= $key ?>" class="card <?= $key ?>">
                    <div class="tag">
                        <div class="icon">
                            <mc-icon name="<?= $entity['icon'] ?>"></mc-icon>
                        </div>
                    </div>
                    <div class="right">
                        <div class="header">
                            <h3><?= $entity['title'] ?></h3>
                            <mc-link route="<?= $entity['route'] ?>">
                                <?= i::__('Ver todos') ?>
                                <mc-icon name="access"></mc-icon>
                            </mc-link>
                        </div>
                        <p><?= i::__($entity['description']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
