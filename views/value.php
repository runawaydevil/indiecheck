<?php

use function Indiechecker\e;
use function Indiechecker\is_absolute_url;
use function Indiechecker\link_to;
use function Indiechecker\looks_like_image;
use function Indiechecker\shorten;
use function Indiechecker\view;

$imageProperty = in_array($name, ['photo', 'logo', 'featured'], true);
?>
<?php if (is_array($value) && isset($value['type'])): ?>
    <div class="value value-nested">
        <?php if (isset($value['value']) && is_string($value['value']) && $value['value'] !== ''): ?>
            <p class="value-text"><?= e(shorten($value['value'], 200)) ?></p>
        <?php endif ?>
        <?= view('item', ['item' => $value, 'depth' => $depth + 1, 'showHead' => true]) ?>
    </div>
<?php elseif (is_array($value) && isset($value['html'])): ?>
    <div class="value value-e">
        <?php $text = shorten((string) $value['value'], 400); ?>
        <p><?= $text === '' ? '<span class="muted">(empty)</span>' : e($text) ?></p>
        <?php if (trim($value['html']) !== ''): ?>
            <details>
                <summary>HTML</summary>
                <pre class="code"><code><?= e($value['html']) ?></code></pre>
            </details>
        <?php endif ?>
    </div>
<?php elseif (is_array($value) && isset($value['value'])): ?>
    <div class="value value-image">
        <?php if (is_absolute_url($value['value'])): ?>
            <img src="<?= e($value['value']) ?>" alt="<?= e($value['alt'] ?? '') ?>" loading="lazy" referrerpolicy="no-referrer">
        <?php endif ?>
        <p><?= link_to($value['value']) ?><?php if (isset($value['alt'])): ?><br><span class="muted">alt:</span> <?= $value['alt'] === '' ? '<span class="muted">(empty)</span>' : e($value['alt']) ?><?php endif ?></p>
    </div>
<?php elseif (is_string($value) && is_absolute_url($value)): ?>
    <div class="value<?= $imageProperty || looks_like_image($value) ? ' value-image' : '' ?>">
        <?php if ($imageProperty || looks_like_image($value)): ?>
            <img src="<?= e($value) ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
        <?php endif ?>
        <p><?= link_to($value) ?></p>
    </div>
<?php else: ?>
    <p class="value"><?= trim((string) $value) === '' ? '<span class="muted">(empty)</span>' : e(shorten((string) $value, 500)) ?></p>
<?php endif ?>
