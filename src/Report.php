<?php

namespace Indiechecker;

final class Report
{
    private const HTML_TYPES = ['text/html', 'application/xhtml+xml'];
    private const MAX_FEED_CHECKS = 4;
    private const MAX_PASTE_BYTES = 2_000_000;

    public readonly Document $document;
    public readonly Microformats $microformats;
    public readonly Rels $rels;
    public readonly Metadata $metadata;
    public readonly array $feedChecks;
    public readonly Advice $advice;

    private function __construct(public readonly ?Response $response, string $html, ?string $url, Fetcher $fetcher)
    {
        $this->document = new Document($html, $url, $response?->charset());
        $this->microformats = new Microformats($this->document);
        $this->rels = new Rels($this->microformats->parsed, $response);
        $this->metadata = new Metadata($this->document);
        $this->feedChecks = $this->checkFeeds($fetcher);
        $this->advice = new Advice($this->microformats, $this->rels, $this->metadata, $this->feedChecks, $url, $response?->charset());
    }

    public static function fromUrl(string $url, Fetcher $fetcher): self
    {
        $response = $fetcher->get($url);

        if ($response->status >= 400) {
            throw new FetchError("{$response->url} answered with HTTP {$response->status}.");
        }
        $type = $response->contentType();
        if ($type !== '' && !in_array($type, self::HTML_TYPES, true)) {
            throw new FetchError("{$response->url} is {$type}, not an HTML page.");
        }

        return new self($response, $response->body, $response->url, $fetcher);
    }

    public static function fromHtml(string $html, ?string $baseUrl, Fetcher $fetcher): self
    {
        if (trim($html) === '') {
            throw new FetchError('Paste some HTML to check.');
        }
        if (strlen($html) > self::MAX_PASTE_BYTES) {
            throw new FetchError('That HTML is bigger than 2 MB, the most this checker reads.');
        }

        $baseUrl = trim((string) $baseUrl) === '' ? null : Fetcher::normalize($baseUrl);

        return new self(null, $html, $baseUrl, $fetcher);
    }

    public function url(): ?string
    {
        return $this->document->url;
    }

    public function indieWeb(): array
    {
        $mf = $this->microformats;
        $card = $mf->representativeCard;
        $relMe = $this->rels->urls('me');
        $entries = $mf->ofType('h-entry');
        $authored = array_filter($entries, fn (array $found) => $mf->authorOf($found) !== null);
        $indieAuth = $this->rels->endpoint('indieauth-metadata');
        $authorization = $this->rels->endpoint('authorization_endpoint');
        $token = $this->rels->endpoint('token_endpoint');

        return [
            [
                'label' => 'Representative h-card',
                'ok' => $card !== null,
                'detail' => $card ? 'Found: ' . $card['reason'] . '.' : 'No h-card clearly represents this page.',
            ],
            [
                'label' => 'rel=me links',
                'ok' => $relMe !== [],
                'detail' => $relMe ? count($relMe) . ' profile link(s).' : 'None found.',
                'links' => $relMe,
            ],
            $this->endpointCheck('Webmention endpoint', 'webmention'),
            [
                'label' => 'IndieAuth',
                'ok' => $indieAuth !== null || ($authorization && $token),
                'detail' => match (true) {
                    $indieAuth !== null => 'Metadata endpoint found in the ' . $indieAuth['source'] . '.',
                    $authorization && $token => 'Authorization and token endpoints found (older discovery).',
                    $authorization || $token => 'Only one of the two endpoints is there.',
                    default => 'No IndieAuth endpoints.',
                },
                'links' => array_filter([$indieAuth['url'] ?? null, $authorization['url'] ?? null, $token['url'] ?? null]),
            ],
            $this->endpointCheck('Micropub endpoint', 'micropub'),
            $this->endpointCheck('Microsub endpoint', 'microsub'),
            $this->endpointCheck('WebSub hub', 'hub'),
            [
                'label' => 'Posts with an author',
                'ok' => $entries !== [] && count($authored) === count($entries),
                'detail' => $entries
                    ? sprintf('%d of %d h-entry post(s) have an author.', count($authored), count($entries))
                    : 'No h-entry posts on this page.',
            ],
        ];
    }

    public function toArray(): array
    {
        $canonical = $this->microformats->canonical();
        $canonical['rels'] = $canonical['rels'] ?: new \stdClass();
        $canonical['rel-urls'] = $canonical['rel-urls'] ?: new \stdClass();

        return [
            'url' => $this->url(),
            'fetch' => $this->response ? [
                'requested' => $this->response->requestedUrl,
                'final' => $this->response->url,
                'status' => $this->response->status,
                'content-type' => $this->response->header('content-type'),
                'bytes' => strlen($this->response->body),
                'milliseconds' => $this->response->milliseconds,
                'redirects' => $this->response->redirects,
            ] : null,
            'summary' => [
                'types' => $this->microformats->counts() ?: new \stdClass(),
                'classic' => $this->microformats->classic ?: new \stdClass(),
                'representative-h-card' => $this->microformats->representativeCard['card']['properties']['url'][0] ?? null,
                'errors' => $this->advice->count('error'),
                'warnings' => $this->advice->count('warning'),
                'tips' => $this->advice->count('tip'),
            ],
            'mf2' => $canonical,
            'indieweb' => $this->indieWeb(),
            'rels' => [
                'html' => $this->rels->fromHtml ?: new \stdClass(),
                'link-header' => $this->rels->fromHeaders ?: new \stdClass(),
                'xfn' => $this->rels->xfn(),
                'vote-links' => $this->microformats->voteLinks,
            ],
            'metadata' => $this->metadata->toArray(),
            'feed-checks' => $this->feedChecks,
            'advice' => $this->advice->all(),
        ];
    }

    private function endpointCheck(string $label, string $rel): array
    {
        $endpoint = $this->rels->endpoint($rel);

        return [
            'label' => $label,
            'ok' => $endpoint !== null,
            'detail' => $endpoint ? 'Found in the ' . $endpoint['source'] . '.' : 'None found.',
            'links' => $endpoint ? [$endpoint['url']] : [],
        ];
    }

    private function checkFeeds(Fetcher $fetcher): array
    {
        $checks = [];
        $feeds = array_filter($this->metadata->feeds, fn (array $feed) => is_absolute_url($feed['url']));

        foreach (array_slice($feeds, 0, self::MAX_FEED_CHECKS) as $feed) {
            try {
                $response = $fetcher->probe($feed['url'], 'application/rss+xml, application/atom+xml, application/feed+json, application/xml;q=0.9, text/html;q=0.8, */*;q=0.5');
            } catch (FetchError $error) {
                $checks[] = ['url' => $feed['url'], 'ok' => false, 'problem' => $error->getMessage()];
                continue;
            }

            $type = $response->contentType();
            $problem = match (true) {
                $response->status >= 400 => "It answered with HTTP {$response->status}.",
                $feed['type'] === 'text/html' && !in_array($type, self::HTML_TYPES, true) => "It’s served as {$type}, not HTML.",
                $feed['type'] !== 'text/html' && !preg_match('/xml|rss|atom|json/', $type) => "It’s served as “{$type}”, which feed readers may reject.",
                default => null,
            };

            $checks[] = [
                'url' => $feed['url'],
                'ok' => $problem === null,
                'problem' => $problem,
                'status' => $response->status,
                'content-type' => $type,
            ];
        }

        return $checks;
    }
}
