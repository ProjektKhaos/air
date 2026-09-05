<?php
// Senast uppdaterad: 2026-09-05 14:00 Asia/Bangkok | Version 1.11 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);
namespace ChiangMaiAirWatch\Repositories;

use PDO;

final class StationRepository
{
    public function __construct(private readonly PDO $db) {}
    /** @return list<array<string,mixed>> */
    public function allEnabled(): array { return $this->db->query('SELECT * FROM stations WHERE enabled=1 ORDER BY sort_order, provider, provider_station_code')->fetchAll(); }
    /** @return list<array<string,mixed>> */
    public function forProvider(string $provider): array { $s=$this->db->prepare('SELECT * FROM stations WHERE enabled=1 AND provider=:provider ORDER BY sort_order'); $s->execute(['provider'=>$provider]); return $s->fetchAll(); }
    public function byPublicId(string $publicId): ?array { [$provider,$code] = array_pad(explode(':',$publicId,2),2,''); if ($provider===''||$code==='') return null; $s=$this->db->prepare('SELECT * FROM stations WHERE provider=:provider AND provider_station_code=:code AND enabled=1'); $s->execute(['provider'=>$provider,'code'=>$code]); $row=$s->fetch(); return is_array($row)?$row:null; }
    public function primary(): ?array { $row=$this->db->query('SELECT * FROM stations WHERE enabled=1 AND is_primary=1 ORDER BY sort_order LIMIT 1')->fetch(); return is_array($row)?$row:null; }
    public function upsertDustBoy(array $station):string
    {
        $metadata=json_encode($station['metadata'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $existing=$this->db->prepare("SELECT * FROM stations WHERE provider='dustboy' AND provider_station_code=:code");$existing->execute(['code'=>$station['provider_station_code']]);$found=$existing->fetch();
        if(is_array($found)
            &&(string)$found['display_name_en']===(string)$station['display_name_en']
            &&(string)$found['display_name_th']===(string)$station['display_name_th']
            &&abs((float)$found['latitude']-(float)$station['latitude'])<0.00000005
            &&abs((float)$found['longitude']-(float)$station['longitude'])<0.00000005
            &&(string)$found['station_type']==='GROUND'
            &&(int)$found['is_primary']===0
            &&(int)$found['affects_official_status']===0
            &&(int)$found['enabled']===1
            &&(int)$found['sort_order']===(int)$station['sort_order']
            &&(string)$found['source_metadata_json']===$metadata
        )return'unchanged';
        $sql="INSERT INTO stations(provider,provider_station_code,display_name_en,display_name_th,area_en,area_th,province_en,province_th,district_en,district_th,latitude,longitude,station_type,is_primary,affects_official_status,enabled,sort_order,source_metadata_json) VALUES('dustboy',:code,:name_en,:name_th,'Mueang Chiang Mai','เมืองเชียงใหม่','Chiang Mai','เชียงใหม่','Mueang Chiang Mai','เมืองเชียงใหม่',:latitude,:longitude,'GROUND',0,0,1,:sort_order,:metadata) ON DUPLICATE KEY UPDATE display_name_en=VALUES(display_name_en),display_name_th=VALUES(display_name_th),latitude=VALUES(latitude),longitude=VALUES(longitude),station_type='GROUND',is_primary=0,affects_official_status=0,enabled=1,sort_order=VALUES(sort_order),source_metadata_json=VALUES(source_metadata_json)";
        $statement=$this->db->prepare($sql);$statement->execute(['code'=>$station['provider_station_code'],'name_en'=>$station['display_name_en'],'name_th'=>$station['display_name_th'],'latitude'=>$station['latitude'],'longitude'=>$station['longitude'],'sort_order'=>$station['sort_order'],'metadata'=>$metadata]);return $found?'updated':'inserted';
    }
}
