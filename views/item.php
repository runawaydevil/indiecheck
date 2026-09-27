<?php

use Indiechecker\Mf2Parser;
use Indiechecker\Vocabulary;

use function Indiechecker\e;
use function Indiechecker\view;

$types = $item['type'];
?>
<div class="mf<?= $depth > 0 ? ' mf-nested' : '' ?>">
    <?php if ($showHead): ?>
        <p class="mf-head">
            <?php foreach ($types as $type): $spec = Vocabulary::specUrl($type); ?>
                <?php if ($spec): ?><a class="type" href="<?= e($spec) ?>"><?= e($type) ?></a><?php else: ?><span class="type"><?= e($type) ?></span><?php endif ?>
            <?php endforeach ?>
            <?php foreach ($item[Mf2Parser::CLASSIC_KEY] ?? [] as $class): ?><span class="badge badge-classic">from <?= e($class) ?></span><?php endforeach ?>
        </p>
    <?php endif ?>

    <?php if (isset($item['id']) || isset($item['lang'])): ?>
        <p class="mf-attrs">
            <?php if (isset($item['id'])): ?><code>id="<?= e($item['id']) ?>"</code><?php endif ?>
            <?php if (isset($item['lang'])): ?><code>lang="<?= e($item['lang']) ?>"</code><?php endif ?>
        </p>
    <?php endif ?>

    <?php if ($item['properties']): ?>
        <dl class="props">
            <?php foreach ($item['properties'] as $name => $values): ?>
                <div class="prop">
                    <dt><code><span class="prefix"><?= e(Vocabulary::prefix($types, $name, $values[0])) ?>-</span><?= e($name) ?></code></dt>
                    <dd>
                        <?php foreach ($values as $value): ?>
                            <?= view('value', ['value' => $value, 'name' => $name, 'depth' => $depth]) ?>
                        <?php endforeach ?>
                    </dd>
                </div>
            <?php endforeach ?>
        </dl>
    <?php else: ?>
        <p class="muted">No properties.</p>
    <?php endif ?>

    <?php if (!empty($item['children'])): ?>
        <div class="children">
            <p class="children-label"><?= count($item['children']) ?> child item<?= count($item['children']) > 1 ? 's' : '' ?></p>
            <?php foreach ($item['children'] as $child): ?>
                <?= view('item', ['item' => $child, 'depth' => $depth + 1, 'showHead' => true]) ?>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
