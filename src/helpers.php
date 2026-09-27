<?php

namespace Indiechecker;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

function class_tokens(string $class): array
{
    return array_values(array_unique(preg_split('/[\t\n\f\r ]+/', $class, -1, PREG_SPLIT_NO_EMPTY)));
}

function is_absolute_url(string $value): bool
{
    return (bool) preg_match('~^https?://[^\s/]+~i', $value);
}

function first_text(array $item, string $property): ?string
{
    $value = $item['properties'][$property][0] ?? null;
    if (is_array($value)) {
        $value = $value['value'] ?? null;
    }

    return is_string($value) ? $value : null;
}

function view(string $template, array $data = []): string
{
    extract($data);
    ob_start();
    require dirname(__DIR__) . "/views/{$template}.php";

    return ob_get_clean();
}

function link_to(string $url, ?string $text = null): string
{
    if (!is_absolute_url($url)) {
        return '<code>' . e($text ?? $url) . '</code>';
    }

    return sprintf('<a href="%s" rel="nofollow noopener noreferrer">%s</a>', e($url), e($text ?? $url));
}

function looks_like_image(string $url): bool
{
    return (bool) preg_match('~\.(avif|gif|ico|jpe?g|png|svg|webp)([?#].*)?$~i', $url);
}

function shorten(string $text, int $length = 280): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text));

    return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length - 1)) . '…' : $text;
}
