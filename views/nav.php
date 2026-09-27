<?php

use function Indiechecker\e;

$total = count($report->microformats->all);
$checks = $report->indieWeb();
$passing = count(array_filter($checks, fn (array $check) => $check['ok']));
$feeds = count($report->metadata->feeds);
$errors = $report->advice->count('error');
$warnings = $report->advice->count('warning');
$tips = $report->advice->count('tip');

$items = [
    'microformats' => ['Microformats', $total === 1 ? '1 found' : "{$total} found"],
    'indieweb' => ['IndieWeb & rels', "{$passing} of " . count($checks) . ' checks pass'],
    'metadata' => ['Metadata', $feeds === 1 ? 'head tags, 1 feed' : "head tags, {$feeds} feeds"],
    'advice' => ['Advice', "{$errors} err · {$warnings} warn · {$tips} tips"],
    'json' => ['Raw JSON', 'canonical mf2 and more'],
];
?>
<nav class="bar" aria-label="Report sections">
    <?php foreach ($items as $anchor => [$name, $label]): ?>
        <a href="#<?= e($anchor) ?>"><span class="bar-label"><?= e($label) ?></span><strong><?= e($name) ?></strong></a>
    <?php endforeach ?>
</nav>
