<?php

use function Indiechecker\e;
use function Indiechecker\first_text;
use function Indiechecker\is_absolute_url;
use function Indiechecker\link_to;
use function Indiechecker\shorten;

$mf = $report->microformats;
$card = $mf->representativeCard['card'] ?? null;
$entries = $mf->ofType('h-entry');
$entryLimit = 15;
$linkLimit = 5;
?>
<section id="indieweb" class="section" aria-labelledby="indieweb-title">
    <h2 id="indieweb-title">IndieWeb &amp; rels</h2>

    <ul class="checklist">
        <?php foreach ($report->indieWeb() as $check): ?>
            <li class="<?= $check['ok'] ? 'is-ok' : 'is-missing' ?>">
                <span class="mark"><svg class="icon"><use href="#<?= $check['ok'] ? 'i-check' : 'i-missing' ?>"/></svg><span class="visually-hidden"><?= $check['ok'] ? 'Yes' : 'No' ?>:</span></span>
                <div>
                    <strong><?= e($check['label']) ?></strong>
                    <span class="detail"><?= e($check['detail']) ?></span>
                    <?php if (!empty($check['links'])): ?>
                        <ul class="links">
                            <?php foreach (array_slice($check['links'], 0, $linkLimit) as $link): ?><li><?= link_to($link) ?></li><?php endforeach ?>
                            <?php if (count($check['links']) > $linkLimit): ?>
                                <li class="muted">and <?= count($check['links']) - $linkLimit ?> more, listed under <a href="#rel-links">rel links</a></li>
                            <?php endif ?>
                        </ul>
                    <?php endif ?>
                </div>
            </li>
        <?php endforeach ?>
    </ul>

    <?php if ($card): ?>
        <h3>Representative h-card</h3>
        <?php $photo = first_text($card, 'photo'); $url = first_text($card, 'url'); $note = first_text($card, 'note'); ?>
        <div class="person">
            <?php if ($photo && is_absolute_url($photo)): ?>
                <img src="<?= e($photo) ?>" alt="" width="64" height="64" loading="lazy" referrerpolicy="no-referrer">
            <?php endif ?>
            <div>
                <p class="person-name"><?= e(first_text($card, 'name') ?? '(no name)') ?></p>
                <?php if ($url): ?><p><?= link_to($url) ?></p><?php endif ?>
                <?php if ($note): ?><p class="muted"><?= e(shorten($note, 200)) ?></p><?php endif ?>
                <p class="muted">Chosen because <?= e($mf->representativeCard['reason']) ?>.</p>
            </div>
        </div>
    <?php endif ?>

    <?php if ($entries): ?>
        <h3>Posts and their authors</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Post</th><th scope="col">Published</th><th scope="col">Author</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($entries, 0, $entryLimit) as $found):
                    $entry = $found['item'];
                    $label = first_text($entry, 'name') ?? first_text($entry, 'summary') ?? first_text($entry, 'content') ?? $found['path'];
                    $author = $mf->authorOf($found);
                    $authorName = null;
                    if ($author) {
                        $authorName = is_array($author['author'])
                            ? (first_text($author['author'], 'name') ?? first_text($author['author'], 'url') ?? ($author['author']['value'] ?? null))
                            : $author['author'];
                    }
                ?>
                    <tr>
                        <td>
                            <?php $permalink = first_text($entry, 'url'); ?>
                            <?= $permalink ? link_to($permalink, shorten($label, 80)) : e(shorten($label, 80)) ?>
                        </td>
                        <td class="num"><?= e(first_text($entry, 'published') ?? '—') ?></td>
                        <td>
                            <?php if ($author): ?>
                                <?= e(shorten((string) $authorName, 60)) ?> <span class="muted">(<?= e($author['source']) ?>)</span>
                            <?php else: ?>
                                <span class="level level-warning"><svg class="icon"><use href="#i-warning"/></svg>none</span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php if (count($entries) > $entryLimit): ?>
            <p class="muted">Showing <?= $entryLimit ?> of <?= count($entries) ?> posts.</p>
        <?php endif ?>
    <?php endif ?>

    <h3 id="rel-links">rel links</h3>
    <?php if ($report->rels->count() === 0): ?>
        <p class="empty">No rel links in the HTML or the Link headers.</p>
    <?php endif ?>
    <?php foreach ($report->rels->grouped() as $group => $rels): ?>
        <h4><?= e($group) ?></h4>
        <div class="table-wrap">
            <table class="rels">
                <thead><tr><th scope="col">rel</th><th scope="col">Links</th></tr></thead>
                <tbody>
                <?php foreach ($rels as $rel => $info): ?>
                    <tr>
                        <td>
                            <code><?= e($rel) ?></code>
                            <?php if ($info['about']): ?><span class="about"><?= e($info['about']) ?></span><?php endif ?>
                        </td>
                        <td>
                            <ul class="links">
                                <?php foreach ($info['links'] as $link): ?>
                                    <li>
                                        <?= link_to($link['url'], $link['url'] === '' ? '(this page)' : null) ?>
                                        <?php if ($link['source'] !== 'HTML'): ?><span class="badge badge-header"><?= e($link['source']) ?></span><?php endif ?>
                                        <?php if ($link['text'] !== null && trim($link['text']) !== '' && trim($link['text']) !== $link['url']): ?><span class="muted">“<?= e(shorten($link['text'], 60)) ?>”</span><?php endif ?>
                                    </li>
                                <?php endforeach ?>
                            </ul>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endforeach ?>

    <?php if ($report->rels->xfn()): ?>
        <h3>XFN relationships</h3>
        <ul class="plain">
            <?php foreach ($report->rels->xfn() as $person): ?>
                <li><?= link_to($person['url'], $person['text'] ? shorten($person['text'], 60) : null) ?> <span class="muted"><?= e(implode(', ', $person['relations'])) ?></span></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>

    <?php if ($mf->voteLinks): ?>
        <h3>VoteLinks</h3>
        <ul class="plain">
            <?php foreach ($mf->voteLinks as $vote): ?>
                <li><code><?= e($vote['vote']) ?></code> <?= link_to($vote['url'], $vote['text'] ?: null) ?></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
</section>
