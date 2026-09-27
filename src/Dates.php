<?php

namespace Indiechecker;

final class Dates
{
    private const DATE = '\d{4}-(\d{2}-\d{2}|\d{3})';
    private const TIME = '\d{2}:\d{2}(:\d{2}(\.\d+)?)?';
    private const ZONE = '(Z|[+-]\d{2}:?\d{2})';

    public static function isIso(string $value): bool
    {
        $date = self::DATE;
        $time = self::TIME;
        $zone = self::ZONE;

        return (bool) preg_match("/^{$date}([T ]{$time}{$zone}?)?$/", trim($value))
            || (bool) preg_match("/^{$time}{$zone}?$/", trim($value))
            || (bool) preg_match('/^--\d{2}-\d{2}$/', trim($value));
    }

    public static function isDuration(string $value): bool
    {
        return (bool) preg_match('/^P(?!$)(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(?=\d)(\d+H)?(\d+M)?(\d+(\.\d+)?S)?)?$/', trim($value));
    }

    public static function hasZone(string $value): bool
    {
        return (bool) preg_match('/' . self::TIME . self::ZONE . '$/', trim($value));
    }
}
