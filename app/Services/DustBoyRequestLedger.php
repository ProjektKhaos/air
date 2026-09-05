<?php
// Senast uppdaterad: 2026-09-05 14:00 Asia/Bangkok | Version 1.11 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch\Services;

use ChiangMaiAirWatch\Config;
use ChiangMaiAirWatch\Providers\ProviderException;
use PDO;

final class DustBoyRequestLedger
{
    public function __construct(private readonly PDO $db) {}

    public function latestIsDue(): bool
    {
        $minutes=(int)Config::get('providers.dustboy.minimum_fetch_interval_minutes',55);
        $last=$this->db->query("SELECT MAX(requested_at) FROM provider_api_requests WHERE provider='dustboy' AND request_class='latest' AND outcome='success'")->fetchColumn();
        return !$last || strtotime((string)$last.' UTC') <= time()-($minutes*60);
    }

    public function reserve(string $requestClass): int
    {
        if (!preg_match('/^(latest|stations|history_30d|history_1y|history_5y)$/',$requestClass)) throw new \InvalidArgumentException('Invalid DustBoy request class.');
        $limit=(int)Config::get('providers.dustboy.maximum_requests_per_hour',10);
        $locked=(int)$this->db->query("SELECT GET_LOCK('cmaw_dustboy_api_quota',5)")->fetchColumn();
        if ($locked!==1) throw new ProviderException('DustBoy request quota lock is busy','UPSTREAM_TIMEOUT');
        try {
            $count=(int)$this->db->query("SELECT COUNT(*) FROM provider_api_requests WHERE provider='dustboy' AND requested_at>UTC_TIMESTAMP()-INTERVAL 60 MINUTE")->fetchColumn();
            if ($count >= $limit) throw new ProviderException('DustBoy local hourly request budget is exhausted','RATE_LIMITED');
            $statement=$this->db->prepare("INSERT INTO provider_api_requests(provider,request_class,requested_at,outcome) VALUES('dustboy',:class,UTC_TIMESTAMP(),'started')");
            $statement->execute(['class'=>$requestClass]);
            return (int)$this->db->lastInsertId();
        } finally {$this->db->query("SELECT RELEASE_LOCK('cmaw_dustboy_api_quota')");}
    }

    public function finish(int $id,string $outcome):void
    {
        if(!in_array($outcome,['success','failed'],true))$outcome='failed';
        $statement=$this->db->prepare('UPDATE provider_api_requests SET outcome=:outcome WHERE id=:id');
        $statement->execute(['outcome'=>$outcome,'id'=>$id]);
    }

    public function reserveLatestIfAvailable(): ?int
    {
        try {
            return $this->reserve('latest');
        } catch (ProviderException $error) {
            if ($error->providerCode === 'RATE_LIMITED') return null;
            throw $error;
        }
    }
}
