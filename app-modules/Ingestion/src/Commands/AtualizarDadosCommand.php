<?php

namespace MeuCandidato\Ingestion\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Ingestion\Models\IngestionJob;
use MeuCandidato\Ingestion\Services\CamaraApiClient;
use MeuCandidato\Legislative\Models\Bill;
use MeuCandidato\Legislative\Models\Vote;
use MeuCandidato\Legislative\Models\VotingSession;

class AtualizarDadosCommand extends Command
{
    protected $signature = 'atualizar-dados
                            {--dias=1 : Dias retroativos para buscar votações}
                            {--mes= : Mês para despesas (1-12, padrão: mês anterior)}
                            {--ano= : Ano para despesas (padrão: ano atual)}';

    protected $description = 'Atualiza dados recentes de votações, votos e despesas CEAP (cron diário)';

    private CamaraApiClient $camara;

    public function __construct()
    {
        parent::__construct();
        $this->camara = new CamaraApiClient;
    }

    public function handle(): int
    {
        $this->info('=== Atualização diária de dados ===');
        $this->newLine();

        $job = $this->criarJob('atualizacao_diaria');

        try {
            $countVotacoes = $this->atualizarVotacoesRecentes();
            $countVotos = $this->atualizarVotosRecentes();
            $countDespesas = $this->atualizarDespesasCeap();

            $total = $countVotacoes + $countVotos + $countDespesas;

            $this->finalizarJob($job, $total);
            $this->newLine();
            $this->info("=== Atualização concluída: {$total} registros ===");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function atualizarVotacoesRecentes(): int
    {
        $dias = (int) $this->option('dias');
        $dataInicio = now()->subDays($dias)->format('Y-m-d');
        $dataFim = now()->format('Y-m-d');

        $this->info("Buscando votações de {$dataInicio} a {$dataFim}...");

        $count = 0;
        $pagina = 1;

        do {
            $votacoes = $this->camara->getVotacoes($dataInicio, $dataFim, $pagina);

            foreach ($votacoes as $votacao) {
                $externalId = (string) ($votacao['id'] ?? '');
                $data = $votacao['data'] ?? $votacao['dataHoraRegistro'] ?? null;
                $descricao = $votacao['descricao'] ?? $votacao['descricaoVotacao'] ?? '';
                $placarSim = $votacao['placarSim'] ?? null;
                $placarNao = $votacao['placarNao'] ?? null;

                $billId = $this->extrairBillId($votacao['uriProposicaoObjeto'] ?? null);

                VotingSession::updateOrCreate(
                    ['external_id' => $externalId],
                    array_merge([
                        'date' => $data,
                        'description' => trim($descricao.' Sim: '.($placarSim ?? '?').' Não: '.($placarNao ?? '?')),
                    ], $billId ? ['bill_id' => $billId] : [])
                );

                $count++;
                usleep(100000);
            }

            $pagina++;
        } while (count($votacoes) === 100 && $pagina <= 50);

        $this->info("  {$count} votações processadas.");

        return $count;
    }

    private function extrairBillId(?string $uri): ?string
    {
        if (! $uri) {
            return null;
        }

        if (preg_match('#idProposicao=(\d+)#', $uri, $matches)) {
            $externalId = $matches[1];

            $bill = Bill::firstOrCreate(
                ['external_id' => $externalId],
                [
                    'title' => "Proposição #{$externalId}",
                    'status' => 'desconhecido',
                    'year' => (int) date('Y'),
                ]
            );

            return $bill->id;
        }

        return null;
    }

    private function atualizarVotosRecentes(): int
    {
        $this->info('Buscando votos individuais do ano atual...');

        $anoAtual = (int) date('Y');
        $count = 0;

        $this->camara->streamVotosBulk($anoAtual, function (array $voto) use (&$count) {
            $idVotacao = $voto['idVotacao'] ?? null;
            $deputado = $voto['deputado_'] ?? null;
            $votoValor = $voto['voto'] ?? '';

            if (! $idVotacao || ! is_array($deputado)) {
                return;
            }

            $idDep = $deputado['id'] ?? null;
            if (! $idDep) {
                return;
            }

            $session = VotingSession::where('external_id', (string) $idVotacao)->first();
            if (! $session) {
                return;
            }

            $politician = Politician::where('external_id', (string) $idDep)->first();
            if (! $politician) {
                return;
            }

            Vote::updateOrCreate(
                [
                    'voting_session_id' => $session->id,
                    'politician_id' => $politician->id,
                ],
                [
                    'vote' => mb_substr($votoValor, 0, 10),
                ]
            );

            $count++;
        });

        $this->info("  {$count} votos processados.");

        return $count;
    }

    private function atualizarDespesasCeap(): int
    {
        $mes = $this->option('mes') ? (int) $this->option('mes') : (int) date('m', strtotime('-1 month'));
        $ano = $this->option('ano') ? (int) $this->option('ano') : (int) date('Y', strtotime('-1 month'));

        $this->info("Atualizando despesas CEAP de {$mes}/{$ano} via API por deputado...");

        $deputados = Politician::whereNotNull('external_id')
            ->where('position', 'Deputado Federal')
            ->pluck('external_id', 'id');

        if ($deputados->isEmpty()) {
            $this->warn('Nenhum deputado encontrado.');

            return 0;
        }

        $count = 0;

        foreach ($deputados as $politicianId => $externalId) {
            $pagina = 1;

            do {
                $despesas = $this->camara->getDespesasDeputado((int) $externalId, $ano, $pagina, 100, $mes);

                foreach ($despesas as $despesa) {
                    $tipoDespesa = $despesa['tipoDespesa'] ?? '';
                    $valor = $despesa['valorLiquido'] ?? $despesa['valorDocumento'] ?? 0;
                    $cnpjCpf = $despesa['cnpjCpfFornecedor'] ?? '';

                    DB::table('expenses')->updateOrInsert(
                        [
                            'politician_id' => $politicianId,
                            'year' => $ano,
                            'type' => mb_substr($tipoDespesa, 0, 500),
                            'document_number' => mb_substr($despesa['numDocumento'] ?? '', 0, 100),
                            'document_date' => $despesa['dataDocumento'] ?? null,
                        ],
                        [
                            'description' => mb_substr($despesa['nomeFornecedor'] ?? '', 0, 500),
                            'value' => $valor,
                            'supplier_cnpj_cpf' => mb_substr($cnpjCpf, 0, 20),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $count++;
                }

                usleep(100000);
                $pagina++;
            } while (count($despesas) === 100 && $pagina <= 10);
        }

        $this->info("  {$count} despesas CEAP processadas.");

        return $count;
    }

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

        Log::error('Falha na atualização diária', [
            'job_id' => $job->id,
            'error' => $e->getMessage(),
        ]);

        $this->error("Erro: {$e->getMessage()}");
    }
}
