<?php 
use \MapasCulturais\i;

return [

    /* 
    Define o nome do asset da imagem da logo do site - Substituirá a logo padrão

    ex: `img/meu-mapa-logo.jpg` (pasta assets/img/meu-mapa-logo.jpg do tema) 
    */
    'logo.image' => 'img/marca-rede-das-artes.svg',

    /* 
    Define o nome do asset da imagem do background e banner no header da home - Substituirá o background padrão
    ex: `img/meu-home-header-background.jpg` (pasta assets/img/meu-home-header-background.jpg do tema)
    */
    'homeHeader.background' => 'img/banner.png',

    /*
    Define as configurações de ícones de redes sociais do componente main-footer.
    */
    'social-media' => [
        'facebook-icon' => [
            'title' => 'facebook',
            'link' => 'https://www.facebook.com/funarte'
        ],
        'twitter-icon' => [
            'title' => 'twitter',
            'link' => 'https://twitter.com/Funarte'
        ],
        'instagram-icon' => [
            'title' => 'instagram',
            'link' => 'https://www.instagram.com/funarte/'
        ],
        'youtube-icon' => [
            'title' => 'youtube',
            'link' => 'https://www.youtube.com/funarte'
        ]
    ]

];