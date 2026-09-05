<?php
// Senast uppdaterad: 2026-09-03 13:05 Asia/Bangkok | Version 1.10 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

namespace ChiangMaiAirWatch\Services;

use PDO;

final class CollectorRun
{
    private int $id;

    public function __construct(private readonly PDO $db, string $collector)
    {
        $statement = $this->db->prepare(
            "INSERT INTO collector_runs (collector, status, started_at) VALUES (:collector, 'running', UTC_TIMESTAMP())"
        );
        $statement->execute(['collector' => $collector]);
        $this->id = (int) $this->db->lastInsertId();
    }

    public function finish(int $inserted, int $updated, string $message = 'Completed'): void
    {
        $this->complete('success',$inserted,$updated,$message);
    }

    public function complete(string $status,int $inserted,int $updated,string $message='Completed',?string $errorCode=null):void
    {
        if(!in_array($status,['success','partial'],true))throw new \InvalidArgumentException('Invalid collector completion status.');
        $statement = $this->db->prepare(
            "UPDATE collector_runs SET status = :status, finished_at = UTC_TIMESTAMP(), records_inserted = :inserted,
                records_updated = :updated, error_code=:error_code, message = :message WHERE id = :id"
        );
        $statement->execute(['id'=>$this->id,'status'=>$status,'inserted'=>$inserted,'updated'=>$updated,'error_code'=>$errorCode,'message'=>substr($message,0,500)]);
    }

    public function fail(string $code, string $message): void
    {
        $statement = $this->db->prepare(
            "UPDATE collector_runs SET status = 'failed', finished_at = UTC_TIMESTAMP(), error_code = :code,
                message = :message WHERE id = :id"
        );
        $statement->execute(['id' => $this->id, 'code' => substr($code, 0, 64), 'message' => substr($message, 0, 500)]);
    }
}
