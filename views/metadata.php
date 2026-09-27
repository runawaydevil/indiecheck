<?php

use function Indiechecker\e;
use function Indiechecker\is_absolute_url;
use function Indiechecker\link_to;

$meta = $report->metadata;
$preview = $meta->preview();
$checks = array_column($report->feedChecks, null, 'url');
$head = [
    'title' => $meta->title,
    'description' => $meta->description,
    'lang' => $meta->lang,
    'charset' => $meta->charset ?? $report->response?->charset(),
    'viewport' => $meta->viewport,
    'canonical' => $meta->canonical,
    'manifest' => $meta->manifest,
    'generator' => $meta->generator,
    'robots' => $meta->robots,
];
$missing = '<span class="missing"><svg class="icon"><use href="#i-missing"/></svg>not set</span>';
?>
<section id="metadata" class="section" aria-labelledby="metadata-title">
    <h2 id="metadata-title">Metadata</h2>

    <div class="meta-top">
        <figure class="share">
            <div class="share-card">
                <?php if ($preview['image'] && is_absolute_url($preview['image'])): ?>
                    <img src="<?= e($preview['image']) ?>" alt="<?= e($preview['imageAlt'] ?? '') ?>" loading="lazy" referrerpolicy="no-referrer">
                <?php else: ?>
                    <div class="share-noimage">No og:image</div>
                <?php endif ?>
                <div class="share-text">
                    <?php if ($preview['host'] || $preview['site']): ?><p class="share-host"><?= e($preview['site'] ?? $preview['host']) ?></p><?php endif ?>
                    <p class="share-title"><?= e($preview['title'] ?? 'No title') ?></p>
                    <?php if ($preview['description']): ?><p class="share-description"><?= e($preview['description']) ?></p><?php endif ?>
                </div>
            </div>
            <figcaption>Roughly how a shared link looks in chat apps and on Mastodon.</figcaption>
        </figure>

        <dl class="facts-list">
            <?php foreach ($head as $label => $value): ?>
                <div>
                    <dt><?= e($label) ?></dt>
                    <dd><?php if ($value === null || $value === ''): ?><?= $missing ?><?php elseif (in_array($label, ['canonical', 'manifest'], true)): ?><?= link_to($value) ?><?php else: ?><?= e($value) ?><?php endif ?></dd>
                </div>
            <?php endforeach ?>
            <div>
                <dt>theme-color</dt>
                <dd>
                    <?php if (!$meta->themeColors): ?><?= $missing ?><?php endif ?>
                    <?php foreach ($meta->themeColors as $theme): ?>
                        <span class="swatch"><svg width="14" height="14" aria-hidden="true"><rect width="14" height="14" rx="3" fill="<?= e($theme['color']) ?>"/></svg><code><?= e($theme['color']) ?></code><?php if ($theme['media']): ?> <span class="muted"><?= e($theme['media']) ?></span><?php endif ?></span>
                    <?php endforeach ?>
                </dd>
            </div>
            <div>
                <dt>fediverse:creator</dt>
                <dd><?= $meta->fediverseCreators ? e(implode(', ', $meta->fediverseCreators)) : $missing ?></dd>
            </div>
        </dl>
    </div>

    <?php foreach (['Open Graph' => $meta->openGraph, 'Twitter card' => $meta->twitter] as $title => $tags): ?>
        <h3><?= e($title) ?></h3>
        <?php if (!$tags): ?>
            <p class="empty">None.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tbody>
                    <?php foreach ($tags as $property => $values): ?>
                        <?php foreach ($values as $value): ?>
                            <tr><th scope="row"><code><?= e($property) ?></code></th><td><?= is_absolute_url($value) ? link_to($value) : e($value) ?></td></tr>
                        <?php endforeach ?>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    <?php endforeach ?>

    <h3>Feeds</h3>
    <?php if (!$meta->feeds): ?>
        <p class="empty">No feed links in the head.<?php if ($report->microformats->ofType('h-feed')): ?> The page has an h-feed, which microformats readers can follow.<?php endif ?></p>
    <?php else: ?>
        <ul class="checklist">
            <?php foreach ($meta->feeds as $feed): $check = $checks[$feed['url']] ?? null; ?>
                <li class="<?= $check === null ? 'is-unknown' : ($check['ok'] ? 'is-ok' : 'is-bad') ?>">
                    <span class="mark"><svg class="icon"><use href="#<?= $check === null ? 'i-missing' : ($check['ok'] ? 'i-check' : 'i-error') ?>"/></svg></span>
                    <div>
                        <strong><?= e($feed['format']) ?></strong><?php if ($feed['title']): ?> <span class="muted"><?= e($feed['title']) ?></span><?php endif ?>
                        <span class="detail"><?= link_to($feed['url']) ?></span>
                        <span class="detail"><?php if ($check === null): ?>Not checked.<?php elseif ($check['ok']): ?>Loads fine, served as <code><?= e($check['content-type']) ?></code>.<?php else: ?><?= e($check['problem']) ?><?php endif ?></span>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>

    <h3>Icons</h3>
    <?php if (!$meta->icons): ?>
        <p class="empty">No icon links. Browsers will try <code>/favicon.ico</code>.</p>
    <?php else: ?>
        <ul class="icons">
            <?php foreach ($meta->icons as $icon): ?>
                <li>
                    <span class="icon-frame"><?php if (is_absolute_url($icon['url'])): ?><img src="<?= e($icon['url']) ?>" alt="" loading="lazy" referrerpolicy="no-referrer"><?php endif ?></span>
                    <span>
                        <code><?= e($icon['kind']) ?></code><?php if ($icon['sizes']): ?> <span class="muted"><?= e($icon['sizes']) ?></span><?php endif ?><?php if ($icon['type']): ?> <span class="muted"><?= e($icon['type']) ?></span><?php endif ?>
                        <br><?= link_to($icon['url']) ?>
                    </span>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
</section>
