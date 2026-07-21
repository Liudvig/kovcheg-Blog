<?php

declare(strict_types=1);

use Kovcheg\Hooks;

Hooks::on('layout.head',static function(mixed $html): string{
    $version=rawurlencode(ASSET_REVISION);
    return (string)$html
        ."\n<link rel=\"stylesheet\" href=\"".e(app_url('/assets/css/modern-upload.css?v='.$version))."\">"
        ."\n<link rel=\"stylesheet\" href=\"".e(app_url('/assets/css/template-polish.css?v='.$version))."\">"
        ."\n<link rel=\"stylesheet\" href=\"".e(app_url('/assets/css/layout-repair.css?v='.$version))."\">"
        ."\n<link rel=\"stylesheet\" href=\"".e(app_url('/assets/css/vk-structural-fix.css?v='.$version))."\">";
});

Hooks::on('layout.scripts',static function(mixed $html): string{
    $version=rawurlencode(ASSET_REVISION);
    return (string)$html
        ."\n<script src=\"".e(app_url('/assets/js/modern-upload.js?v='.$version))."\" defer></script>"
        ."\n<script src=\"".e(app_url('/assets/js/layout-repair.js?v='.$version))."\" defer></script>"
        ."\n<script src=\"".e(app_url('/assets/js/vk-structural-fix.js?v='.$version))."\" defer></script>";
});
