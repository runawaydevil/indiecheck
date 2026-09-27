<?php

use function Indiechecker\e;
use function Indiechecker\view;

$checked = $report?->url();
$host = $checked ? parse_url($checked, PHP_URL_HOST) : null;
$pasted = $html !== null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $report ? e(($host ?? 'Pasted HTML') . ' · indiechecker') : 'indiechecker · microformats and IndieWeb checker' ?></title>
    <meta name="description" content="Paste a URL or some HTML and see every microformat, rel link, IndieWeb endpoint and meta tag on the page, with fixes for what’s missing.">
    <meta property="og:title" content="indiechecker">
    <meta property="og:description" content="Every microformat, rel link, IndieWeb endpoint and meta tag on a page, with fixes for what’s missing.">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
    <script src="app.js" defer></script>
</head>
<body>
<svg class="sprite" aria-hidden="true" focusable="false">
    <symbol id="i-check" viewBox="0 0 16 16"><path d="M3.5 8.5l3 3 6-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-missing" viewBox="0 0 16 16"><path d="M4 8h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-error" viewBox="0 0 16 16"><path d="M5 5l6 6M11 5l-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-warning" viewBox="0 0 16 16"><path d="M8 4v5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="8" cy="12" r="1.2" fill="currentColor"/></symbol>
    <symbol id="i-tip" viewBox="0 0 16 16"><circle cx="8" cy="4.5" r="1.2" fill="currentColor"/><path d="M8 7.5V12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
</svg>

<div class="wrapper">
    <header class="side h-app">
        <h1 class="brand"><a class="p-name u-url" href="./">indiechecker</a></h1>
        <p class="tagline p-summary">Every microformat, rel link and IndieWeb endpoint on a page.</p>

        <form class="check" method="get" action="./">
            <label class="visually-hidden" for="url">Page URL</label>
            <input id="url" name="url" type="text" inputmode="url" autocomplete="url" spellcheck="false"
                   placeholder="https://your.site/" value="<?= e($pasted ? '' : $url) ?>" required>
            <button class="button" type="submit">Check</button>
        </form>

        <details class="paste"<?= $pasted ? ' open' : '' ?>>
            <summary>Or paste some HTML</summary>
            <form method="post" action="./">
                <label for="html">HTML</label>
                <textarea id="html" name="html" rows="8" spellcheck="false" required
                          placeholder="&lt;article class=&quot;h-entry&quot;&gt;…&lt;/article&gt;"><?= e($html ?? '') ?></textarea>
                <label for="base">Page URL <span class="optional">(optional, for relative links)</span></label>
                <input id="base" name="base" type="text" inputmode="url" spellcheck="false" placeholder="https://your.site/post" value="<?= e($base) ?>">
                <button class="button" type="submit">Check HTML</button>
            </form>
        </details>

        <?php if ($report !== null): ?>
            <?= view('nav', ['report' => $report]) ?>
        <?php endif ?>
    </header>

    <main class="content">
        <?php if ($error !== null): ?>
            <div class="alert" role="alert">
                <svg class="icon"><use href="#i-error"/></svg>
                <p><?= e($error) ?></p>
            </div>
        <?php endif ?>

        <?php if ($report !== null): ?>
            <?= view('report', ['report' => $report, 'pasted' => $pasted]) ?>
        <?php elseif ($error === null): ?>
            <?= view('intro') ?>
        <?php endif ?>
    </main>

    <footer class="site-footer">
        <p>Vocabularies from <a href="https://microformats.org/wiki/Main_Page">microformats.org</a>.</p>
        <p>dev pablo murad 2026</p>
    </footer>
</div>
</body>
</html>
