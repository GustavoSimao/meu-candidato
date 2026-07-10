<?php

namespace MeuCandidato\Ingestion\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use MeuCandidato\Ingestion\Models\IngestionJob;
use MeuCandidato\Ingestion\Services\CamaraApiClient;
use MeuCandidato\Legislative\Models\Bill;
use MeuCandidato\Legislative\Models\VotingSession;

class VincularVotosBillsCommand extends Command
{
    protected $signature = 'vincular-votos-bills
                            {--dias=365 : Processar apenas votações dos últimos N dias}
                            {--dry-run : Apenas listar sem alterar}';

    protected $description = 'Vincula votações sem bill à proposição correspondente via API da Câmara';

    private CamaraApiClient $camara;

    public function __construct(CamaraApiClient $camara)
    {
        parent::__construct();
        $this->camara = $camara;
    }

    public function handle(): int
    {
        $dias = (int) $this->option('dias');
        $dryRun = $this->option('dry-run');

        $this->info('=== Vinculando votações a proposições ===');
        $this->newLine();

        $job = IngestionJob::create([
            'source' => 'vincular_votos_bills',
            'status' => 'processing',
            'started_at' => now(),
            'records_count' => 0,
        ]);

        try {
            $query = VotingSession::whereNull('bill_id')
                ->whereNotNull('external_id')
                ->where('date', '>=', now()->subDays($dias)->toDateString())
                ->orderBy('date');

            $total = $query->count();
            $this->info("{$total} votações sem bill encontradas (últimos {$dias} dias).");

            if ($total === 0) {
                $this->info('Nada a fazer.');
                $job->update(['status' => 'completed', 'finished_at' => now()]);

                return self::SUCCESS;
            }

            $count = 0;
            $linked = 0;
            $skipped = 0;

            foreach ($query->get() as $session) {
                $count++;
                $this->info("  [{$count}/{$total}] Sessão {$session->external_id} ({$session->date})...");

                if ($dryRun) {
                    $billId = $this->tentarVincular($session, dryRun: true);

                    if ($billId) {
                        $this->line("    → Vinculada ao bill {$billId}", 'info');
                        $linked++;
                    } else {
                        $this->line('    → Sem proposição vinculada', 'comment');
                        $skipped++;
                    }

                    continue;
                }

                $billId = $this->tentarVincular($session);

                if ($billId) {
                    $session->update(['bill_id' => $billId]);
                    $linked++;
                    $this->line('    → Vinculada', 'info');
                } else {
                    $skipped++;
                    $this->line('    → Sem proposição', 'comment');
                }

                usleep(200000); // 200ms entre chamadas
            }

            $job->update([
                'status' => 'completed',
                'finished_at' => now(),
                'records_count' => $linked,
            ]);

            $this->newLine();
            $this->info("=== Concluído: {$linked} vinculadas, {$skipped} sem proposição ===");

            if ($dryRun) {
                $this->warn('Modo dry-run: nenhuma alteração foi feita.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $job->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_log' => $e->getMessage().PHP_EOL.$e->getTraceAsString(),
            ]);

            Log::error('Falha ao vincular votos a bills', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            $this->error("Erro: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function tentarVincular(VotingSession $session, bool $dryRun = false): ?string
    {
        $votacao = $this->camara->getVotacao($session->external_id);

        if (! $votacao) {
            return null;
        }

        $uri = $votacao['uriProposicaoObjeto'] ?? null;

        if (! $uri) {
            return null;
        }

        if (! preg_match('#idProposicao=(\d+)#', $uri, $matches)) {
            return null;
        }

        $externalId = $matches[1];

        $bill = Bill::firstOrCreate(
            ['external_id' => $externalId],
            [
                'title' => "Proposição #{$externalId}",
                'status' => 'desconhecido',
                'year' => (int) date('Y', strtotime($session->date)),
            ]
        );

        return $bill->id;
    }
}
