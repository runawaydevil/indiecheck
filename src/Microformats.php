<?php

namespace Indiechecker;

final class Microformats
{
    private const ROOT = '/^h(-[a-z0-9]+)?(-[a-z]+)+$/';
    private const PROPERTY = '/^(p|u|dt|e)-([a-z0-9]+-)?[a-z]+(-[a-z]+)*$/';
    private const MISTAKES = [
        'hcard' => 'h-card (or the classic vcard)',
        'h-vcard' => 'h-card',
        'hcalendar' => 'h-event (or the classic vevent)',
        'h-vevent' => 'h-event',
        'h-calendar' => 'h-event',
        'hatom' => 'h-entry (or the classic hentry)',
        'h-atom' => 'h-entry',
        'h-hentry' => 'h-entry',
        'h-post' => 'h-entry',
        'h-article' => 'h-entry',
        'h-person' => 'h-card',
        'h-address' => 'h-adr',
        'p-title' => 'p-name (or p-job-title on an h-card)',
        'e-summary' => 'p-summary',
        'u-link' => 'u-url',
        'dt-date' => 'dt-published',
        'dt-created' => 'dt-published',
        'dt-modified' => 'dt-updated',
    ];

    public readonly array $parsed;
    public readonly array $all;
    public readonly array $classic;
    public readonly array $voteLinks;
    public readonly array $orphans;
    public readonly array $mistakes;
    public readonly ?array $representativeCard;

    public function __construct(private Document $document)
    {
        $parser = new Mf2Parser($document->dom, $document->url);
        $this->parsed = $parser->parse();

        $all = [];
        foreach ($this->parsed['items'] as $index => $item) {
            $this->flatten($item, [], $item['type'][0] . '[' . ($index + 1) . ']', null, $all);
        }
        $this->all = $all;

        [$this->classic, $this->orphans, $this->mistakes] = $this->scanClasses();
        $this->voteLinks = $this->scanVoteLinks();
        $this->representativeCard = $this->findRepresentativeCard();
    }

    public function canonical(): array
    {
        return self::strip($this->parsed);
    }

    public function isEmpty(): bool
    {
        return $this->all === [];
    }

    public function counts(): array
    {
        $counts = [];
        foreach ($this->all as $found) {
            foreach ($found['item']['type'] as $type) {
                $counts[$type] = ($counts[$type] ?? 0) + 1;
            }
        }
        arsort($counts);

        return $counts;
    }

    public function ofType(string $type): array
    {
        return array_values(array_filter($this->all, fn (array $found) => in_array($type, $found['item']['type'], true)));
    }

    public function authorOf(array $found): ?array
    {
        if (!empty($found['item']['properties']['author'])) {
            return ['source' => 'author property', 'author' => $found['item']['properties']['author'][0]];
        }

        foreach (array_reverse($found['ancestors']) as $ancestor) {
            if (in_array('h-feed', $ancestor['type'], true) && !empty($ancestor['properties']['author'])) {
                return ['source' => 'inherited from the parent h-feed', 'author' => $ancestor['properties']['author'][0]];
            }
        }

        if (!empty($this->parsed['rels']['author'])) {
            return ['source' => 'rel=author link', 'author' => $this->parsed['rels']['author'][0]];
        }

        return null;
    }

    private function flatten(array $item, array $ancestors, string $path, ?string $property, array &$all): void
    {
        $all[] = ['item' => $item, 'ancestors' => $ancestors, 'path' => $path, 'property' => $property];
        $ancestors[] = $item;

        foreach ($item['properties'] as $name => $values) {
            foreach ($values as $value) {
                if (is_array($value) && isset($value['type'])) {
                    $this->flatten($value, $ancestors, "{$path} › {$name}", $name, $all);
                }
            }
        }

        foreach ($item['children'] ?? [] as $index => $child) {
            $this->flatten($child, $ancestors, "{$path} › {$child['type'][0]}[" . ($index + 1) . ']', null, $all);
        }
    }

    private function scanClasses(): array
    {
        $classic = [];
        $orphans = [];
        $mistakes = [];

        foreach ($this->document->all('//*[@class]') as $element) {
            $classes = class_tokens($element->getAttribute('class'));
            $hasRoot = (bool) preg_grep(self::ROOT, $classes);

            foreach ($classes as $class) {
                if (isset(Vocabulary::CLASSIC_ROOTS[$class])) {
                    $classic[$class] ??= ['count' => 0, 'alongsideMf2' => 0, 'example' => $this->document->snippet($element)];
                    $classic[$class]['count']++;
                    $classic[$class]['alongsideMf2'] += $hasRoot ? 1 : 0;
                }

                if (isset(self::MISTAKES[$class])) {
                    $mistakes[] = ['class' => $class, 'use' => self::MISTAKES[$class], 'snippet' => $this->document->snippet($element)];
                } elseif (preg_match('/^(h|p|u|dt|e)-/i', $class) && $class !== strtolower($class)) {
                    $mistakes[] = ['class' => $class, 'use' => strtolower($class) . ' (class names are case-sensitive and microformats are lowercase)', 'snippet' => $this->document->snippet($element)];
                }

                if (preg_match(self::PROPERTY, $class) && !$this->insideRoot($element)) {
                    $orphans[] = ['class' => $class, 'snippet' => $this->document->snippet($element)];
                }
            }
        }

        return [$classic, $orphans, $mistakes];
    }

    private function insideRoot(\DOMElement $element): bool
    {
        for ($parent = $element->parentNode; $parent instanceof \DOMElement; $parent = $parent->parentNode) {
            if (preg_grep(self::ROOT, class_tokens($parent->getAttribute('class')))) {
                return true;
            }
        }

        return false;
    }

    private function scanVoteLinks(): array
    {
        $votes = [];
        foreach ($this->document->all('//a[@rev and @href]') as $link) {
            foreach (class_tokens($link->getAttribute('rev')) as $rev) {
                if (in_array($rev, ['vote-for', 'vote-against', 'vote-abstain'], true)) {
                    $votes[] = ['vote' => $rev, 'url' => $this->document->resolve($link->getAttribute('href')), 'text' => shorten($link->textContent, 80)];
                }
            }
        }

        return $votes;
    }

    private function findRepresentativeCard(): ?array
    {
        $cards = [];
        foreach ($this->parsed['items'] as $item) {
            foreach ([$item, ...($item['children'] ?? [])] as $candidate) {
                if (in_array('h-card', $candidate['type'], true)) {
                    $cards[] = $candidate;
                }
            }
        }

        $page = $this->document->url;
        $relMe = array_map(self::comparable(...), $this->parsed['rels']['me'] ?? []);

        foreach ($cards as $card) {
            if ($page !== null
                && array_intersect(self::urls($card, 'uid'), [self::comparable($page)])
                && array_intersect(self::urls($card, 'url'), [self::comparable($page)])) {
                return ['card' => $card, 'reason' => 'its uid and url both match the page URL'];
            }
        }

        foreach ($cards as $card) {
            if (array_intersect(self::urls($card, 'url'), $relMe)) {
                return ['card' => $card, 'reason' => 'its url is also a rel=me link'];
            }
        }

        if ($page !== null && count($cards) === 1 && in_array(self::comparable($page), self::urls($cards[0], 'url'), true)) {
            return ['card' => $cards[0], 'reason' => 'it is the only h-card and its url matches the page URL'];
        }

        return null;
    }

    private static function urls(array $item, string $property): array
    {
        return array_map(
            fn ($value) => self::comparable(is_array($value) ? ($value['value'] ?? '') : $value),
            $item['properties'][$property] ?? [],
        );
    }

    private static function comparable(string $url): string
    {
        return rtrim(preg_replace('~^https?://~i', '', strtolower(trim($url))), '/');
    }

    private static function strip(array $data): array
    {
        unset($data[Mf2Parser::CLASSIC_KEY]);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::strip($value);
            }
        }

        return $data;
    }
}
