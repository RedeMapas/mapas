<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */
?>
<section v-if="isActive || cached" v-show="isActive" :aria-hidden="!isActive" :aria-labelledby="aba" class="tab-component" :class="slug" role="tabpanel">
    <slot></slot>
</section>
