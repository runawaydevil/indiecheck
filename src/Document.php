<?php

namespace Indiechecker;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class Document
{
    public readonly DOMDocument $dom;
    public readonly DOMXPath $xpath;

    public function __construct(string $html, public readonly ?string $url, ?string $declaredCharset = null)
    {
        $charset = strtoupper($declaredCharset ?? self::sniffCharset($html) ?? 'UTF-8');
        if ($charset !== 'UTF-8' && in_array($charset, array_map('strtoupper', mb_list_encodings()), true)) {
            $html = mb_convert_encoding($html, 'UTF-8', $charset);
        }

        $previous = libxml_use_internal_errors(true);
        $this->dom = new DOMDocument();
        $this->dom->loadHTML(
            mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8'),
            LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->xpath = new DOMXPath($this->dom);
    }

    public function first(string $expression): ?DOMElement
    {
        $node = $this->xpath->query($expression)->item(0);

        return $node instanceof DOMElement ? $node : null;
    }

    public function all(string $expression): array
    {
        return array_filter(iterator_to_array($this->xpath->query($expression)), fn ($node) => $node instanceof DOMElement);
    }

    public function resolve(string $href): string
    {
        $base = $this->first('//base[@href]')?->getAttribute('href');
        $base = $base !== null && $this->url !== null ? \Mf2\resolveUrl($this->url, $base) : ($base ?? $this->url);

        return $base === null ? $href : \Mf2\resolveUrl($base, trim($href));
    }

    public function snippet(DOMElement $element, int $length = 160): string
    {
        $markup = $this->dom->saveHTML($element);
        $opening = strstr($markup, '>', true);

        return shorten(($opening === false ? $markup : $opening . '>'), $length);
    }

    private static function sniffCharset(string $html): ?string
    {
        $head = substr($html, 0, 2048);
        if (preg_match('/<meta[^>]+charset\s*=\s*["\']?([\w.:-]+)/i', $head, $match)) {
            return $match[1];
        }

        return null;
    }
}
