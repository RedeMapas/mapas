<?php

namespace MapasCulturais\Themes\Funarte;

use MapasCulturais\App;

class Theme extends \MapasCulturais\Themes\BaseV2\Theme
{

    static function getThemeFolder() {

        return __DIR__;

    }

    function _init() {

        parent::_init();

        $this->enqueueStyle('app-v2', 'main', 'css/theme-Funarte.css');

    }
}
