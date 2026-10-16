<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class DescriptionSanitizer
{
    private const ALLOWED = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a'];
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
                while ($node->attributes->length) {
                    $node->removeAttributeNode($node->attributes->item(0));
                }
                if ($tag === 'a' && preg_match('~^(https?://|mailto:)~i', $href)) {
                    $node->setAttribute('href', $href);
                    $node->setAttribute('rel', 'nofollow noopener noreferrer');
                }
            } elseif ($node->nodeType !== XML_TEXT_NODE) {
                $parent->removeChild($node);
            }
        }
    }
}
