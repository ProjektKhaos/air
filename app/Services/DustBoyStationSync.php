<?php
// Senast uppdaterad: 2026-09-05 14:00 Asia/Bangkok | Version 1.11 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch\Services;

use ChiangMaiAirWatch\Repositories\StationRepository;

final class DustBoyStationSync
{
    /** @param array<string,mixed> $config */
    public function __construct(private readonly array $config) {}

    /** @param list<array<string,mixed>> $rows @return array<string,mixed> */
    public function synchronize(array $rows, StationRepository $stations, bool $apply): array
    {
        $center = (array) ($this->config['center'] ?? []);
        $centerLatitude = (float) ($center['latitude'] ?? 18.7883);
        $centerLongitude = (float) ($center['longitude'] ?? 98.9853);
        $radius = (float) ($this->config['radius_km'] ?? 15);
        $maximum = min(10, max(1, (int) ($this->config['maximum_stations'] ?? 10)));
        $allowlist = array_map('strval', (array) ($this->config['station_ids'] ?? []));
        $accepted = [];
        $skipped = [];

        foreach ($rows as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id === '') {
                $skipped[] = ['id' => '', 'reason' => 'missing_installation_id'];
                continue;
            }
            if ($allowlist !== [] && !in_array($id, $allowlist, true)) {
                $skipped[] = ['id' => $id, 'reason' => 'not_allowlisted'];
                continue;
            }
            if (!is_numeric($row['latitude'] ?? null) || !is_numeric($row['longitude'] ?? null)) {
                $skipped[] = ['id' => $id, 'reason' => 'missing_coordinates'];
                continue;
            }
            $distance = Geo::distanceKm($centerLatitude, $centerLongitude, (float) $row['latitude'], (float) $row['longitude']);
            if ($distance > $radius) {
                $skipped[] = ['id' => $id, 'reason' => 'outside_radius'];
                continue;
            }
            $accepted[] = $row + ['distance_km' => $distance];
        }

        usort($accepted, static fn(array $left, array $right): int =>
            $left['distance_km'] <=> $right['distance_km'] ?: strcmp((string) $left['id'], (string) $right['id'])
        );
        $accepted = array_slice($accepted, 0, $maximum);
        $changes = ['inserted' => 0, 'updated' => 0, 'unchanged' => 0];
        $preview = [];

        foreach ($accepted as $index => $row) {
            $name = trim((string) ($row['name'] ?? '')) ?: 'DustBoy ' . $row['id'];
            $hasThai = (bool) preg_match('/[\x{0E00}-\x{0E7F}]/u', $name);
            $station = [
                'provider_station_code' => (string) $row['id'],
                'display_name_en' => $hasThai ? 'DustBoy ' . $row['id'] : $name,
                'display_name_th' => $name,
                'latitude' => (float) $row['latitude'],
                'longitude' => (float) $row['longitude'],
                'sort_order' => 100 + $index,
                'metadata' => [
                    'source' => 'CMU DustBoy / CMU CCDC',
                    'dustboy_id' => $row['dustboy_id'] ?? null,
                    'model' => $row['model'] ?? null,
                    'version' => $row['version'] ?? null,
                    'distance_km' => round((float) $row['distance_km'], 3),
                ],
            ];
            $preview[] = ['id' => $station['provider_station_code'], 'name' => $name, 'distance_km' => round((float) $row['distance_km'], 2)];
            if ($apply) {
                $changes[$stations->upsertDustBoy($station)]++;
            }
        }

        return [
            'mode' => $apply ? 'apply' : 'dry-run',
            'accepted' => $preview,
            'accepted_count' => count($preview),
            'skipped_count' => count($skipped),
            'changes' => $changes,
        ];
    }
}
