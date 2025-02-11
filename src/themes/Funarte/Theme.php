<?php

namespace MapasCulturais\Themes\Funarte;

use MapasCulturais\App;

define('SAAS_PATH', realpath(BASE_PATH . '../Funarte'));

class Theme extends \MapasCulturais\Themes\BaseV2\Theme
{

    static function getThemeFolder()
    {
        return __DIR__;
    }

    function _init()
    {
        parent::_init();

        $app = App::i();

    }
}




