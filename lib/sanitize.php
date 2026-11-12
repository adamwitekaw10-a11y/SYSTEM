<?php
declare(strict_types=1);

/**
 * Sanityzacja HTML z edytora oferty (bez Composer).
 * Dozwolone: akapity, nagłówki, listy, linki, tabele, pogrubienie/kursywa.
 */
function sanitize_offer_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $allowed = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'a' => ['href', 'title', 'rel', 'target'],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tfoot' => [],
        'tr' => [],
        'th' => ['scope', 'colspan', 'rowspan'],
        'td' => ['colspan', 'rowspan'],
        'blockquote' => [],
        'div' => ['class'],
        'span' => [],
    ];

    $wrapped = '<?xml encoding="UTF-8"><div id="sanitize-root">' . $html . '</div>';
    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $loaded = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$loaded) {
        return '';
    }

    $root = $dom->getElementById('sanitize-root');
    if (!$root) {
        return '';
    }

    sanitize_dom_node($root, $allowed);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $dom->saveHTML($child);
    }
    return trim($out);
}

/** @param array<string, list<string>> $allowed */
function sanitize_dom_node(DOMNode $node, array $allowed): void
{
    if (!($node instanceof DOMElement) && !($node instanceof DOMDocument)) {
        return;
    }

    $children = [];
    foreach ($node->childNodes as $child) {
        $children[] = $child;
    }

    foreach ($children as $child) {
        if ($child instanceof DOMText) {
            continue;
        }
        if ($child instanceof DOMComment) {
            $child->parentNode?->removeChild($child);
            continue;
        }
        if (!($child instanceof DOMElement)) {
            $child->parentNode?->removeChild($child);
            continue;
        }

        $tag = strtolower($child->tagName);
        if (!isset($allowed[$tag])) {
            // Unwrap unknown tags: keep children
            while ($child->firstChild) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
            continue;
        }

        $allowedAttrs = $allowed[$tag];
        $removeAttrs = [];
        foreach ($child->attributes ?? [] as $attr) {
            $name = strtolower($attr->name);
            if (str_starts_with($name, 'on') || !in_array($name, $allowedAttrs, true)) {
                $removeAttrs[] = $attr->name;
            }
        }
        foreach ($removeAttrs as $name) {
            $child->removeAttribute($name);
        }

        if ($tag === 'a') {
            $href = trim((string) $child->getAttribute('href'));
            if ($href === '' || preg_match('#^\s*(javascript|data|vbscript):#i', $href)) {
                $child->removeAttribute('href');
            } else {
                $child->setAttribute('href', $href);
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            $target = (string) $child->getAttribute('target');
            if ($target !== '' && $target !== '_blank' && $target !== '_self') {
                $child->removeAttribute('target');
            }
        }

        if ($tag === 'div') {
            $class = (string) $child->getAttribute('class');
            if ($class !== 'table-scroll' && $class !== 'note') {
                $child->removeAttribute('class');
            }
        }

        sanitize_dom_node($child, $allowed);
    }
}
