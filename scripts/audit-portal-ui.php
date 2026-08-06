<?php

declare(strict_types=1);

/*
 * KOVCHEG Blog 3.5.5 — Portal UI static audit.
 * Author and copyright: Ланцет Семён Борисович.
 * License: proprietary / all rights reserved.
 */

$root = dirname(__DIR__);
$layoutPath = $root.'/themes/kovcheg-portal/layout.php';
$cssPath = $root.'/themes/kovcheg-portal/assets/portal-ui-repair.css';
$bootstrapPath = $root.'/app/bootstrap.php';

$failures = [];

$read = static function (string $path) use (&$failures): string {
    if (!is_file($path)) {
        $failures[] = 'Missing file: '.$path;
        return '';
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $failures[] = 'Cannot read file: '.$path;
        return '';
    }

    return $content;
};

$layout = $read($layoutPath);
$css = $read($cssPath);
$bootstrap = $read($bootstrapPath);

$widgetCssPosition = strpos($layout, 'blog-widgets.css');
$repairCssPosition = strpos($layout, 'portal-ui-repair.css');
if ($repairCssPosition === false) {
    $failures[] = 'Portal repair stylesheet is not loaded by the theme layout.';
} elseif ($widgetCssPosition === false || $repairCssPosition < $widgetCssPosition) {
    $failures[] = 'Portal repair stylesheet must be loaded after the shared Widget Engine stylesheet.';
}

foreach ([
    '.site-brand__logo',
    '.site-account__profile img',
    '.portal-matrix-sidebar-grid',
    'grid-auto-rows: max-content',
    '.portal-matrix-footer .widget-subscription',
    '@media (max-width: 900px)',
] as $requiredToken) {
    if (!str_contains($css, $requiredToken)) {
        $failures[] = 'Missing required Portal UI rule: '.$requiredToken;
    }
}

if (substr_count($css, '{') !== substr_count($css, '}')) {
    $failures[] = 'Portal repair stylesheet has unbalanced braces.';
}

if (!str_contains($bootstrap, "const APP_VERSION = '3.5.5';")) {
    $failures[] = 'APP_VERSION is not 3.5.5.';
}

if (!str_contains($bootstrap, "const ASSET_REVISION = '3.5.5-portal-ui-repair';")) {
    $failures[] = 'ASSET_REVISION does not invalidate cached Portal assets.';
}

if ($failures !== []) {
    fwrite(STDERR, "Portal UI audit failed:\n- ".implode("\n- ", $failures)."\n");
    exit(1);
}

echo "Portal UI audit OK\n";
