<?php

namespace MeuCandidato\Ingestion\Support;

use Illuminate\Support\Facades\Log;
use MeuCandidato\Ingestion\Models\IngestionJob;

trait ManagesJobs
{
    private function criarJob(string $source): IngestionJob
    {
        return IngestionJob::create([
            'source' => $source,
            'status' => 'processing',
            'started_at' => now(),
            'records_count' => 0,
        ]);
    }

    private function finalizarJob(IngestionJob $job, int $count): void
    {
        $job->update([
            'status' => 'completed',
            'finished_at' => now(),
            'records_count' => $count,
        ]);
    }

    private function falharJob(IngestionJob $job, \Throwable $e): void
    {
        $job->update([
            'status' => 'failed',
            'finished_at' => now(),
            'error_log' => $e->getMessage()."\n".$e->getTraceAsString(),
        ]);

        Log::error('Falha na ingestão de dados', [
            'job_id' => $job->id,
            'error' => $e->getMessage(),
        ]);

        $this->error("Erro: {$e->getMessage()}");
    }
}
