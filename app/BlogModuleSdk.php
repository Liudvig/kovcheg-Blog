<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

require_once __DIR__.'/BlogModules.php';

/**
 * Compatibility facade for older integrations.
 *
 * KOVCHEG CMS 3.7.0 does not load the module SDK during ordinary page
 * requests. The facade remains available for legacy tooling and health checks.
 */
final class ModuleSdk
{
    public static function installed(): array
    {
        return ModuleManager::installed();
    }
}
