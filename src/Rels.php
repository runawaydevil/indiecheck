<?php

namespace Indiechecker;

use function Mf2\resolveUrl;

final class Rels
{
    public readonly array $fromHtml;
    public readonly array $fromHeaders;
    public readonly array $relUrls;

    public function __construct(array $parsed, ?Response $response)
    {
        $this->fromHtml = $parsed['rels'];
        $this->relUrls = $parsed['rel-urls'];
        $this->fromHeaders = $response ? self::parseLinkHeaders($response->headerValues('link'), $response->url) : [];
    }

    public function endpoint(string $rel): ?array
    {
        if (isset($this->fromHeaders[$rel][0])) {
            return ['url' => $this->fromHeaders[$rel][0], 'source' => 'HTTP Link header'];
        }
        if (isset($this->fromHtml[$rel][0])) {
            return ['url' => $this->fromHtml[$rel][0], 'source' => 'HTML'];
        }

        return null;
    }

    public function urls(string $rel): array
    {
        return array_values(array_unique([...($this->fromHeaders[$rel] ?? []), ...($this->fromHtml[$rel] ?? [])]));
    }

    public function has(string $rel): bool
    {
        return $this->urls($rel) !== [];
    }

    public function count(): int
    {
        return count(array_unique([...array_keys($this->fromHtml), ...array_keys($this->fromHeaders)]));
    }

    public function grouped(): array
    {
        $groups = [];
        foreach ([...array_keys($this->fromHeaders), ...array_keys($this->fromHtml)] as $rel) {
            $group = Vocabulary::relGroup($rel);
            if (isset($groups[$group][$rel])) {
                continue;
            }

            $links = [];
            foreach ($this->fromHeaders[$rel] ?? [] as $url) {
                $links[] = ['url' => $url, 'source' => 'Link header', 'text' => null];
            }
            foreach ($this->fromHtml[$rel] ?? [] as $url) {
                $links[] = ['url' => $url, 'source' => 'HTML', 'text' => $this->relUrls[$url]['text'] ?? $this->relUrls[$url]['title'] ?? null];
            }

            $groups[$group][$rel] = ['about' => Vocabulary::relAbout($rel), 'links' => $links];
        }

        $order = [...array_keys(Vocabulary::REL_GROUPS), 'XFN', 'Other'];
        uksort($groups, fn (string $a, string $b) => array_search($a, $order, true) <=> array_search($b, $order, true));

        return $groups;
    }

    public function xfn(): array
    {
        $people = [];
        foreach ($this->relUrls as $url => $info) {
            $relations = array_values(array_intersect($info['rels'], array_diff(Vocabulary::XFN, ['me'])));
            if ($relations) {
                $people[] = ['url' => $url, 'text' => $info['text'] ?? null, 'relations' => $relations];
            }
        }

        return $people;
    }

    private static function parseLinkHeaders(array $headers, string $base): array
    {
        $rels = [];
        foreach ($headers as $header) {
            preg_match_all('/<([^>]*)>((?:\s*;\s*[^;,]+(?:="[^"]*"|=[^;,]*)?)*)/', $header, $links, PREG_SET_ORDER);
            foreach ($links as [, $target, $parameters]) {
                if (!preg_match('/;\s*rel\s*=\s*(?:"([^"]*)"|([^;,\s]+))/i', $parameters, $rel)) {
                    continue;
                }
                $url = resolveUrl($base, trim($target));
                foreach (class_tokens(strtolower(($rel[1] ?? '') !== '' ? $rel[1] : ($rel[2] ?? ''))) as $name) {
                    $rels[$name][] = $url;
                }
            }
        }

        return $rels;
    }
}
