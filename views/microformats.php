<?php

use Indiechecker\Mf2Parser;
use Indiechecker\Vocabulary;

use function Indiechecker\e;
use function Indiechecker\shorten;
use function Indiechecker\view;

$mf = $report->microformats;
$items = $mf->parsed['items'];
$openLimit = 6;
?>
<section id="microformats" class="section" aria-labelledby="microformats-title">
    <h2 id="microformats-title">Microformats</h2>

    <?php if ($mf->isEmpty() && !$mf->classic): ?>
        <p class="empty">Nothing here yet. The advice below has an h-card you can start with.</p>
    <?php else: ?>
        <?php if ($mf->counts()): ?>
            <ul class="pills" aria-label="Types found">
                <?php foreach ($mf->counts() as $type => $count): $status = Vocabulary::status($type); $spec = Vocabulary::specUrl($type); ?>
                    <li>
                        <?php if ($spec): ?><a class="type" href="<?= e($spec) ?>" title="<?= e(Vocabulary::type($type)['about']) ?>"><?php else: ?><span class="type"><?php endif ?>
                        <?= e($type) ?> <span class="count"><?= $count ?></span>
                        <?= $spec ? '</a>' : '</span>' ?>
                        <?php if ($status !== 'stable'): ?><span class="badge badge-<?= e($status) ?>"><?= e($status) ?></span><?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <?php if ($mf->classic): ?>
            <h3>Classic microformats</h3>
            <p>These use the pre-2010 class names. mf2 parsers read most of them through backward compatibility and show them as their mf2 equivalents below, marked “from”.</p>
            <div class="table-wrap">
                <table>
                    <thead><tr><th scope="col">Class</th><th scope="col">Format</th><th scope="col">Elements</th><th scope="col">mf2 equivalent</th></tr></thead>
                    <tbody>
                    <?php foreach ($mf->classic as $class => $found): $root = Vocabulary::CLASSIC_ROOTS[$class]; ?>
                        <tr>
                            <td><code><?= e($class) ?></code></td>
                            <td><a href="<?= e(Vocabulary::classicSpecUrl($class)) ?>"><?= e($root['name']) ?></a></td>
                            <td class="num"><?= $found['count'] ?><?php if ($found['alongsideMf2']): ?> <span class="muted">(<?= $found['alongsideMf2'] ?> also mf2)</span><?php endif ?></td>
                            <td><?= $root['mf2'] ? '<code>' . e($root['mf2']) . '</code>' : '<span class="muted">none</span>' ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>

        <?php if ($items): ?>
            <h3>What the parser sees</h3>
            <?php foreach ($items as $index => $item):
                $name = $item['properties']['name'][0] ?? $item['properties']['url'][0] ?? null;
                $name = is_array($name) ? ($name['value'] ?? null) : $name;
            ?>
                <details class="mf-root"<?= $index < $openLimit ? ' open' : '' ?>>
                    <summary>
                        <?php foreach ($item['type'] as $type): ?><span class="type"><?= e($type) ?></span><?php endforeach ?>
                        <?php foreach ($item[Mf2Parser::CLASSIC_KEY] ?? [] as $class): ?><span class="badge badge-classic">from <?= e($class) ?></span><?php endforeach ?>
                        <?php if (is_string($name) && trim($name) !== ''): ?><span class="mf-name"><?= e(shorten($name, 90)) ?></span><?php endif ?>
                    </summary>
                    <?= view('item', ['item' => $item, 'depth' => 0, 'showHead' => false]) ?>
                </details>
            <?php endforeach ?>
            <?php if (count($items) > $openLimit): ?>
                <p class="muted"><?= count($items) - $openLimit ?> more top-level item<?= count($items) - $openLimit > 1 ? 's are' : ' is' ?> collapsed.</p>
            <?php endif ?>
        <?php endif ?>
    <?php endif ?>
</section>
