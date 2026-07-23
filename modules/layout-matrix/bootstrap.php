<?php

declare(strict_types=1);

namespace Kovcheg\LayoutMatrix;

use Kovcheg\Blog\Layout;

final class Matrix
{
    public static function boot(): void
    {
        Layout::registerZone('matrix.preheader', ['label'=>'Область над шапкой','width'=>'wide']);

        for ($slot=1; $slot<=5; $slot++) {
            Layout::registerZone('matrix.header.'.$slot, ['label'=>'Шапка · секция '.$slot,'width'=>'header-slot']);
        }

        Layout::registerZone('matrix.postheader', ['label'=>'Область под шапкой','width'=>'wide']);
        Layout::registerZone('matrix.banner.top', ['label'=>'Верхняя полоса для баннера или бегущей строки','width'=>'wide']);

        for ($slot=1; $slot<=4; $slot++) {
            Layout::registerZone('matrix.left.'.$slot, ['label'=>'Левая колонка · блок '.$slot,'width'=>'sidebar']);
        }

        for ($slot=1; $slot<=12; $slot++) {
            Layout::registerZone('matrix.center.'.$slot, ['label'=>'Центральная область · блок '.$slot,'width'=>'content-cell']);
        }

        Layout::registerZone('matrix.content.main', ['label'=>'Основное содержимое страницы','width'=>'content','reserved'=>true]);

        for ($slot=1; $slot<=4; $slot++) {
            Layout::registerZone('matrix.right.'.$slot, ['label'=>'Правая колонка · блок '.$slot,'width'=>'sidebar']);
        }

        Layout::registerZone('matrix.banner.bottom', ['label'=>'Нижняя полоса для баннера или бегущей строки','width'=>'wide']);

        for ($slot=1; $slot<=8; $slot++) {
            Layout::registerZone('matrix.footer.'.$slot, ['label'=>'Подвал · блок '.$slot,'width'=>'footer-cell']);
        }

        Layout::registerZone('matrix.copyright', ['label'=>'Копирайт','width'=>'wide','reserved'=>true]);
    }
}

Matrix::boot();