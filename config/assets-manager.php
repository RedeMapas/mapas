<?php
$app_mode = env('APP_MODE', 'production');
$is_production = $app_mode == 'production';

return [
    'themes.assetManager' => [
        'publishPath' => BASE_PATH . 'assets/',

        // In production assets are already minified by the Node.js build stage
        // (pnpm run build + sass), so we just copy them as-is at runtime.
        // terser/uglifycss are not available in the production image.
        'mergeScripts' =>  env('ASSETS_MERGE_SCRIPTS', $is_production),
        'mergeStyles' => env('ASSETS_MERGE_STYLES', $is_production),

        'process.js' => 'cp {IN} {OUT}',

        'process.css' => 'cp {IN} {OUT}',

        'publishFolderCommand' => 'cp -R {IN} {PUBLISH_PATH}{FILENAME}'
    ],
];