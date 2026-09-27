<?php

use function Indiechecker\e;

$items = $report->advice->all();
$labels = ['error' => 'Error', 'warning' => 'Warning', 'tip' => 'Tip'];
$whereLimit = 8;
?>
<section id="advice" class="section" aria-labelledby="advice-title">
    <h2 id="advice-title">Advice</h2>

    <?php if (!$items): ?>
        <p class="empty">Nothing to fix. This page is in great shape.</p>
    <?php else: ?>
        <p>Errors break parsing, warnings leave something out that tools expect, and tips are nice to have. Each one comes with HTML you can adapt.</p>
        <?php foreach ($items as $item): ?>
            <article class="advice advice-<?= e($item['level']) ?>">
                <header>
                    <span class="level level-<?= e($item['level']) ?>"><svg class="icon"><use href="#i-<?= e($item['level']) ?>"/></svg><?= e($labels[$item['level']]) ?></span>
                    <h3><?= e($item['title']) ?> <span class="area"><?= e($item['area']) ?></span></h3>
                </header>
                <p><?= e($item['detail']) ?></p>

                <?php if ($item['where']): ?>
                    <ul class="where">
                        <?php foreach (array_slice($item['where'], 0, $whereLimit) as $where): ?>
                            <li><code><?= e($where) ?></code></li>
                        <?php endforeach ?>
                        <?php if (count($item['where']) > $whereLimit): ?>
                            <li class="muted">and <?= count($item['where']) - $whereLimit ?> more</li>
                        <?php endif ?>
                    </ul>
                <?php endif ?>

                <?php if ($item['fix']): ?>
                    <pre class="code fix"><code><?= e($item['fix']) ?></code></pre>
                <?php endif ?>
            </article>
        <?php endforeach ?>
    <?php endif ?>
</section>
