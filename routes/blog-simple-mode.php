<?php

declare(strict_types=1);

/*
 * KOVCHEG Blog 3.5.8 keeps legacy builders available in code for backward
 * compatibility, but removes them from the normal blog administration flow.
 */
$router->get('/studio/patterns', function () {
    \Kovcheg\Blog\Studio::require('content');
    redirect('/studio/content');
});

$router->get('/studio/presets', function () {
    \Kovcheg\Blog\Studio::require('site');
    redirect('/studio/settings');
});

$router->get('/studio/widgets', function () {
    \Kovcheg\Blog\Studio::require('site');
    redirect('/studio/appearance');
});

$router->get('/studio/modules', function () {
    \Kovcheg\Blog\Studio::require('site');
    redirect('/studio/settings');
});
