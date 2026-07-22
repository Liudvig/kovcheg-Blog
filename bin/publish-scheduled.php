<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/BlogGrowth.php';

$count = \Kovcheg\Blog\Growth::publishScheduled();
echo "Published scheduled entries: {$count}\n";
