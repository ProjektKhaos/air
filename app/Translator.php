<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch;

final class Translator
{
    /** @var array<string,array<string,string>> */
    private static array $catalogues = [];

    /** @param array<string,string|int|float> $parameters */
    public static function translate(string $key, array $parameters = [], ?string $language = null): string
    {
        $language ??= locale();
        if (!isset(self::$catalogues[$language])) {
            $file = APP_ROOT . '/app/lang/' . $language . '.php';
            self::$catalogues[$language] = is_file($file) ? require $file : [];
        }
        if (!isset(self::$catalogues['en'])) {
            self::$catalogues['en'] = require APP_ROOT . '/app/lang/en.php';
        }

        $text = self::$catalogues[$language][$key] ?? self::$catalogues['en'][$key] ?? $key;
        foreach ($parameters as $name => $value) {
            $text = str_replace('{' . $name . '}', (string) $value, $text);
        }

        return $text;
    }
}

