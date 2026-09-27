<?php

use Indiechecker\FetchError;
use Indiechecker\Fetcher;
use Indiechecker\Report;

use function Indiechecker\view;

require __DIR__ . '/../vendor/autoload.php';

set_time_limit(90);

$fetcher = new Fetcher();
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$format = $isPost ? ($_POST['format'] ?? '') : ($_GET['format'] ?? '');
$url = trim($_GET['url'] ?? '');
$html = $isPost ? (string) ($_POST['html'] ?? '') : null;
$base = $isPost ? (string) ($_POST['base'] ?? '') : '';
$report = null;
$error = null;

try {
    if ($html !== null) {
        $report = Report::fromHtml($html, $base, $fetcher);
    } elseif ($url !== '') {
        $report = Report::fromUrl($url, $fetcher);
    }
} catch (FetchError $exception) {
    $error = $exception->getMessage();
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');

    $body = match (true) {
        $error !== null => ['error' => $error],
        $report !== null => $report->toArray(),
        default => ['usage' => 'GET /?url=https://example.com/&format=json, or POST html (and optionally base) with format=json.'],
    };
    http_response_code($error !== null ? 422 : ($report === null ? 400 : 200));
    echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    return;
}

header("Content-Security-Policy: default-src 'none'; style-src 'self'; font-src 'self'; img-src * data:; script-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");

if ($error !== null) {
    http_response_code(422);
}

echo view('page', [
    'report' => $report,
    'error' => $error,
    'url' => $url,
    'html' => $html,
    'base' => $base,
]);
