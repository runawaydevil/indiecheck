<?php

namespace Indiechecker;

final class Response
{
    public function __construct(
        public readonly string $requestedUrl,
        public readonly string $url,
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body,
        public readonly array $redirects,
        public readonly int $milliseconds,
    ) {
    }

    public function header(string $name): ?string
    {
        $values = $this->headers[strtolower($name)] ?? [];

        return $values ? end($values) : null;
    }

    public function headerValues(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function contentType(): string
    {
        return strtolower(trim(explode(';', $this->header('content-type') ?? '')[0]));
    }

    public function charset(): ?string
    {
        if (preg_match('/charset\s*=\s*"?([\w.:-]+)/i', $this->header('content-type') ?? '', $match)) {
            return $match[1];
        }

        return null;
    }
}
