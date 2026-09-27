<?php

namespace Indiechecker;

final class Vocabulary
{
    private const WIKI = 'https://microformats.org/wiki/';

    public const TYPES = [
        'h-card' => [
            'about' => 'A person, organization or venue.',
            'status' => 'stable',
            'properties' => [
                'name' => 'p', 'honorific-prefix' => 'p', 'given-name' => 'p', 'additional-name' => 'p',
                'family-name' => 'p', 'sort-string' => 'p', 'honorific-suffix' => 'p', 'nickname' => 'p',
                'email' => 'u', 'logo' => 'u', 'photo' => 'u', 'url' => 'u', 'uid' => 'u', 'category' => 'p',
                'adr' => 'p', 'post-office-box' => 'p', 'extended-address' => 'p', 'street-address' => 'p',
                'locality' => 'p', 'region' => 'p', 'postal-code' => 'p', 'country-name' => 'p', 'label' => 'p',
                'geo' => 'p', 'latitude' => 'p', 'longitude' => 'p', 'altitude' => 'p', 'tel' => 'p', 'note' => 'p',
                'bday' => 'dt', 'key' => 'u', 'org' => 'p', 'job-title' => 'p', 'role' => 'p', 'impp' => 'u',
                'sex' => 'p', 'gender-identity' => 'p', 'anniversary' => 'dt', 'organization-name' => 'p',
                'organization-unit' => 'p', 'tz' => 'p', 'rev' => 'dt', 'pronoun' => 'p', 'pronouns' => 'p',
            ],
        ],
        'h-entry' => [
            'about' => 'A post: an article, note, reply, like, photo and so on.',
            'status' => 'stable',
            'properties' => [
                'name' => 'p', 'summary' => 'p', 'content' => 'e', 'published' => 'dt', 'updated' => 'dt',
                'author' => 'p', 'category' => 'p', 'url' => 'u', 'uid' => 'u', 'location' => 'p',
                'syndication' => 'u', 'in-reply-to' => 'u', 'rsvp' => 'p', 'like-of' => 'u', 'repost-of' => 'u',
                'bookmark-of' => 'u', 'quotation-of' => 'u', 'follow-of' => 'u', 'tag-of' => 'u', 'comment' => 'p',
                'like' => 'p', 'repost' => 'p', 'photo' => 'u', 'video' => 'u', 'audio' => 'u', 'featured' => 'u',
                'checkin' => 'p', 'listen-of' => 'u', 'watch-of' => 'u', 'read-of' => 'u', 'read-status' => 'p',
                'post-status' => 'p', 'content-warning' => 'p', 'invitee' => 'p', 'ate' => 'p', 'drank' => 'p',
                'latitude' => 'p', 'longitude' => 'p', 'altitude' => 'p', 'audience' => 'p', 'visibility' => 'p',
            ],
        ],
        'h-feed' => [
            'about' => 'A stream of h-entry posts.',
            'status' => 'stable',
            'properties' => [
                'name' => 'p', 'author' => 'p', 'url' => 'u', 'photo' => 'u', 'summary' => 'p', 'uid' => 'u',
                'featured' => 'u',
            ],
        ],
        'h-event' => [
            'about' => 'An event, with a start and usually an end.',
            'status' => 'stable',
            'properties' => [
                'name' => 'p', 'summary' => 'p', 'start' => 'dt', 'end' => 'dt', 'duration' => 'dt',
                'description' => 'p', 'url' => 'u', 'category' => 'p', 'location' => 'p', 'attendee' => 'p',
                'content' => 'e', 'published' => 'dt', 'updated' => 'dt', 'author' => 'p', 'photo' => 'u',
                'featured' => 'u', 'uid' => 'u', 'organizer' => 'p',
            ],
        ],
        'h-cite' => [
            'about' => 'A citation of something else: a quoted post, a paper, a page.',
            'status' => 'draft',
            'properties' => [
                'name' => 'p', 'published' => 'dt', 'author' => 'p', 'url' => 'u', 'uid' => 'u',
                'publication' => 'p', 'accessed' => 'dt', 'content' => 'p', 'photo' => 'u',
            ],
        ],
        'h-adr' => [
            'about' => 'A physical address.',
            'status' => 'stable',
            'properties' => [
                'post-office-box' => 'p', 'extended-address' => 'p', 'street-address' => 'p', 'locality' => 'p',
                'region' => 'p', 'postal-code' => 'p', 'country-name' => 'p', 'label' => 'p', 'geo' => 'p',
                'latitude' => 'p', 'longitude' => 'p', 'altitude' => 'p', 'name' => 'p',
            ],
        ],
        'h-geo' => [
            'about' => 'Latitude and longitude.',
            'status' => 'stable',
            'properties' => ['latitude' => 'p', 'longitude' => 'p', 'altitude' => 'p', 'name' => 'p'],
        ],
        'h-product' => [
            'about' => 'A product for sale or review.',
            'status' => 'stable',
            'properties' => [
                'name' => 'p', 'photo' => 'u', 'brand' => 'p', 'category' => 'p', 'description' => 'e',
                'url' => 'u', 'identifier' => 'u', 'review' => 'p', 'price' => 'p',
            ],
        ],
        'h-recipe' => [
            'about' => 'A recipe: ingredients and instructions.',
            'status' => 'stable',
            'properties' => [
                'name' => 'p', 'ingredient' => 'p', 'yield' => 'p', 'instructions' => 'e', 'duration' => 'dt',
                'photo' => 'u', 'summary' => 'p', 'author' => 'p', 'published' => 'dt', 'nutrition' => 'p',
                'category' => 'p', 'url' => 'u',
            ],
        ],
        'h-review' => [
            'about' => 'A review of a product, place, event or anything else.',
            'status' => 'stable',
            'properties' => [
                'name' => 'p', 'item' => 'p', 'author' => 'p', 'published' => 'dt', 'rating' => 'p', 'best' => 'p',
                'worst' => 'p', 'content' => 'e', 'category' => 'p', 'url' => 'u',
            ],
        ],
        'h-review-aggregate' => [
            'about' => 'The combined result of many reviews.',
            'status' => 'stable',
            'properties' => [
                'item' => 'p', 'average' => 'p', 'best' => 'p', 'worst' => 'p', 'count' => 'p', 'votes' => 'p',
                'name' => 'p', 'rating' => 'p', 'category' => 'p', 'url' => 'u',
            ],
        ],
        'h-resume' => [
            'about' => 'A résumé or CV.',
            'status' => 'stable',
            'properties' => [
                'name' => 'p', 'summary' => 'p', 'contact' => 'p', 'education' => 'p', 'experience' => 'p',
                'skill' => 'p', 'affiliation' => 'p', 'url' => 'u', 'photo' => 'u',
            ],
        ],
        'h-item' => [
            'about' => 'A generic thing, usually the subject of a review.',
            'status' => 'stable',
            'properties' => ['name' => 'p', 'url' => 'u', 'photo' => 'u'],
        ],
        'h-listing' => [
            'about' => 'A classified ad or listing.',
            'status' => 'draft',
            'properties' => [
                'action' => 'p', 'name' => 'p', 'lister' => 'p', 'url' => 'u', 'published' => 'dt',
                'updated' => 'dt', 'item' => 'p', 'content' => 'e', 'price' => 'p', 'location' => 'p',
            ],
        ],
        'h-measure' => [
            'about' => 'A measurement: a number and a unit.',
            'status' => 'draft',
            'properties' => ['num' => 'p', 'unit' => 'p', 'type' => 'p', 'name' => 'p'],
        ],
        'h-app' => [
            'about' => 'An application, used by IndieAuth to describe a client.',
            'status' => 'draft',
            'properties' => ['name' => 'p', 'url' => 'u', 'logo' => 'u', 'summary' => 'p', 'author' => 'p', 'photo' => 'u'],
        ],
        'h-x-app' => [
            'about' => 'The older, experimental name of h-app.',
            'status' => 'experimental',
            'properties' => ['name' => 'p', 'url' => 'u', 'logo' => 'u', 'summary' => 'p', 'author' => 'p', 'photo' => 'u'],
        ],
        'h-breadcrumb' => [
            'about' => 'One step of a breadcrumb trail.',
            'status' => 'draft',
            'properties' => ['name' => 'p', 'url' => 'u'],
        ],
        'h-org' => [
            'about' => 'An organization. Most sites use h-card for this instead.',
            'status' => 'draft',
            'properties' => ['name' => 'p', 'url' => 'u', 'photo' => 'u', 'logo' => 'u', 'unit' => 'p'],
        ],
        'h-food' => [
            'about' => 'Something eaten or drunk, used by ate and drank posts.',
            'status' => 'draft',
            'properties' => ['name' => 'p', 'url' => 'u', 'photo' => 'u'],
        ],
    ];

    public const CLASSIC_ROOTS = [
        'vcard' => ['name' => 'hCard', 'mf2' => 'h-card', 'page' => 'hcard'],
        'adr' => ['name' => 'adr', 'mf2' => 'h-adr', 'page' => 'adr'],
        'geo' => ['name' => 'geo', 'mf2' => 'h-geo', 'page' => 'geo'],
        'hentry' => ['name' => 'hAtom entry', 'mf2' => 'h-entry', 'page' => 'hatom'],
        'hfeed' => ['name' => 'hAtom feed', 'mf2' => 'h-feed', 'page' => 'hatom'],
        'hnews' => ['name' => 'hNews', 'mf2' => 'h-entry', 'page' => 'hnews'],
        'vevent' => ['name' => 'hCalendar event', 'mf2' => 'h-event', 'page' => 'hcalendar'],
        'vcalendar' => ['name' => 'hCalendar container', 'mf2' => null, 'page' => 'hcalendar'],
        'hreview' => ['name' => 'hReview', 'mf2' => 'h-review', 'page' => 'hreview'],
        'hreview-aggregate' => ['name' => 'hReview-aggregate', 'mf2' => 'h-review-aggregate', 'page' => 'hreview-aggregate'],
        'hrecipe' => ['name' => 'hRecipe', 'mf2' => 'h-recipe', 'page' => 'hrecipe'],
        'hproduct' => ['name' => 'hProduct', 'mf2' => 'h-product', 'page' => 'hproduct'],
        'hresume' => ['name' => 'hResume', 'mf2' => 'h-resume', 'page' => 'hresume'],
        'hlisting' => ['name' => 'hListing', 'mf2' => 'h-listing', 'page' => 'hlisting'],
        'hmedia' => ['name' => 'hMedia', 'mf2' => null, 'page' => 'hmedia'],
        'haudio' => ['name' => 'hAudio', 'mf2' => null, 'page' => 'haudio'],
        'xoxo' => ['name' => 'XOXO outline', 'mf2' => null, 'page' => 'xoxo'],
    ];

    public const XFN = [
        'contact', 'acquaintance', 'friend', 'met', 'co-worker', 'colleague', 'co-resident', 'neighbor',
        'child', 'parent', 'sibling', 'spouse', 'kin', 'muse', 'crush', 'date', 'sweetheart', 'me',
    ];

    public const REL_GROUPS = [
        'IndieWeb endpoints' => [
            'webmention' => 'Where to send Webmentions',
            'pingback' => 'Where to send Pingbacks (older than Webmention)',
            'micropub' => 'Micropub endpoint for posting from apps',
            'microsub' => 'Microsub endpoint for readers',
            'indieauth-metadata' => 'IndieAuth server metadata',
            'authorization_endpoint' => 'IndieAuth authorization endpoint',
            'token_endpoint' => 'IndieAuth token endpoint',
            'ticket_endpoint' => 'TicketAuth endpoint',
            'hub' => 'WebSub hub',
            'self' => 'WebSub topic URL',
        ],
        'Identity' => [
            'me' => 'Another profile of the same person',
            'author' => 'The author of the page',
            'pgpkey' => 'Public PGP key',
            'openid.server' => 'OpenID 1 server',
            'openid.delegate' => 'OpenID 1 delegate',
            'openid2.provider' => 'OpenID 2 provider',
            'openid2.local_id' => 'OpenID 2 local id',
        ],
        'Classic rel microformats' => [
            'tag' => 'rel-tag: a tag for the page',
            'license' => 'rel-license: the license of the content',
            'nofollow' => 'rel-nofollow: don’t endorse the link',
            'bookmark' => 'rel-bookmark: permalink of a post',
            'enclosure' => 'rel-enclosure: a downloadable attachment',
            'directory' => 'rel-directory: a directory that lists the page',
            'home' => 'rel-home: the home page of the site',
            'payment' => 'rel-payment: where to pay or tip',
        ],
        'Navigation' => [
            'canonical' => 'The preferred URL of the page',
            'alternate' => 'Another version: feed, translation, format',
            'prev' => 'Previous page',
            'next' => 'Next page',
            'up' => 'Parent page',
            'first' => 'First page',
            'last' => 'Last page',
            'search' => 'Search description',
            'shortlink' => 'Short URL',
            'archives' => 'Archives',
            'privacy-policy' => 'Privacy policy',
            'terms-of-service' => 'Terms of service',
            'help' => 'Help',
            'external' => 'Link to another site',
            'noopener' => 'Opens without window.opener',
            'noreferrer' => 'Opens without a Referer',
            'ugc' => 'User-generated link',
            'sponsored' => 'Paid link',
        ],
        'Page resources' => [
            'stylesheet' => 'Stylesheet',
            'icon' => 'Favicon',
            'shortcut' => 'Legacy favicon keyword',
            'apple-touch-icon' => 'Home screen icon for iOS',
            'apple-touch-icon-precomposed' => 'Older iOS home screen icon',
            'mask-icon' => 'Safari pinned tab icon',
            'manifest' => 'Web app manifest',
            'preload' => 'Preloaded resource',
            'modulepreload' => 'Preloaded JavaScript module',
            'prefetch' => 'Prefetched resource',
            'preconnect' => 'Early connection',
            'dns-prefetch' => 'Early DNS lookup',
            'prerender' => 'Prerendered page',
        ],
    ];

    public static function type(string $type): ?array
    {
        return self::TYPES[$type] ?? null;
    }

    public static function status(string $type): string
    {
        if (isset(self::TYPES[$type])) {
            return self::TYPES[$type]['status'];
        }

        return str_starts_with($type, 'h-x-') ? 'experimental' : 'unknown';
    }

    public static function specUrl(string $type): ?string
    {
        return isset(self::TYPES[$type]) ? self::WIKI . $type : null;
    }

    public static function classicSpecUrl(string $class): string
    {
        return self::WIKI . self::CLASSIC_ROOTS[$class]['page'];
    }

    public static function knowsProperty(array $types, string $property): bool
    {
        foreach ($types as $type) {
            if (isset(self::TYPES[$type]['properties'][$property])) {
                return true;
            }
        }

        return false;
    }

    public static function prefix(array $types, string $property, mixed $value): string
    {
        foreach ($types as $type) {
            if (isset(self::TYPES[$type]['properties'][$property])) {
                return self::TYPES[$type]['properties'][$property];
            }
        }

        if (is_array($value) && isset($value['html'])) {
            return 'e';
        }
        if (is_string($value) && is_absolute_url($value)) {
            return 'u';
        }
        if (is_string($value) && Dates::isIso($value)) {
            return 'dt';
        }

        return 'p';
    }

    public static function relGroup(string $rel): string
    {
        foreach (self::REL_GROUPS as $group => $rels) {
            if (isset($rels[$rel])) {
                return $group;
            }
        }

        return in_array($rel, self::XFN, true) ? 'XFN' : 'Other';
    }

    public static function relAbout(string $rel): ?string
    {
        foreach (self::REL_GROUPS as $rels) {
            if (isset($rels[$rel])) {
                return $rels[$rel];
            }
        }

        return in_array($rel, self::XFN, true) ? 'XFN relationship' : null;
    }
}
