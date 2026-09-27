<?php

use function Indiechecker\e;
use function Indiechecker\link_to;
use function Indiechecker\view;

$response = $report->response;
$mf = $report->microformats;
$counts = $mf->counts();
$total = count($mf->all);
$classicTotal = array_sum(array_column($mf->classic, 'count'));
$errors = $report->advice->count('error');
$warnings = $report->advice->count('warning');
$tips = $report->advice->count('tip');
?>
<section class="summary" aria-labelledby="summary-title">
    <h2 id="summary-title" class="visually-hidden">Summary</h2>
    <p class="checked">
        <?php if ($report->url()): ?>
            <?= $pasted ? 'Pasted HTML for' : 'Checked' ?> <?= link_to($report->url()) ?>
        <?php else: ?>
            Pasted HTML, no page URL
        <?php endif ?>
    </p>

    <?php if ($response): ?>
        <p class="facts">
            <span>HTTP <?= e($response->status) ?></span>
            <span><?= e(number_format(strlen($response->body) / 1024, 1)) ?> KB</span>
            <span><?= e(number_format($response->milliseconds / 1000, 2)) ?> s</span>
            <?php if ($response->redirects): ?>
                <span><?= count($response->redirects) ?> redirect<?= count($response->redirects) > 1 ? 's' : '' ?> from <?= link_to($response->requestedUrl) ?></span>
            <?php endif ?>
            <a href="?url=<?= e(rawurlencode($report->url())) ?>&amp;format=json">JSON</a>
        </p>
    <?php endif ?>

    <p class="tally">
        <span>
            <?php if ($total === 0): ?>
                No microformats found.
            <?php else: ?>
                Found <strong><?= $total ?></strong> microformat<?= $total > 1 ? 's' : '' ?> of <?= count($counts) ?> type<?= count($counts) > 1 ? 's' : '' ?><?php if ($classicTotal): ?>, <?= $classicTotal ?> elements with classic class names<?php endif ?>.
            <?php endif ?>
        </span>
        <a class="level level-error<?= $errors === 0 ? ' level-zero' : '' ?>" href="#advice"><svg class="icon"><use href="#i-error"/></svg><?= $errors ?> error<?= $errors === 1 ? '' : 's' ?></a>
        <a class="level level-warning<?= $warnings === 0 ? ' level-zero' : '' ?>" href="#advice"><svg class="icon"><use href="#i-warning"/></svg><?= $warnings ?> warning<?= $warnings === 1 ? '' : 's' ?></a>
        <a class="level level-tip<?= $tips === 0 ? ' level-zero' : '' ?>" href="#advice"><svg class="icon"><use href="#i-tip"/></svg><?= $tips ?> tip<?= $tips === 1 ? '' : 's' ?></a>
    </p>
</section>

<?= view('microformats', ['report' => $report]) ?>
<?= view('indieweb', ['report' => $report]) ?>
<?= view('metadata', ['report' => $report]) ?>
<?= view('advice', ['report' => $report]) ?>

<section id="json" class="section" aria-labelledby="json-title">
    <h2 id="json-title">Raw JSON</h2>
    <p>The canonical mf2 parse is under <code>mf2</code>. Everything else is what this page shows.
        <?php if ($report->url() && !$pasted): ?>Get it straight from <code>?url=…&amp;format=json</code>.<?php else: ?>POST the HTML with <code>format=json</code> to get it from the API.<?php endif ?></p>
    <details class="json">
        <summary>Show JSON</summary>
        <pre class="code"><code><?= e(json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)) ?></code></pre>
    </details>
</section>
