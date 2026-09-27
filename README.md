# indiechecker

Point it at a web page and it lists every microformat, rel link, IndieWeb endpoint and meta tag it finds there, then tells you what to fix, with the HTML to fix it.

Think [Lens](https://lens.rknight.me/), but for [microformats](https://microformats.org/wiki/Main_Page). You can give it a URL, paste some HTML, or ask the API for JSON.

## What it reads

**microformats2.** The vocabularies on the wiki, stable and draft: h-card, h-entry, h-feed, h-event, h-cite, h-adr, h-geo, h-product, h-recipe, h-review, h-review-aggregate, h-resume, h-item, h-listing, h-measure, h-app, h-breadcrumb, h-org and h-food. Anything under `h-x-` shows up as experimental. Types nobody has heard of still get parsed, with a note saying so.

**Classic microformats.** The pre-2010 class names: vcard, adr, geo, hentry, hfeed, hnews, vevent, vcalendar, hreview, hreview-aggregate, hrecipe, hproduct, hresume, hlisting, hmedia, haudio and xoxo. Plus rel-tag, rel-license, rel-nofollow, XFN and VoteLinks. Items that came from classic markup are marked with the class they came from.

**IndieWeb.** The representative h-card, who wrote each h-entry (following the authorship rules: the entry, then the parent h-feed, then rel=author), rel=me, Webmention, Micropub, Microsub, IndieAuth and WebSub. Endpoints are read from the HTML and from the HTTP `Link` header, which is where some sites keep them.

**Meta tags.** Title, description, lang, charset, viewport, canonical, Open Graph, Twitter cards, `fediverse:creator`, theme-color, icons, the manifest and feeds. Declared feeds get requested, so a feed that answers 401 or comes back as `text/plain` gets flagged.

**Advice.** Errors break parsing, like a `dt-published` that isn't a date or a class called `hcard`. Warnings leave out something tools look for, like an h-entry without an author. Tips are nice to have. Each one says where the problem is and comes with a snippet.

## Running it

You need PHP 8.2 or newer with the curl, dom and mbstring extensions, and Composer.

```bash
composer install
php -S 127.0.0.1:8123 -t public
```

Then open http://127.0.0.1:8123.

To put it online, point the web server's document root at `public/`. Nothing outside that folder should be reachable from the web.

## JSON API

Add `format=json` to any check:

```bash
curl "http://127.0.0.1:8123/?url=https://tantek.com/&format=json"
```

Or POST the HTML itself, with an optional `base` URL for resolving relative links:

```bash
curl -X POST http://127.0.0.1:8123/ \
  --data-urlencode "html@page.html" \
  -d "base=https://example.com/post" \
  -d "format=json"
```

The canonical mf2 parse is under `mf2`. The rest of the response is what the page shows: `summary`, `indieweb`, `rels`, `metadata`, `feed-checks` and `advice`. A failed fetch answers 422 with `{"error": "..."}`.

## Fetching

The checker requests pages from the server it runs on, so it's careful about where it goes:

- only http and https, on ports 80, 443, 8080 and 8443
- no private, loopback, link-local or otherwise reserved addresses, checked again after every redirect, and cURL is pinned to the IP that passed the check
- at most 5 redirects, 2 MB and 20 seconds per page
- up to 4 feeds per page, with 8 seconds each (only the headers are read)

A site on your own network can't be fetched. Paste its HTML instead.

## Where things are

- `public/index.php` takes the request and answers with a page or JSON
- `src/Fetcher.php` does the HTTP requests and the address checks
- `src/Mf2Parser.php` extends php-mf2 so that top-level `geo`, `hreview-aggregate` and `hnews` get parsed, and marks items that came from classic markup
- `src/Microformats.php`, `src/Rels.php` and `src/Metadata.php` read the page
- `src/Advice.php` has every rule behind the advice list
- `src/Vocabulary.php` lists the vocabularies, their properties and the known rel values
- `views/` holds the PHP templates

## Credits

Parsing by [php-mf2](https://github.com/microformats/php-mf2). The layout follows the [Minimal](https://github.com/orderedlist/minimal) GitHub Pages theme by Steve Smith, rewritten from scratch with darker link and label colors so the text passes contrast checks. Type is [Noto Sans](https://fonts.google.com/noto/specimen/Noto+Sans) and [IBM Plex Mono](https://github.com/IBM/plex), both under the SIL Open Font License and served from `public/fonts/`.
