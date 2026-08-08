<?php
\Kovcheg\Hooks::on('blog.layout.head',static function($html){
    return (string)$html.'<link rel="stylesheet" href="'.e(app_url('/themes/kovcheg-portfolio/assets/bright-3.8.2.css?v='.rawurlencode(ASSET_REVISION))).'">';
});
require BASE_PATH.'/themes/kovcheg-editorial/layout.php';
