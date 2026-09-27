<?php

namespace Indiechecker;

final class Advice
{
    private const LEVELS = ['error' => 0, 'warning' => 1, 'tip' => 2];
    private const DURATIONS = ['duration'];
    private const LOOSE_URLS = ['email', 'tel', 'impp', 'key', 'uid'];

    private array $items = [];

    public function __construct(
        private Microformats $microformats,
        private Rels $rels,
        private Metadata $metadata,
        private array $feedChecks,
        private ?string $pageUrl,
        private ?string $headerCharset,
    ) {
        $this->checkMicroformats();
        $this->checkIndieWeb();
        $this->checkMetadata();
    }

    public function all(): array
    {
        $items = array_values($this->items);
        usort($items, fn (array $a, array $b) => self::LEVELS[$a['level']] <=> self::LEVELS[$b['level']]);

        return $items;
    }

    public function count(string $level): int
    {
        return count(array_filter($this->items, fn (array $item) => $item['level'] === $level));
    }

    private function add(string $key, string $level, string $area, string $title, string $detail, ?string $fix = null, ?string $where = null): void
    {
        $this->items[$key] ??= compact('level', 'area', 'title', 'detail', 'fix') + ['where' => []];
        if ($where !== null && !in_array($where, $this->items[$key]['where'], true)) {
            $this->items[$key]['where'][] = $where;
        }
    }

    private function checkMicroformats(): void
    {
        $mf = $this->microformats;

        if ($mf->isEmpty() && !$mf->classic) {
            $this->add('no-mf', 'warning', 'Microformats', 'No microformats on this page',
                'Nothing here is marked up with microformats, so readers, Webmention receivers and IndieWeb tools can’t tell who wrote it or what it is. An h-card is the usual first step.',
                $this->cardSnippet());
        }

        foreach ($mf->classic as $class => $found) {
            $root = Vocabulary::CLASSIC_ROOTS[$class];
            $alone = $found['count'] - $found['alongsideMf2'];
            if ($alone === 0) {
                continue;
            }
            if ($root['mf2'] === null) {
                continue;
            }
            $this->add("classic-{$class}", 'tip', 'Microformats', "Classic {$root['name']} without {$root['mf2']}",
                "{$alone} element(s) use the classic “{$class}” class. Parsers still read it through backward compatibility, but mf2 properties only work under an mf2 root. Keep the old class and add {$root['mf2']} next to it, then move the properties to their mf2 names.",
                "<div class=\"{$class} {$root['mf2']}\">…</div>", $found['example']);
        }

        if (isset($mf->classic['hlisting'])) {
            $this->add('classic-hlisting-parse', 'tip', 'Microformats', 'hListing isn’t parsed by mf2 parsers',
                'mf2 parsers have no backward compatibility rules for hListing, so these listings are invisible to them. Mark them up with h-listing.',
                '<div class="hlisting h-listing">…</div>');
        }

        foreach ($mf->mistakes as $mistake) {
            $this->add("mistake-{$mistake['class']}", 'error', 'Microformats', "“{$mistake['class']}” isn’t a microformats class",
                "Parsers ignore it. Use {$mistake['use']}.", null, $mistake['snippet']);
        }

        foreach ($mf->orphans as $orphan) {
            $this->add('orphans', 'warning', 'Microformats', 'Properties outside any h-* root',
                'These elements have microformats property classes (p-, u-, dt-, e-) but no h-* element around them, so parsers skip them. Wrap them in the right root, like h-entry or h-card.',
                "<article class=\"h-entry\">\n  <!-- the p-*, u-*, dt-* and e-* elements go in here -->\n</article>", $orphan['snippet']);
        }

        $topEntries = array_filter($mf->parsed['items'], fn (array $item) => in_array('h-entry', $item['type'], true));
        if (count($topEntries) > 1 && !$mf->ofType('h-feed')) {
            $this->add('entries-no-feed', 'tip', 'Microformats', 'Several h-entry posts without an h-feed',
                'Readers treat a page of top-level h-entry items as an implied feed, but an explicit h-feed lets you give it a name and an author.',
                "<main class=\"h-feed\">\n  <h1 class=\"p-name\">Posts</h1>\n  <article class=\"h-entry\">…</article>\n</main>");
        }

        foreach ($mf->all as $found) {
            $this->checkItem($found);
        }
    }

    private function checkItem(array $found): void
    {
        $item = $found['item'];
        $types = $item['type'];
        $properties = $item['properties'];
        $where = $found['path'];

        foreach ($types as $type) {
            $status = Vocabulary::status($type);
            if ($status === 'unknown') {
                $this->add("unknown-type-{$type}", 'tip', 'Microformats', "“{$type}” isn’t a known vocabulary",
                    'Parsers still read it, but no consumer will understand it. Check the spelling, or use the h-x- prefix for experiments.', null, $where);
            }
            if ($status === 'experimental' && $type === 'h-x-app') {
                $this->add('h-x-app', 'tip', 'Microformats', 'h-x-app is the old name of h-app',
                    'IndieAuth servers read both, but h-app is the current name. You can keep both classes.', '<div class="h-app h-x-app">…</div>', $where);
            }
        }

        foreach ($properties as $name => $values) {
            if (!str_starts_with($name, 'x-') && array_filter($types, fn ($type) => Vocabulary::type($type)) && !Vocabulary::knowsProperty($types, $name)) {
                $typeList = implode(' ', $types);
                $this->add("unknown-property-{$typeList}-{$name}", 'tip', 'Microformats', "“{$name}” isn’t a property of {$typeList}",
                    'Consumers won’t look for it. Check the spelling against the vocabulary, or prefix experiments with x-.', null, $where);
            }

            $prefix = Vocabulary::prefix($types, $name, $values[0]);
            foreach ($values as $value) {
                $text = is_array($value) ? ($value['value'] ?? null) : $value;
                if (!is_string($text)) {
                    continue;
                }
                if ($prefix === 'dt') {
                    $this->checkDate($name, $text, $where);
                }
                if ($prefix === 'u' && !in_array($name, self::LOOSE_URLS, true) && !(is_array($value) && isset($value['type']))) {
                    $this->checkUrl($name, $text, $where);
                }
            }
        }

        if (in_array('h-entry', $types, true)) {
            $this->checkEntry($found);
        }
        if (in_array('h-card', $types, true) && trim((string) first_text($item, 'name')) === '') {
            $this->add('card-no-name', 'warning', 'Microformats', 'h-card without a name',
                'Every h-card needs a name. Add p-name, or put the name as the only text inside the h-card.', '<a class="h-card p-name u-url" href="https://example.com/">Your Name</a>', $where);
        }
        if (in_array('h-event', $types, true)) {
            if (!isset($properties['start'])) {
                $this->add('event-no-start', 'error', 'Microformats', 'h-event without dt-start',
                    'An event needs a start date and time.', '<time class="dt-start" datetime="2026-10-03T19:00:00-03:00">October 3, 7 pm</time>', $where);
            }
            if (trim((string) first_text($item, 'name')) === '') {
                $this->add('event-no-name', 'warning', 'Microformats', 'h-event without a name', 'Give the event a p-name.', '<h2 class="p-name">Homebrew Website Club</h2>', $where);
            }
        }
        if (in_array('h-feed', $types, true) && !array_filter($item['children'] ?? [], fn (array $child) => in_array('h-entry', $child['type'], true))) {
            $this->add('feed-empty', 'warning', 'Microformats', 'h-feed without h-entry posts',
                'Readers expect h-entry items inside an h-feed. Check that each post has class="h-entry" and sits inside the feed element.', null, $where);
        }
        if (in_array('h-review', $types, true)) {
            if (!isset($properties['item'])) {
                $this->add('review-no-item', 'warning', 'Microformats', 'h-review without p-item', 'Say what is being reviewed with p-item, ideally an h-item, h-card, h-product or h-event.', '<a class="p-item h-item" href="https://example.com/thing">The Thing</a>', $where);
            }
            if (!isset($properties['rating'])) {
                $this->add('review-no-rating', 'tip', 'Microformats', 'h-review without p-rating', 'A rating lets readers show stars. Add p-rating, and p-best if the scale isn’t 1 to 5.', '<data class="p-rating" value="4">★★★★☆</data>', $where);
            }
        }
        if (in_array('h-review-aggregate', $types, true)) {
            if (!isset($properties['average']) && !isset($properties['rating'])) {
                $this->add('aggregate-no-average', 'warning', 'Microformats', 'h-review-aggregate without p-average', 'An aggregate review needs the average rating.', '<data class="p-average" value="4.2">4.2</data> from <data class="p-count" value="87">87</data> reviews', $where);
            }
            if (!isset($properties['item'])) {
                $this->add('aggregate-no-item', 'warning', 'Microformats', 'h-review-aggregate without p-item', 'Say what the reviews are about with p-item.', '<span class="p-item h-item"><span class="p-name">The Thing</span></span>', $where);
            }
        }
        if (in_array('h-recipe', $types, true)) {
            if (!isset($properties['ingredient'])) {
                $this->add('recipe-no-ingredient', 'warning', 'Microformats', 'h-recipe without ingredients', 'Mark each ingredient with p-ingredient.', '<li class="p-ingredient">2 cups of flour</li>', $where);
            }
            if (!isset($properties['instructions'])) {
                $this->add('recipe-no-instructions', 'tip', 'Microformats', 'h-recipe without e-instructions', 'Wrap the method in e-instructions.', '<ol class="e-instructions">…</ol>', $where);
            }
        }
        if (in_array('h-geo', $types, true)) {
            foreach (['latitude', 'longitude'] as $axis) {
                $value = first_text($item, $axis);
                if ($value === null || !is_numeric(trim((string) $value))) {
                    $this->add("geo-{$axis}", 'error', 'Microformats', "h-geo without a numeric {$axis}", 'Coordinates are decimal degrees, like -23.5505.', '<data class="p-latitude" value="-23.5505"></data><data class="p-longitude" value="-46.6333"></data>', $where);
                }
            }
        }
        if (in_array('h-product', $types, true) && trim((string) first_text($item, 'name')) === '') {
            $this->add('product-no-name', 'warning', 'Microformats', 'h-product without a name', 'Add p-name to the product.', '<h2 class="p-name">Blue Mug</h2>', $where);
        }
    }

    private function checkEntry(array $found): void
    {
        $properties = $found['item']['properties'];
        $where = $found['path'];

        if ($this->microformats->authorOf($found) === null) {
            $this->add('entry-no-author', 'warning', 'Microformats', 'h-entry without an author',
                'Webmention receivers and readers can’t tell who wrote the post. Add a p-author h-card inside the entry, on the parent h-feed, or a rel=author link.',
                '<a class="p-author h-card" href="https://example.com/">Your Name</a>', $where);
        }
        if (!isset($properties['published'])) {
            $this->add('entry-no-published', 'warning', 'Microformats', 'h-entry without dt-published',
                'Readers sort and display posts by their publish date.',
                '<time class="dt-published" datetime="2026-09-27T14:30:00-03:00">September 27, 2026</time>', $where);
        }
        if (!isset($properties['url'])) {
            $this->add('entry-no-url', 'tip', 'Microformats', 'h-entry without u-url',
                'A u-url permalink lets people reply to, like and share this exact post.',
                '<a class="u-url" href="https://example.com/2026/09/my-post">Permalink</a>', $where);
        }
        if (!isset($properties['content']) && !isset($properties['name']) && !isset($properties['summary'])) {
            $this->add('entry-no-content', 'warning', 'Microformats', 'h-entry without content',
                'The post has no e-content, p-name or p-summary, so there’s nothing to show.', '<div class="e-content">…</div>', $where);
        }
    }

    private function checkDate(string $name, string $value, string $where): void
    {
        if (in_array($name, self::DURATIONS, true)) {
            if (!Dates::isDuration($value) && !Dates::isIso($value)) {
                $this->add("bad-duration-{$name}", 'error', 'Microformats', "dt-{$name} isn’t an ISO 8601 duration",
                    "“{$value}” can’t be read. Durations look like PT1H30M.", '<time class="dt-duration" datetime="PT1H30M">1 hour 30 minutes</time>', $where);
            }

            return;
        }

        if (!Dates::isIso($value)) {
            $this->add("bad-date-{$name}", 'error', 'Microformats', "dt-{$name} isn’t an ISO 8601 date",
                "“{$value}” can’t be read as a date. Put the machine-readable date in a datetime attribute and keep any text you like inside the element.",
                "<time class=\"dt-{$name}\" datetime=\"2026-09-27T14:30:00-03:00\">September 27, 2026</time>", $where);
        } elseif (in_array($name, ['published', 'updated', 'start', 'end'], true) && str_contains($value, ':') && !Dates::hasZone($value)) {
            $this->add("date-no-zone-{$name}", 'tip', 'Microformats', "dt-{$name} has a time but no timezone",
                "“{$value}” will be read in whatever timezone the reader is in. Add an offset like -03:00 or Z.", null, $where);
        }
    }

    private function checkUrl(string $name, string $value, string $where): void
    {
        if (trim($value) === '') {
            $this->add("empty-url-{$name}", 'warning', 'Microformats', "Empty u-{$name}", 'The property is there but has no URL. Check the href or src.', null, $where);
        } elseif (preg_match('/^\s*javascript:/i', $value)) {
            $this->add("js-url-{$name}", 'error', 'Microformats', "u-{$name} points to javascript:", 'Use a real URL.', null, $where);
        }
    }

    private function checkIndieWeb(): void
    {
        $mf = $this->microformats;
        $cards = $mf->ofType('h-card');

        if (!$mf->isEmpty() && !$cards) {
            $this->add('no-card', 'warning', 'IndieWeb', 'No h-card on the page',
                'An h-card says who is behind the site. IndieWeb tools use it to show your name and photo next to replies and mentions.',
                $this->cardSnippet());
        } elseif ($cards && $mf->representativeCard === null && $this->pageUrl !== null) {
            $this->add('no-representative-card', 'warning', 'IndieWeb', 'None of the h-cards represents this page',
                'Tools look for a representative h-card: one whose u-url is also a rel=me link, or whose u-url and u-uid both equal the page URL. Mark your own h-card that way.',
                sprintf('<a class="h-card u-url u-uid" rel="me" href="%s">Your Name</a>', $this->pageUrl));
        }

        if (!$this->rels->has('me')) {
            $this->add('no-rel-me', 'tip', 'IndieWeb', 'No rel=me links',
                'rel=me links connect this site to your other profiles. They power IndieLogin and the verified links on Mastodon.',
                "<a rel=\"me\" href=\"https://github.com/you\">GitHub</a>\n<a rel=\"me\" href=\"https://mastodon.social/@you\">Mastodon</a>");
        }

        if (!$this->rels->has('webmention')) {
            $this->add('no-webmention', 'tip', 'IndieWeb', 'No Webmention endpoint',
                'Without one, other sites can’t notify you when they reply to or mention your posts. A hosted service like webmention.io works well.',
                '<link rel="webmention" href="https://webmention.io/example.com/webmention">');
        }

        $authorization = $this->rels->has('authorization_endpoint');
        $token = $this->rels->has('token_endpoint');
        if (!$this->rels->has('indieauth-metadata') && $authorization !== $token) {
            $missing = $authorization ? 'token_endpoint' : 'authorization_endpoint';
            $this->add('indieauth-half', 'warning', 'IndieWeb', "IndieAuth is missing {$missing}",
                'IndieAuth clients need both endpoints, or a single indieauth-metadata link that lists them.',
                "<link rel=\"{$missing}\" href=\"https://example.com/auth/{$missing}\">");
        }
        if (($authorization || $token) && !$this->rels->has('indieauth-metadata')) {
            $this->add('indieauth-metadata', 'tip', 'IndieWeb', 'No indieauth-metadata link',
                'The current IndieAuth spec discovers endpoints through a metadata document. Keep the old links and add the new one.',
                '<link rel="indieauth-metadata" href="https://example.com/.well-known/oauth-authorization-server">');
        }
    }

    private function checkMetadata(): void
    {
        $meta = $this->metadata;

        if ($meta->title === null || $meta->title === '') {
            $this->add('no-title', 'error', 'Metadata', 'No <title>', 'Browsers, search engines and link previews all use it.', '<title>My page</title>');
        } elseif (mb_strlen($meta->title) > 70) {
            $this->add('long-title', 'tip', 'Metadata', 'Long title', sprintf('The title has %d characters. Search results and previews cut it around 60 to 70.', mb_strlen($meta->title)));
        }
        if ($meta->description === null) {
            $this->add('no-description', 'warning', 'Metadata', 'No meta description', 'Search engines and link previews show it under the title.', '<meta name="description" content="A sentence about this page.">');
        }
        if ($meta->lang === null) {
            $this->add('no-lang', 'warning', 'Metadata', 'No lang on <html>', 'Screen readers and translators need to know the language.', '<html lang="en">');
        }
        if ($meta->charset === null && $this->headerCharset === null) {
            $this->add('no-charset', 'warning', 'Metadata', 'No character encoding', 'Without it, browsers may guess wrong and break accents and symbols.', '<meta charset="utf-8">');
        }
        if ($meta->viewport === null) {
            $this->add('no-viewport', 'warning', 'Metadata', 'No viewport meta tag', 'Phones will render the page zoomed out at desktop width.', '<meta name="viewport" content="width=device-width, initial-scale=1">');
        }

        if ($meta->openGraph('og:title') === null) {
            $this->add('no-og-title', 'tip', 'Metadata', 'No og:title', 'Link previews fall back to the page title, which may carry the site name twice.', '<meta property="og:title" content="My page">');
        }
        $image = $meta->openGraph('og:image');
        if ($image === null) {
            $this->add('no-og-image', 'tip', 'Metadata', 'No og:image', 'Shared links show a bare text card without it. 1200×630 is a safe size.', '<meta property="og:image" content="https://example.com/card.png">');
        } elseif (!is_absolute_url($image)) {
            $this->add('relative-og-image', 'warning', 'Metadata', 'og:image is a relative URL', "Open Graph requires an absolute URL, and many previews ignore “{$image}”.", sprintf('<meta property="og:image" content="%s">', \Mf2\resolveUrl($this->pageUrl ?? 'https://example.com/', $image)));
        }
        if ($image !== null && $meta->twitter('twitter:card') === null) {
            $this->add('no-twitter-card', 'tip', 'Metadata', 'No twitter:card', 'Some apps show a small thumbnail unless you ask for the large image card.', '<meta name="twitter:card" content="summary_large_image">');
        }

        foreach ($meta->fediverseCreators as $creator) {
            if (!preg_match('/^@?[^@\s]+@[^@\s]+\.[^@\s]+$/', $creator)) {
                $this->add('bad-fediverse-creator', 'warning', 'Metadata', 'fediverse:creator isn’t a fediverse handle', "“{$creator}” should look like @you@instance.social.", '<meta name="fediverse:creator" content="@you@mastodon.social">');
            }
        }
        $mastodonLike = array_filter($this->rels->urls('me'), fn (string $url) => preg_match('~^https://[^/]+/@[^/]+/?$~', $url));
        if (!$meta->fediverseCreators && $mastodonLike) {
            $handle = preg_replace('~^https://([^/]+)/@([^/]+)/?$~', '@$2@$1', reset($mastodonLike));
            $this->add('no-fediverse-creator', 'tip', 'Metadata', 'No fediverse:creator',
                'You link to a fediverse profile with rel=me. With this tag, Mastodon shows your name under links to your posts. Your domain also has to be listed under Author attribution in your Mastodon settings.',
                sprintf('<meta name="fediverse:creator" content="%s">', $handle));
        }

        if (!array_filter($meta->icons, fn (array $icon) => $icon['kind'] === 'icon')) {
            $this->add('no-icon', 'tip', 'Metadata', 'No favicon link', 'Browsers fall back to /favicon.ico. An explicit link lets you use SVG or PNG.', '<link rel="icon" href="/favicon.svg" type="image/svg+xml">');
        }
        if (!array_filter($meta->icons, fn (array $icon) => $icon['kind'] === 'apple-touch-icon')) {
            $this->add('no-touch-icon', 'tip', 'Metadata', 'No apple-touch-icon', 'iOS and some Android launchers use it for home screen shortcuts.', '<link rel="apple-touch-icon" href="/apple-touch-icon.png">');
        }

        if (!$meta->feeds && !$this->microformats->ofType('h-feed') && !$this->microformats->ofType('h-entry')) {
            $this->add('no-feed', 'tip', 'Metadata', 'No feed', 'No RSS, Atom, JSON Feed or h-feed found. Readers need one to follow the site.', '<link rel="alternate" type="application/rss+xml" title="RSS" href="/feed.xml">');
        }
        foreach ($this->feedChecks as $check) {
            if (!$check['ok']) {
                $this->add('broken-feed-' . $check['url'], 'warning', 'Metadata', 'A declared feed has a problem', $check['problem'], null, $check['url']);
            }
        }
    }

    private function cardSnippet(): string
    {
        return sprintf(
            "<div class=\"h-card\">\n  <img class=\"u-photo\" src=\"/me.jpg\" alt=\"\">\n  <a class=\"p-name u-url u-uid\" rel=\"me\" href=\"%s\">Your Name</a>\n  <p class=\"p-note\">A line about you.</p>\n</div>",
            $this->pageUrl ?? 'https://example.com/',
        );
    }
}
