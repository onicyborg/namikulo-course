<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class DescriptionSanitizer
{
    private const ALLOWED = ['p', 'div', 'span', 'font', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'pre', 'code', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'img'];
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form', 'input', 'button', 'video', 'audio'];

    public function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $root = $document->getElementsByTagName('div')->item(0);
        if (! $root) {
            return null;
        }
        $this->sanitizeChildren($root);
        $clean = '';
        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }
        return trim($clean) ?: null;
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if (in_array($tag, self::DROP, true)) {
                    $parent->removeChild($node);
                    continue;
                }
                $this->sanitizeChildren($node);
                if (! in_array($tag, self::ALLOWED, true)) {
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                    continue;
                }
                $href = $tag === 'a' ? trim($node->getAttribute('href')) : '';
                $src = $tag === 'img' ? trim($node->getAttribute('src')) : '';
                $alt = $tag === 'img' ? trim($node->getAttribute('alt')) : '';
                if ($tag === 'img' && ! preg_match('~^/editor/images/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$~i', $src)) {
                    $parent->removeChild($node);
                    continue;
                }
                $opensNewWindow = $tag === 'a' && strtolower(trim($node->getAttribute('target'))) === '_blank';
                $style = $this->safeStyle($node->getAttribute('style'));
                if ($tag === 'font' && preg_match('/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', $node->getAttribute('color'))) {
                    $style .= 'color:'.$node->getAttribute('color').';';
                }
                while ($node->attributes->length) {
                    $node->removeAttributeNode($node->attributes->item(0));
                }
                if ($style !== '') $node->setAttribute('style', $style);
                if ($tag === 'a' && preg_match('~^(https?://|mailto:)~i', $href)) {
                    $node->setAttribute('href', $href);
                    $node->setAttribute('rel', 'nofollow noopener noreferrer');
                    if ($opensNewWindow) $node->setAttribute('target', '_blank');
                }
                if ($tag === 'img') {
                    $node->setAttribute('src', $src);
                    $node->setAttribute('alt', mb_substr($alt, 0, 160));
                    $node->setAttribute('loading', 'lazy');
                    $node->setAttribute('decoding', 'async');
                }
            } elseif ($node->nodeType !== XML_TEXT_NODE) {
                $parent->removeChild($node);
            }
        }
    }

    private function safeStyle(string $style): string
    {
        $safe = [];
        foreach (explode(';', $style) as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) !== 2) continue;
            [$property, $value] = array_map('trim', $parts);
            $property = strtolower($property);
            $value = strtolower($value);
            $valid = match ($property) {
                'color', 'background-color' => (bool) preg_match('/^(#[0-9a-f]{3}(?:[0-9a-f]{3})?|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)|black|white|red|green|blue|yellow|gray|grey|teal|transparent)$/', $value),
                'text-align' => in_array($value, ['left', 'right', 'center', 'justify'], true),
                'font-weight' => in_array($value, ['normal', 'bold', '400', '500', '600', '700', '800'], true),
                'font-style' => in_array($value, ['normal', 'italic'], true),
                'text-decoration', 'text-decoration-line' => in_array($value, ['none', 'underline', 'line-through'], true),
                'font-size' => (bool) preg_match('/^(1[2-9]|[23][0-9]|40)px$/', $value),
                'font-family' => in_array($value, ['arial', 'verdana', 'georgia', 'sans-serif', 'serif', 'monospace', 'system-ui'], true),
                default => false,
            };
            if ($valid) $safe[$property] = $value;
        }
        return implode('', array_map(fn ($property, $value) => $property.':'.$value.';', array_keys($safe), $safe));
    }
}
