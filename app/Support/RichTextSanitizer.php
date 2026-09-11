<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class RichTextSanitizer
{
    public static function sanitize(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<!doctype html><html><body><div id="rich-text-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $root = $document->getElementById('rich-text-root');
        if (! $root) {
            return '';
        }

        $sanitized = '';
        foreach ($root->childNodes as $node) {
            $sanitized .= self::sanitizeNode($node);
        }

        return trim($sanitized);
    }

    private static function sanitizeNode(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return htmlspecialchars($node->wholeText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
            return '';
        }

        $content = '';
        foreach ($node->childNodes as $child) {
            $content .= self::sanitizeNode($child);
        }

        if (! in_array($tag, ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'a'], true)) {
            return $content;
        }

        if ($tag === 'br') {
            return '<br>';
        }

        if ($tag === 'a') {
            $href = trim((string) $node->getAttribute('href'));
            $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));

            return $href !== '' && in_array($scheme, ['http', 'https', 'mailto'], true)
                ? '<a href="'.htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" rel="noopener noreferrer">'.$content.'</a>'
                : $content;
        }

        return '<'.$tag.'>'.$content.'</'.$tag.'>';
    }
}
