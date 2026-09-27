<?php

use Indiechecker\Vocabulary;

use function Indiechecker\e;
?>
<section class="intro">
    <h2>What’s on your page?</h2>
    <p class="lede">Give it a URL and indiechecker reads the page the way IndieWeb tools do. It lists every microformat, old and new, every rel link and endpoint, the meta tags behind link previews, and tells you what to fix, with the HTML to fix it.</p>

    <p class="try">Try <a href="?url=https://tantek.com/">tantek.com</a>, <a href="?url=https://aaronparecki.com/">aaronparecki.com</a> or <a href="?url=https://microformats.org/wiki/Main_Page">microformats.org</a>.</p>

    <h3>It reads</h3>
    <dl class="reads">
        <div>
            <dt>microformats2</dt>
            <dd class="pills">
                <?php foreach (Vocabulary::TYPES as $type => $info): ?>
                    <a class="type" href="<?= e(Vocabulary::specUrl($type)) ?>" title="<?= e($info['about']) ?>"><?= e($type) ?></a>
                <?php endforeach ?>
                <span class="more">and any h-x- experiment</span>
            </dd>
        </div>
        <div>
            <dt>Classic microformats</dt>
            <dd class="pills">
                <?php foreach (Vocabulary::CLASSIC_ROOTS as $class => $info): ?>
                    <a class="type type-classic" href="<?= e(Vocabulary::classicSpecUrl($class)) ?>" title="<?= e($info['name']) ?>"><?= e($class) ?></a>
                <?php endforeach ?>
                <span class="type type-classic">rel-tag</span>
                <span class="type type-classic">rel-license</span>
                <span class="type type-classic">rel-nofollow</span>
                <span class="type type-classic">XFN</span>
                <span class="type type-classic">VoteLinks</span>
            </dd>
        </div>
        <div>
            <dt>IndieWeb</dt>
            <dd>Representative h-card, post authorship, rel=me, Webmention, Micropub, Microsub, IndieAuth and WebSub, from the HTML and from HTTP Link headers.</dd>
        </div>
        <div>
            <dt>Meta tags</dt>
            <dd>Title, description, Open Graph, Twitter cards, fediverse:creator, feeds (and whether they load), icons, theme color and the rest of the head.</dd>
        </div>
    </dl>
</section>
