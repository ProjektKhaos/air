<?php
// Senast uppdaterad: 2026-09-03 12:50 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch;

use ChiangMaiAirWatch\Providers\ProviderException;

class HttpClient
{
    /** @return array<string,mixed> */
    public function getJson(string $url, int $connectTimeout, int $timeout, int $maxBytes, array $headers = [], ?string $caBundle = null): array
    {
        if (!str_starts_with($url, 'https://')) throw new ProviderException('HTTPS is required', 'INSECURE_PROVIDER_URL');
        $handle = curl_init($url);
        if ($handle === false) throw new ProviderException('Unable to initialize provider request', 'HTTP_INIT');
        $body = ''; $contentType = ''; $tooLarge = false;
        curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => false, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => $connectTimeout, CURLOPT_TIMEOUT => $timeout, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_HTTPHEADER => array_merge(['Accept: application/json', 'User-Agent: ChiangMaiAirWatch/1.0'], $headers), CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$contentType): int { if (str_starts_with(strtolower($line), 'content-type:')) $contentType = trim(substr($line, 13)); return strlen($line); }, CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body, &$tooLarge, $maxBytes): int { if (strlen($body) + strlen($chunk) > $maxBytes) { $tooLarge=true; return 0; } $body .= $chunk; return strlen($chunk); }]);
        if ($caBundle !== null && $caBundle !== '') curl_setopt($handle, is_dir($caBundle) ? CURLOPT_CAPATH : CURLOPT_CAINFO, $caBundle);
        $ok = curl_exec($handle); $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE); $error = curl_error($handle); curl_close($handle);
        if ($ok === false) throw new ProviderException($error === '' ? 'Provider request failed' : $error, str_contains(strtolower($error), 'certificate') ? 'TLS_CERTIFICATE' : ($tooLarge ? 'RESPONSE_TOO_LARGE' : 'HTTP_TRANSPORT'));
        if ($status < 200 || $status >= 300) {
            $code=match(true){$status===401||$status===403=>'AUTH_FAILED',$status===429=>'RATE_LIMITED',$status>=500=>'UPSTREAM_HTTP_ERROR',default=>'HTTP_STATUS'};
            throw new ProviderException('Provider returned HTTP ' . $status, $code);
        }
        if (!str_contains(strtolower($contentType), 'application/json')) throw new ProviderException('Provider returned an unexpected content type', 'CONTENT_TYPE');
        try { $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR); } catch (\JsonException) { throw new ProviderException('Provider returned malformed JSON', 'MALFORMED_JSON'); }
        if (!is_array($data)) throw new ProviderException('Provider JSON root is invalid', 'INVALID_SCHEMA');
        return $data;
    }
}
