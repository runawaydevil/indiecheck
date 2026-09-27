<?php

namespace Indiechecker;

final class Metadata
{
    private const FEED_TYPES = [
        'application/rss+xml' => 'RSS',
        'application/atom+xml' => 'Atom',
        'application/feed+json' => 'JSON Feed',
        'application/json' => 'JSON Feed',
        'application/rdf+xml' => 'RDF',
        'text/xml' => 'XML',
        'application/xml' => 'XML',
    ];

    public readonly ?string $title;
    public readonly ?string $description;
    public readonly ?string $lang;
    public readonly ?string $charset;
    public readonly ?string $viewport;
    public readonly ?string $canonical;
    public readonly ?string $generator;
    public readonly ?string $robots;
    public readonly array $themeColors;
    public readonly array $openGraph;
    public readonly array $twitter;
    public readonly array $fediverseCreators;
    public readonly array $feeds;
    public readonly array $icons;
    public readonly ?string $manifest;

    public function __construct(private Document $document)
    {
        $title = $document->first('//head/title') ?? $document->first('//title');
        $this->title = $title ? shorten($title->textContent, 500) : null;
        $this->description = $this->meta('description');
        $this->lang = $document->first('//html[@lang]')?->getAttribute('lang');
        $this->charset = $document->first('//meta[@charset]')?->getAttribute('charset')
            ?? $this->httpEquivCharset();
        $this->viewport = $this->meta('viewport');
        $canonical = $document->first('//link[@rel and @href][contains(concat(" ", normalize-space(@rel), " "), " canonical ")]');
        $this->canonical = $canonical ? $document->resolve($canonical->getAttribute('href')) : null;
        $this->generator = $this->meta('generator');
        $this->robots = $this->meta('robots');

        $themeColors = [];
        foreach ($document->all('//meta[translate(@name, "THEMCOLR", "themcolr")="theme-color"][@content]') as $meta) {
            $themeColors[] = ['color' => trim($meta->getAttribute('content')), 'media' => $meta->getAttribute('media') ?: null];
        }
        $this->themeColors = $themeColors;

        $this->openGraph = $this->prefixed('og:', 'property');
        $this->twitter = $this->prefixed('twitter:', 'name') + $this->prefixed('twitter:', 'property');
        $this->fediverseCreators = array_map(
            fn (\DOMElement $meta) => trim($meta->getAttribute('content')),
            $document->all('//meta[@name="fediverse:creator"][@content]'),
        );

        $this->feeds = $this->findFeeds();
        $this->icons = $this->findIcons();
        $manifest = $document->first('//link[@href][contains(concat(" ", normalize-space(translate(@rel, "MANIFEST", "manifest")), " "), " manifest ")]');
        $this->manifest = $manifest ? $document->resolve($manifest->getAttribute('href')) : null;
    }

    public function openGraph(string $property): ?string
    {
        return $this->openGraph[$property][0] ?? null;
    }

    public function twitter(string $name): ?string
    {
        return $this->twitter[$name][0] ?? null;
    }

    public function preview(): array
    {
        $image = $this->openGraph('og:image') ?? $this->openGraph('og:image:url') ?? $this->twitter('twitter:image');

        return [
            'title' => $this->openGraph('og:title') ?? $this->twitter('twitter:title') ?? $this->title,
            'description' => $this->openGraph('og:description') ?? $this->twitter('twitter:description') ?? $this->description,
            'image' => $image !== null ? $this->document->resolve($image) : null,
            'imageAlt' => $this->openGraph('og:image:alt') ?? $this->twitter('twitter:image:alt'),
            'site' => $this->openGraph('og:site_name'),
            'host' => $this->document->url ? parse_url($this->canonical ?? $this->document->url, PHP_URL_HOST) : null,
        ];
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'lang' => $this->lang,
            'charset' => $this->charset,
            'viewport' => $this->viewport,
            'canonical' => $this->canonical,
            'generator' => $this->generator,
            'robots' => $this->robots,
            'theme-color' => $this->themeColors,
            'open-graph' => $this->openGraph,
            'twitter' => $this->twitter,
            'fediverse:creator' => $this->fediverseCreators,
            'feeds' => $this->feeds,
            'icons' => $this->icons,
            'manifest' => $this->manifest,
        ];
    }

    private function meta(string $name): ?string
    {
        $meta = $this->document->first(sprintf(
            '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="%s"][@content]',
            $name,
        ));

        return $meta ? trim($meta->getAttribute('content')) : null;
    }

    private function httpEquivCharset(): ?string
    {
        $meta = $this->document->first('//meta[translate(@http-equiv, "CONTENT-TYPE", "content-type")="content-type"][@content]');
        if ($meta && preg_match('/charset\s*=\s*([\w.:-]+)/i', $meta->getAttribute('content'), $match)) {
            return $match[1];
        }

        return null;
    }

    private function prefixed(string $prefix, string $attribute): array
    {
        $values = [];
        foreach ($this->document->all("//meta[starts-with(@{$attribute}, '{$prefix}')][@content]") as $meta) {
            $values[$meta->getAttribute($attribute)][] = trim($meta->getAttribute('content'));
        }

        return $values;
    }

    private function findFeeds(): array
    {
        $feeds = [];
        $links = $this->document->all('//link[@href][contains(concat(" ", normalize-space(translate(@rel, "ALTERNATE", "alternate")), " "), " alternate ")]');
        foreach ($links as $link) {
            $type = strtolower(trim($link->getAttribute('type')));
            if (!isset(self::FEED_TYPES[$type])) {
                continue;
            }
            $feeds[] = [
                'url' => $this->document->resolve($link->getAttribute('href')),
                'format' => self::FEED_TYPES[$type],
                'type' => $type,
                'title' => $link->getAttribute('title') ?: null,
            ];
        }

        foreach ($this->document->all('//link[@href][contains(concat(" ", normalize-space(@rel), " "), " feed ")]') as $link) {
            $feeds[] = [
                'url' => $this->document->resolve($link->getAttribute('href')),
                'format' => 'h-feed (rel=feed)',
                'type' => 'text/html',
                'title' => $link->getAttribute('title') ?: null,
            ];
        }

        return $feeds;
    }

    private function findIcons(): array
    {
        $icons = [];
        foreach ($this->document->all('//link[@rel and @href]') as $link) {
            $rels = class_tokens(strtolower($link->getAttribute('rel')));
            $kind = match (true) {
                in_array('apple-touch-icon', $rels, true), in_array('apple-touch-icon-precomposed', $rels, true) => 'apple-touch-icon',
                in_array('mask-icon', $rels, true) => 'mask-icon',
                in_array('icon', $rels, true) => 'icon',
                default => null,
            };
            if ($kind === null) {
                continue;
            }
            $icons[] = [
                'kind' => $kind,
                'url' => $this->document->resolve($link->getAttribute('href')),
                'sizes' => $link->getAttribute('sizes') ?: null,
                'type' => $link->getAttribute('type') ?: null,
            ];
        }

        return $icons;
    }
}
