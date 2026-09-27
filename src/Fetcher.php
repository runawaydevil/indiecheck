<?php

namespace Indiechecker;

use function Mf2\resolveUrl;

final class Fetcher
{
    public const USER_AGENT = 'indiechecker/1.0 (microformats and IndieWeb checker)';
    private const ALLOWED_PORTS = [80, 443, 8080, 8443];
    private const PROBE_TIMEOUT = 8;

    public function __construct(
        private int $maxBytes = 2_000_000,
        private int $timeout = 20,
        private int $maxRedirects = 5,
    ) {
    }

    public static function normalize(string $input): string
    {
        $url = trim($input);
        if ($url === '') {
            throw new FetchError('Enter a URL to check.');
        }
        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $url)) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            throw new FetchError('That doesn’t look like a URL.');
        }
        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            throw new FetchError('Only http and https URLs can be checked.');
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new FetchError('URLs with a username or password can’t be checked.');
        }

        return $url;
    }

    public function get(string $url, string $accept = 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.5'): Response
    {
        return $this->follow($url, $accept, true);
    }

    public function probe(string $url, string $accept): Response
    {
        return $this->follow($url, $accept, false);
    }

    private function follow(string $url, string $accept, bool $readBody): Response
    {
        $started = hrtime(true);
        $redirects = [];
        $current = self::normalize($url);

        for ($hop = 0; ; $hop++) {
            [$status, $headers, $body] = $this->request($current, $accept, $readBody);
            $location = $headers['location'][0] ?? null;

            if ($status < 300 || $status >= 400 || $location === null) {
                return new Response(
                    $url,
                    $current,
                    $status,
                    $headers,
                    $body,
                    $redirects,
                    intdiv(hrtime(true) - $started, 1_000_000),
                );
            }

            if ($hop >= $this->maxRedirects) {
                throw new FetchError("Gave up after {$this->maxRedirects} redirects.");
            }

            $redirects[] = ['url' => $current, 'status' => $status];
            $current = self::normalize(resolveUrl($current, $location));
        }
    }

    private function request(string $url, string $accept, bool $readBody): array
    {
        $parts = parse_url($url);
        $host = trim($parts['host'], '[]');
        $port = $parts['port'] ?? (strtolower($parts['scheme']) === 'https' ? 443 : 80);

        if (!in_array($port, self::ALLOWED_PORTS, true)) {
            throw new FetchError("Port {$port} is not allowed. Use 80, 443, 8080 or 8443.");
        }

        $address = $this->publicAddress($host);
        $headers = [];
        $body = '';
        $tooLarge = false;
        $probed = false;

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RESOLVE => ["{$host}:{$port}:" . (str_contains($address, ':') ? "[{$address}]" : $address)],
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $readBody ? $this->timeout : self::PROBE_TIMEOUT,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => ['Accept: ' . $accept],
            CURLOPT_ENCODING => '',
            CURLOPT_HEADERFUNCTION => function ($handle, string $line) use (&$headers) {
                if (str_starts_with($line, 'HTTP/')) {
                    $headers = [];
                } elseif (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $headers[strtolower(trim($name))][] = trim($value);
                }

                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => function ($handle, string $chunk) use (&$body, &$tooLarge, &$probed, $readBody) {
                if (!$readBody) {
                    $probed = true;

                    return 0;
                }
                if (strlen($body) + strlen($chunk) > $this->maxBytes) {
                    $tooLarge = true;

                    return 0;
                }
                $body .= $chunk;

                return strlen($chunk);
            },
        ]);

        $finished = curl_exec($handle);

        if ($tooLarge) {
            throw new FetchError(sprintf('%s is bigger than %s MB, the most this checker reads.', $url, round($this->maxBytes / 1_000_000, 1)));
        }
        if ($finished === false && !$probed) {
            throw new FetchError("Couldn’t fetch {$url}: " . curl_error($handle));
        }

        return [curl_getinfo($handle, CURLINFO_RESPONSE_CODE), $headers, $body];
    }

    private function publicAddress(string $host): string
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses = [$host];
        } else {
            $addresses = [];
            foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
                $addresses[] = $record['ip'] ?? $record['ipv6'];
            }
            if (!$addresses) {
                $addresses = gethostbynamel($host) ?: [];
            }
        }

        if (!$addresses) {
            throw new FetchError("Couldn’t find {$host}. Check the spelling of the domain.");
        }

        foreach ($addresses as $address) {
            if (!self::isPublic($address)) {
                throw new FetchError("{$host} points to a private or reserved address, so it can’t be checked from here. Paste the HTML instead.");
            }
        }

        usort($addresses, fn (string $a, string $b) => str_contains($a, ':') <=> str_contains($b, ':'));

        return $addresses[0];
    }

    private static function isPublic(string $address): bool
    {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE;

        return filter_var($address, FILTER_VALIDATE_IP, $flags) !== false;
    }
}
