<?php

namespace Indiechecker;

use DOMElement;
use Mf2\Parser;

final class Mf2Parser extends Parser
{
    public const CLASSIC_KEY = 'x-indiechecker-classic';
    private const CLASSIC_ATTRIBUTE = 'data-indiechecker-classic';

    public function __construct(\DOMDocument $document, ?string $url)
    {
        parent::__construct($document, $url);

        $this->classicRootMap += [
            'hnews' => 'h-entry',
            'hreview-aggregate' => 'h-review-aggregate',
            'geo' => 'h-geo',
        ];

        $this->classicPropertyMap['hnews'] = $this->classicPropertyMap['hentry'] + [
            'source-org' => ['replace' => 'p-source-org h-card', 'context' => 'vcard'],
            'dateline' => ['replace' => 'p-dateline h-card', 'context' => 'vcard'],
        ];

        $this->classicPropertyMap['hreview-aggregate'] = [
            'summary' => ['replace' => 'p-name'],
            'item' => ['replace' => 'p-item h-item', 'context' => 'item'],
            'rating' => ['replace' => 'p-rating'],
            'average' => ['replace' => 'p-average'],
            'best' => ['replace' => 'p-best'],
            'worst' => ['replace' => 'p-worst'],
            'count' => ['replace' => 'p-count'],
            'votes' => ['replace' => 'p-votes'],
            'category' => ['replace' => 'p-category'],
        ];

        foreach ($this->xpath->query('//*[@class]') as $element) {
            $classes = class_tokens($element->getAttribute('class'));
            $classic = array_intersect($classes, array_keys($this->classicRootMap));
            if ($classic && !preg_grep('/^h-[a-z0-9-]*[a-z]$/', $classes)) {
                $element->setAttribute(self::CLASSIC_ATTRIBUTE, implode(' ', $classic));
            }
        }
    }

    public function parseH(DOMElement $e, $is_backcompat = false, $has_nested_mf = false)
    {
        $result = parent::parseH($e, $is_backcompat, $has_nested_mf);

        if ($result && $e->hasAttribute(self::CLASSIC_ATTRIBUTE)) {
            $result[self::CLASSIC_KEY] = explode(' ', $e->getAttribute(self::CLASSIC_ATTRIBUTE));
        }

        return $result;
    }
}
