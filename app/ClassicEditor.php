<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use Throwable;

final class ClassicEditor
{
    private const MAX_HTML_BYTES = 2_500_000;

    private const ALLOWED_TAGS = [
        'p','br','h2','h3','h4','strong','b','em','i','u','s','del','ul','ol','li',
        'blockquote','a','img','figure','figcaption','hr','pre','code','table','thead',
        'tbody','tr','th','td','div','span','sup','sub',
    ];

    private const DROP_CONTENT_TAGS = [
        'script','style','iframe','object','embed','form','input','button','textarea','select',
        'option','link','meta','base','svg','math','canvas','video','audio','source',
    ];

    public static function isClassicPayload(string $json): bool
    {
        try {
            $blocks = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return false;
        }

        return is_array($blocks)
            && isset($blocks[0])
            && is_array($blocks[0])
            && (string)($blocks[0]['type'] ?? '') === 'classic';
    }

    public static function normalizePayload(string $json): string
    {
        if (strlen($json) > self::MAX_HTML_BYTES + 100_000) {
            abort(413, 'Материал превышает допустимый размер классического редактора.');
        }

        try {
            $blocks = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            abort(422, 'Классический редактор передал повреждённые данные.');
        }

        $html = '';
        if (is_array($blocks) && isset($blocks[0]) && is_array($blocks[0])) {
            $data = is_array($blocks[0]['data'] ?? null) ? $blocks[0]['data'] : [];
            $html = (string)($data['html'] ?? '');
        }

        $normalized = [[
            'id' => preg_match('/^[a-zA-Z0-9_-]{3,80}$/', (string)($blocks[0]['id'] ?? ''))
                ? (string)$blocks[0]['id']
                : 'classic-'.bin2hex(random_bytes(6)),
            'type' => 'classic',
            'data' => ['html' => self::sanitize($html)],
        ]];

        return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public static function renderPayload(string $json): string
    {
        try {
            $blocks = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return '';
        }

        if (!is_array($blocks) || !isset($blocks[0]) || !is_array($blocks[0])) return '';
        $data = is_array($blocks[0]['data'] ?? null) ? $blocks[0]['data'] : [];
        return self::sanitize((string)($data['html'] ?? ''));
    }

    public static function sanitize(string $html): string
    {
        $html = str_replace("\0", '', $html);
        if (strlen($html) > self::MAX_HTML_BYTES) {
            abort(413, 'Текст материала слишком большой.');
        }
        if (trim($html) === '') return '';

        if (!class_exists(DOMDocument::class)) {
            return self::fallbackSanitize($html);
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument('1.0', 'UTF-8');
            $wrapped = '<!doctype html><html><body><div id="kovcheg-classic-root">'.$html.'</div></body></html>';
            $loaded = $document->loadHTML('<?xml encoding="UTF-8">'.$wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            if (!$loaded) return self::fallbackSanitize($html);

            $root = $document->getElementById('kovcheg-classic-root');
            if (!$root) return self::fallbackSanitize($html);

            self::cleanChildren($root);
            return trim(self::innerHtml($root));
        } catch (Throwable) {
            return self::fallbackSanitize($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        for ($node = $parent->firstChild; $node !== null;) {
            $next = $node->nextSibling;

            if ($node instanceof DOMComment) {
                $parent->removeChild($node);
                $node = $next;
                continue;
            }

            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);

                if (in_array($tag, self::DROP_CONTENT_TAGS, true)) {
                    $parent->removeChild($node);
                    $node = $next;
                    continue;
                }

                if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                    while ($node->firstChild) $parent->insertBefore($node->firstChild, $node);
                    $parent->removeChild($node);
                    $node = $next;
                    continue;
                }

                self::cleanElement($node, $tag);
                self::cleanChildren($node);
            }

            $node = $next;
        }
    }

    private static function cleanElement(DOMElement $element, string $tag): void
    {
        $original = [];
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            $attributes[] = $attribute->name;
            $original[strtolower($attribute->name)] = (string)$attribute->value;
        }

        $style = strtolower((string)($original['style'] ?? ''));
        $alignment = '';
        if (preg_match('/text-align\s*:\s*(left|center|right|justify)/', $style, $match)) {
            $alignment = 'text-align-'.$match[1];
        }

        foreach ($attributes as $attribute) $element->removeAttribute($attribute);

        if ($alignment !== '' && in_array($tag, ['p','h2','h3','h4','div','blockquote','figure'], true)) {
            $element->setAttribute('class', $alignment);
        }

        if ($tag === 'a') {
            $href = self::safeUrl((string)($_SERVER['HTTP_HOST'] ?? ''), (string)($original['href'] ?? ''));
            if ($href !== '') $element->setAttribute('href', $href);
            $title = self::cleanTextAttribute((string)($original['title'] ?? ''), 300);
            if ($title !== '') $element->setAttribute('title', $title);
            $target = (string)($original['target'] ?? '') === '_blank' ? '_blank' : '';
            if ($target !== '') {
                $element->setAttribute('target', '_blank');
                $element->setAttribute('rel', 'noopener noreferrer');
            }
        } elseif ($tag === 'img') {
            $src = self::safeImageUrl((string)($original['src'] ?? ''));
            if ($src === '') {
                $element->parentNode?->removeChild($element);
                return;
            }
            $element->setAttribute('src', $src);
            $element->setAttribute('alt', self::cleanTextAttribute((string)($original['alt'] ?? ''), 500));
            $title = self::cleanTextAttribute((string)($original['title'] ?? ''), 300);
            if ($title !== '') $element->setAttribute('title', $title);
            foreach (['width','height'] as $dimension) {
                $value = (int)($original[$dimension] ?? 0);
                if ($value > 0 && $value <= 5000) $element->setAttribute($dimension, (string)$value);
            }
            $element->setAttribute('loading', 'lazy');
            $element->setAttribute('decoding', 'async');
        } elseif (in_array($tag, ['th','td'], true)) {
            foreach (['colspan','rowspan'] as $span) {
                $value = (int)($original[$span] ?? 0);
                if ($value > 1 && $value <= 20) $element->setAttribute($span, (string)$value);
            }
        }
    }

    private static function innerHtml(DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) $html .= $element->ownerDocument?->saveHTML($child) ?: '';
        return $html;
    }

    private static function safeUrl(string $host, string $url): string
    {
        $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) return '';
        if (str_starts_with($url, '#')) return preg_match('/^#[a-zA-Z0-9_-]{1,100}$/', $url) ? $url : '';
        if (str_starts_with($url, '/')) return preg_match('~^/[a-zA-Z0-9_./?=&%#:+~-]*$~', $url) ? $url : '';
        if (!filter_var($url, FILTER_VALIDATE_URL)) return '';
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http','https','mailto','tel'], true) ? $url : '';
    }

    private static function safeImageUrl(string $url): string
    {
        $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (str_starts_with($url, '/')) return preg_match('~^/[a-zA-Z0-9_./?=&%#:+~-]*$~', $url) ? $url : '';
        if (!filter_var($url, FILTER_VALIDATE_URL)) return '';
        return in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http','https'], true) ? $url : '';
    }

    private static function cleanTextAttribute(string $value, int $max): string
    {
        return mb_substr(trim(strip_tags($value)), 0, $max);
    }

    private static function fallbackSanitize(string $html): string
    {
        $allowed = '<'.implode('><', self::ALLOWED_TAGS).'>';
        $html = strip_tags($html, $allowed);
        $html = preg_replace('/\s(?:on[a-z]+|style|srcdoc)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? '';
        $html = preg_replace('/\s(?:href|src)\s*=\s*(["\'])\s*(?:javascript|data|vbscript):.*?\1/iu', '', $html) ?? '';
        $html = preg_replace('/<(script|style|iframe|object|embed|form)\b[^>]*>.*?<\/\1>/isu', '', $html) ?? '';
        return trim($html);
    }
}
