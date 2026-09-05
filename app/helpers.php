<?php
// Senast uppdaterad: 2026-09-02 18:55 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Translator;

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { $base = '/' . trim((string) Config::get('app.base_url', '/'), '/') . '/'; $base = preg_replace('#/+#', '/', $base) ?: '/'; return $path === '' ? $base : $base . ltrim($path, '/'); }
function asset_url(string $path): string { return url($path . (str_contains($path, '?') ? '&' : '?') . 'v=' . rawurlencode((string) Config::get('app.asset_version', '1.0.0'))); }
function locale(): string
{
    static $locale; if (is_string($locale)) return $locale;
    $supported = Config::get('app.supported_languages', ['en', 'th']);
    foreach ([PHP_SAPI !== 'cli' ? ($_GET['lang'] ?? null) : null, PHP_SAPI !== 'cli' ? ($_COOKIE['cmaw_lang'] ?? null) : null, PHP_SAPI !== 'cli' ? substr((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''), 0, 2) : null, Config::get('app.default_language', 'en')] as $candidate) if (is_string($candidate) && in_array($candidate, $supported, true)) { $locale = $candidate; break; }
    $locale ??= 'en';
    if (PHP_SAPI !== 'cli' && isset($_GET['lang']) && in_array($_GET['lang'], $supported, true) && !headers_sent()) setcookie('cmaw_lang', $_GET['lang'], ['expires' => time() + 31536000, 'path' => url(), 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax']);
    return $locale;
}
function t(string $key, array $parameters = [], ?string $language = null): string { return Translator::translate($key, $parameters, $language); }
function language_url(string $language): string { $uri = (string) ($_SERVER['REQUEST_URI'] ?? url()); $path = parse_url($uri, PHP_URL_PATH) ?: url(); parse_str((string) parse_url($uri, PHP_URL_QUERY), $query); $query['lang'] = $language; return $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986); }
function format_local_time(?string $utc, string $format = 'H:i · d M'): string { if (!$utc) return '—'; try { return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone((string) Config::get('app.timezone')))->format($format); } catch (Throwable) { return '—'; } }
function format_pollutant(mixed $value, int $decimals = 1): string { return is_numeric($value) ? number_format((float) $value, $decimals, '.', '') : '—'; }
function trend_display(mixed $value): string { if (!is_numeric($value)) return '—'; $v = round((float) $value, 1); return ($v > 0 ? '↑ +' : ($v < 0 ? '↓ ' : '→ ')) . number_format($v, 1, '.', '') . ' µg/m³'; }
