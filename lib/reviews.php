<?php
declare(strict_types=1);

const GOOGLE_REVIEWS_SHARE_URL = 'https://share.google/mMoHvEHkiu30qDoO8';

/**
 * Embed Trustindex / innego darmowego widgetu (HTML).
 * Kolejność: env GOOGLE_REVIEWS_EMBED → config google_reviews_embed → data/google-reviews.html
 */
function resolve_google_reviews_embed(?array $config = null): string
{
    $env = getenv('GOOGLE_REVIEWS_EMBED');
    if (is_string($env) && trim($env) !== '') {
        return sanitize_reviews_embed($env);
    }

    $config = $config ?? load_config();
    $fromConfig = trim((string) ($config['google_reviews_embed'] ?? ''));
    if ($fromConfig !== '') {
        return sanitize_reviews_embed($fromConfig);
    }

    $file = app_root() . '/data/google-reviews.html';
    if (is_file($file)) {
        $raw = file_get_contents($file);
        if (is_string($raw) && trim($raw) !== '') {
            return sanitize_reviews_embed($raw);
        }
    }

    return '';
}

function google_reviews_embed_configured(?array $config = null): bool
{
    return resolve_google_reviews_embed($config) !== '';
}

/**
 * Allowlista pod typowy embed Trustindex (div + script z cdn.trustindex.io).
 * Nie przepuszcza inline event handlerów ani obcych skryptów.
 */
function sanitize_reviews_embed(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $allowedHosts = [
        'cdn.trustindex.io',
        'www.trustindex.io',
        'trustindex.io',
    ];

    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $wrapped = '<?xml encoding="UTF-8"><div id="reviews-embed-root">' . $html . '</div>';
    $ok = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$ok) {
        return '';
    }

    $root = $dom->getElementById('reviews-embed-root');
    if (!$root) {
        return '';
    }

    sanitize_reviews_node($root, $allowedHosts);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $dom->saveHTML($child);
    }
    return trim($out);
}

/** @param list<string> $allowedHosts */
function sanitize_reviews_node(DOMNode $node, array $allowedHosts): void
{
    if (!($node instanceof DOMElement) && !($node instanceof DOMDocument)) {
        return;
    }

    $children = [];
    foreach ($node->childNodes as $child) {
        $children[] = $child;
    }

    $allowedTags = [
        'div' => ['id', 'class', 'data-src', 'data-widget', 'style'],
        'span' => ['id', 'class', 'style'],
        'a' => ['href', 'class', 'target', 'rel', 'title'],
        'p' => ['class'],
        'br' => [],
        'strong' => [],
        'em' => [],
        'img' => ['src', 'alt', 'width', 'height', 'class', 'loading'],
        'iframe' => ['src', 'title', 'width', 'height', 'loading', 'referrerpolicy', 'allowfullscreen', 'frameborder', 'class', 'style'],
        'script' => ['src', 'defer', 'async', 'type', 'charset', 'crossorigin'],
    ];

    foreach ($children as $child) {
        if ($child instanceof DOMText || $child instanceof DOMComment) {
            if ($child instanceof DOMComment) {
                $child->parentNode?->removeChild($child);
            }
            continue;
        }
        if (!($child instanceof DOMElement)) {
            $child->parentNode?->removeChild($child);
            continue;
        }

        $tag = strtolower($child->tagName);
        if (!isset($allowedTags[$tag])) {
            while ($child->firstChild) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
            continue;
        }

        $allowedAttrs = $allowedTags[$tag];
        $remove = [];
        foreach ($child->attributes ?? [] as $attr) {
            $name = strtolower($attr->name);
            $dataOk = str_starts_with($name, 'data-') && ($tag === 'div' || $tag === 'span');
            if (str_starts_with($name, 'on') || (!$dataOk && !in_array($name, $allowedAttrs, true))) {
                $remove[] = $attr->name;
            }
        }
        foreach ($remove as $name) {
            $child->removeAttribute($name);
        }

        if ($tag === 'script') {
            $src = trim((string) $child->getAttribute('src'));
            if ($src === '' || !reviews_url_host_allowed($src, $allowedHosts)) {
                $child->parentNode?->removeChild($child);
                continue;
            }
            // Usuń treść inline w skrypcie zewnętrznym
            while ($child->firstChild) {
                $child->removeChild($child->firstChild);
            }
            $child->setAttribute('defer', 'defer');
        }

        if ($tag === 'iframe') {
            $src = trim((string) $child->getAttribute('src'));
            if ($src === '' || !reviews_url_host_allowed($src, array_merge($allowedHosts, ['www.google.com', 'maps.google.com']))) {
                $child->parentNode?->removeChild($child);
                continue;
            }
            $child->setAttribute('loading', 'lazy');
            $child->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        }

        if ($tag === 'a') {
            $href = trim((string) $child->getAttribute('href'));
            if ($href === '' || preg_match('#^\s*(javascript|data|vbscript):#i', $href)) {
                $child->removeAttribute('href');
            } else {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
        }

        if ($tag === 'img') {
            $src = trim((string) $child->getAttribute('src'));
            if ($src !== '' && str_starts_with($src, 'http') && !reviews_url_host_allowed($src, array_merge($allowedHosts, [
                'lh3.googleusercontent.com',
                'streetviewpixels-pa.googleapis.com',
                'www.google.com',
                'maps.gstatic.com',
            ]))) {
                $child->parentNode?->removeChild($child);
                continue;
            }
        }

        sanitize_reviews_node($child, $allowedHosts);
    }
}

/** @param list<string> $allowedHosts */
function reviews_url_host_allowed(string $url, array $allowedHosts): bool
{
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        return false;
    }
    $host = strtolower((string) $parts['host']);
    foreach ($allowedHosts as $allowed) {
        $allowed = strtolower($allowed);
        if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
            return true;
        }
    }
    return false;
}
