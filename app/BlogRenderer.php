<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

require_once __DIR__.'/BlogBuilder.php';

/**
 * Legacy compatibility facade.
 *
 * The active Pages editor does not load the old builder or this class during
 * normal requests. Older modules that still reference Renderer can continue
 * to render their saved block JSON without breaking the lightweight runtime.
 */
final class Renderer
{
    public static function normalize(string $json): string
    {
        return Builder::normalize($json);
    }

    public static function render(string $json): string
    {
        return Builder::render($json);
    }
}
