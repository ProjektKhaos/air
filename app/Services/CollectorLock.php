<?php
// Senast uppdaterad: 2026-09-02 21:05 Asia/Bangkok | Version 1.00 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch\Services;

use ChiangMaiAirWatch\Config;

final class CollectorLock
{
    /** @var resource|null */
    private $handle = null;

    public function acquire(string $name): bool
    {
        $path = rtrim((string) Config::get('storage.locks'), '/') . '/' . preg_replace('/[^a-z0-9_-]/i', '-', $name) . '.lock';
        $this->handle = fopen($path, 'c');
        return is_resource($this->handle) && flock($this->handle, LOCK_EX | LOCK_NB);
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
        }
    }
}
