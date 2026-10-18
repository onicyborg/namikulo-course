<?php

namespace App\Services;

class DiscussionContent
{
    public function __construct(private readonly DescriptionSanitizer $sanitizer) {}

    public function html(string $body, string $format = 'plain'): string
    {
        return $format === 'html' ? ($this->sanitizer->clean($body) ?? '') : nl2br(e($body));
    }

    public function text(string $body, string $format = 'plain'): string
    {
        if ($format === 'html') {
            $body = preg_replace('~</(?:p|div|li|h[2-4]|blockquote|pre)>|<br\s*/?>~i', ' ', $this->html($body, $format));
            $body = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return trim(preg_replace('/[\s\x{00a0}\x{200b}\x{feff}]+/u', ' ', $body));
    }
}
