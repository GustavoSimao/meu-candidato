<?php

namespace MeuCandidato\Ingestion\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Ingestion\Models\IngestionJob;
use MeuCandidato\Ingestion\Services\CamaraApiClient;
use MeuCandidato\Ingestion\Services\SenadoApiClient;
use MeuCandidato\Ingestion\Services\TseApiClient;
use MeuCandidato\Legislative\Models\Bill;
use MeuCandidato\Legislative\Models\BillCoauthor;
use MeuCandidato\Legislative\Models\BillProgress;
use MeuCandidato\Legislative\Models\BillTheme;
use MeuCandidato\Legislative\Models\CommitteeMembership;
use MeuCandidato\Legislative\Models\Event;
use MeuCandidato\Legislative\Models\LeadershipPosition;
use MeuCandidato\Legislative\Models\ParliamentaryBloc;
use MeuCandidato\Legislative\Models\ParliamentaryFront;
use MeuCandidato\Legislative\Models\PartyOrientation;
use MeuCandidato\Legislative\Models\Rapporteurship;
use MeuCandidato\Legislative\Models\Speech;
use MeuCandidato\Legislative\Models\Vote;
use MeuCandidato\Legislative\Models\VotingSession;
use MeuCandidato\Mandate\Models\Mandate;
use MeuCandidato\Party\Models\Party;
use MeuCandidato\Transparency\Models\CampaignFinancing;
use MeuCandidato\Transparency\Models\Expense;

class ImportarDadosCommand extends Command
{
    protected $signature = 'importar-dados
                            {subcomando? : Subcomando a executar}
                            {--ano-inicio=2001 : Ano inicial para dados legislativos}
                            {--ano-fim= : Ano final (padrão: ano atual)}
                            {--mes= : Mês para despesas-mes (1-12, obrigatório)}
                            {--limite= : Limita processamento a N registros (para testes)}';

    protected $description = 'Importa dados reais de APIs governamentais (Câmara, Senado, TSE)';

    private CamaraApiClient $camara;

    private SenadoApiClient $senado;

    private TseApiClient $tse;

    private const RATE_LIMIT_MS = 100;

    private const MAX_PAGINATION_PAGES = 200;

    public function __construct(CamaraApiClient $camara, SenadoApiClient $senado, TseApiClient $tse)
    {
        parent::__construct();
        $this->camara = $camara;
        $this->senado = $senado;
        $this->tse = $tse;
    }

    private function validateArrayData(mixed $data, array $requiredKeys, string $context): bool
    {
        if (! is_array($data)) {
            Log::warning("Dados inválidos em {$context}: não é array", ['data' => $data]);

            return false;
        }

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $data)) {
                Log::warning("Campo obrigatório ausente em {$context}", ['key' => $key]);

                return false;
            }
        }

        return true;
    }

    private function extrairBill(?string $uri): ?Bill
    {
        if (! $uri) {
            return null;
        }

        if (preg_match('#idProposicao=(\d+)#', $uri, $matches)) {
            $externalId = $matches[1];

            return Bill::firstOrCreate(
                ['external_id' => $externalId],
                [
                    'title' => "Proposição #{$externalId}",
                    'status' => 'desconhecido',
                    'year' => (int) date('Y'),
                ]
            );
        }

        return null;
    }

    private function rateLimit(): void
    {
        usleep(self::RATE_LIMIT_MS * 1000);
    }

    private function getLimite(): ?int
    {
        $limite = $this->option('limite');

        return $limite ? (int) $limite : null;
    }

    private function shouldStop(int $count, ?int $limite): bool
    {
        return $limite !== null && $count >= $limite;
    }

    public function handle(): int
    {
        $subcomando = $this->argument('subcomando') ?? 'todos';

        $anoFim = $this->option('ano-fim') ? (int) $this->option('ano-fim') : (int) date('Y');
        $anoInicio = (int) $this->option('ano-inicio');

        return match ($subcomando) {
            'partidos' => $this->importarPartidos(),
            'deputados' => $this->importarDeputados(),
            'senadores' => $this->importarSenadores(),
            'mandatos' => $this->importarMandatos(),
            'proposicoes' => $this->importarProposicoes($anoInicio, $anoFim),
            'votacoes' => $this->importarVotacoes($anoInicio, $anoFim),
            'votacoes-sessoes' => $this->importarVotacoesSessoes($anoInicio, $anoFim),
            'votos' => $this->importarVotosBulk($anoInicio, $anoFim),
            'despesas' => $this->importarDespesas($anoInicio, $anoFim),
            'despesas-ceap' => $this->importarDespesasCeap($anoInicio, $anoFim),
            'despesas-deputados' => $this->importarDespesasPorDeputado($anoInicio, $anoFim),
            'despesas-mes' => $this->importarDespesasMes($anoFim),
            'financiamento-campanha' => $this->importarFinanciamentoCampanha($anoFim),
            'senador-votos' => $this->importarVotosSenadores(),
            'senador-autorias' => $this->importarAutoriasSenadores(),
            'discursos-camara' => $this->importarDiscursosCamara(),
            'eventos-camara' => $this->importarEventosCamara(),
            'frentes-camara' => $this->importarFrentesCamara(),
            'orgaos-camara' => $this->importarOrgaosCamara(),
            'orientacoes-votacao' => $this->importarOrientacoesVotacao(),
            'temas-proposicoes' => $this->importarTemasProposicoes(),
            'tramitacao-proposicoes' => $this->importarTramitacaoProposicoes(),
            'autores-proposicoes' => $this->importarAutoresProposicoes(),
            'blocos' => $this->importarBlocos(),
            'comissoes-senado' => $this->importarComissoesSenado(),
            'discursos-senado' => $this->importarDiscursosSenado(),
            'relatorias-senado' => $this->importarRelatoriasSenado(),
            'liderancas-senado' => $this->importarLiderancasSenado(),
            'vincular-bills-autores' => $this->vincularBillsAutores(),
            'todos' => $this->importarTodos($anoInicio, $anoFim),
            default => $this->errorSubcomando($subcomando),
        };
    }

    private function errorSubcomando(string $subcomando): int
    {
        $this->error("Subcomando '{$subcomando}' não encontrado. Opções disponíveis:");
        $this->line('  Dados base: partidos, deputados, senadores, mandatos');
        $this->line('  Legislação: proposicoes, votacoes, votacoes-sessoes, votos, votos-bulk');
        $this->line('  Transparência: despesas, despesas-ceap, despesas-deputados, despesas-mes, financiamento-campanha');
        $this->line('  Senado: senador-votos, senador-autorias, comissoes-senado, discursos-senado, relatorias-senado, liderancas-senado');
        $this->line('  Câmara extras: discursos-camara, eventos-camara, frentes-camara, orgaos-camara');
        $this->line('  Enriquecimento: orientacoes-votacao, temas-proposicoes, tramitacao-proposicoes, autores-proposicoes, blocos');
        $this->line('  Vínculos: vincular-bills-autores');
        $this->line('  Geral: todos');

        return self::FAILURE;
    }

    private function importarPartidos(): int
    {
        $this->info('Importando partidos da Câmara dos Deputados...');

        $job = $this->criarJob('partidos_camara');

        try {
            $partidosApi = $this->camara->getPartidos();
            $count = 0;

            foreach ($partidosApi as $partidoData) {
                $sigla = $partidoData['sigla'] ?? null;
                $nome = $partidoData['nome'] ?? null;

                if (! $sigla || ! $nome) {
                    continue;
                }

                $existing = Party::where('acronym', $sigla)->first();

                if ($existing) {
                    $existing->update(['name' => $nome]);
                } else {
                    Party::create([
                        'acronym' => $sigla,
                        'name' => $nome,
                    ]);
                }

                $count++;
            }

            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} partidos.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarDeputados(): int
    {
        $this->info('Importando deputados da Câmara dos Deputados...');

        $job = $this->criarJob('deputados_camara');

        try {
            $pagina = 1;
            $count = 0;
            $totalPaginas = $this->camara->getTotalDeputados();

            $this->info("Total de páginas de deputados: {$totalPaginas}");

            $this->output->progressStart($totalPaginas > 0 ? $totalPaginas : 1);

            do {
                $deputados = $this->camara->getDeputados($pagina, 100);

                foreach ($deputados as $dep) {
                    if ($this->importarDeputado($dep)) {
                        $count++;
                    }
                    $this->rateLimit();
                }

                $this->output->progressAdvance(1);

                $pagina++;
            } while (count($deputados) === 100 && $pagina <= $totalPaginas && $pagina <= self::MAX_PAGINATION_PAGES);

            $this->output->progressFinish();
            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} deputados.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarDeputado(array $dep): bool
    {
        if (! $this->validateArrayData($dep, ['id'], 'deputado')) {
            return false;
        }

        $idExterno = (string) ($dep['id'] ?? '');
        $nome = $dep['nomeCivil'] ?? $dep['nome'] ?? '';
        $siglaPartido = $dep['ultimoStatus']['siglaPartido'] ?? $dep['siglaPartido'] ?? null;
        $uf = $dep['ultimoStatus']['siglaUf'] ?? $dep['siglaUf'] ?? null;
        $foto = $dep['ultimoStatus']['urlFoto'] ?? $dep['urlFoto'] ?? null;
        $dataNascimento = $dep['dataNascimento'] ?? null;
        $escolaridade = $dep['ultimoStatus']['escolaridade'] ?? null;
        $nomeUrna = $dep['ultimoStatus']['nomeEleitoral'] ?? null;
        $email = $dep['ultimoStatus']['gabinete']['email'] ?? null;
        $telefone = $dep['ultimoStatus']['gabinete']['telefone'] ?? null;
        $gabinete = $dep['ultimoStatus']['gabinete']['nome'] ?? null;
        $predio = $dep['ultimoStatus']['gabinete']['predio'] ?? null;
        $ufNascimento = $dep['ufNascimento'] ?? null;
        $municipioNascimento = $dep['municipioNascimento'] ?? null;

        $redeSocial = $dep['redeSocial'] ?? [];
        $socialMedia = [];
        if (is_array($redeSocial)) {
            foreach ($redeSocial as $url) {
                if (str_contains($url, 'twitter.com') || str_contains($url, 'x.com')) {
                    $socialMedia[] = ['platform' => 'twitter', 'url' => $url];
                } elseif (str_contains($url, 'instagram.com')) {
                    $socialMedia[] = ['platform' => 'instagram', 'url' => $url];
                } elseif (str_contains($url, 'facebook.com')) {
                    $socialMedia[] = ['platform' => 'facebook', 'url' => $url];
                } elseif (str_contains($url, 'tiktok.com')) {
                    $socialMedia[] = ['platform' => 'tiktok', 'url' => $url];
                } elseif (str_contains($url, 'youtube.com')) {
                    $socialMedia[] = ['platform' => 'youtube', 'url' => $url];
                }
            }
        }

        $office = null;
        if ($gabinete || $predio) {
            $office = trim(($gabinete ?? '').' - '.($predio ?? ''));
        }

        $party = $this->findOrCreateParty($siglaPartido);

        $politician = Politician::updateOrCreate(
            ['external_id' => $idExterno],
            [
                'name' => $nome,
                'nome_urna' => $nomeUrna,
                'party_id' => $party->id,
                'birth_date' => $dataNascimento,
                'education' => $escolaridade,
                'photo_url' => $foto,
                'position' => 'Deputado Federal',
                'email' => $email,
                'phone' => $telefone,
                'office' => $office,
                'social_media' => $socialMedia ?: null,
                'uf_birth' => $ufNascimento,
                'municipality_birth' => $municipioNascimento,
            ]
        );

        if ($uf) {
            $politician->address()->updateOrCreate(
                ['addressable_type' => Politician::class, 'addressable_id' => $politician->id],
                ['uf' => $uf]
            );
        }

        return true;
    }

    private function findOrCreateParty(?string $sigla): Party
    {
        $sigla = $sigla ?? 'S/';

        return Party::firstOrCreate(['acronym' => $sigla], ['name' => $sigla]);
    }

    private function importarSenadores(): int
    {
        $this->info('Importando senadores do Senado Federal...');

        $job = $this->criarJob('senadores_senado');

        try {
            $count = 0;

            $this->info('Buscando senadores em exercício...');
            $senadoresAtuais = $this->senado->getSenadoresAtuais();

            foreach ($senadoresAtuais as $sen) {
                if ($this->importarSenador($sen)) {
                    $count++;
                }
                $this->rateLimit();
            }

            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} senadores.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarSenador(array $sen): bool
    {
        $identificacao = $sen['IdentificacaoParlamentar'] ?? $sen;

        if (! $this->validateArrayData($identificacao, ['CodigoParlamentar'], 'senador')) {
            return false;
        }

        $codigo = $identificacao['CodigoParlamentar'] ?? $identificacao['codigoParlamentar'] ?? null;
        $nome = $identificacao['NomeParlamentar'] ?? $identificacao['nomeParlamentar'] ?? '';
        $nomeCivil = $identificacao['NomeCompletoParlamentar'] ?? $identificacao['nomeCompletoParlamentar'] ?? $nome;
        $siglaPartido = $identificacao['SiglaPartidoParlamentar'] ?? $identificacao['siglaPartidoParlamentar'] ?? null;
        $uf = $identificacao['UfParlamentar'] ?? $identificacao['ufParlamentar'] ?? null;
        $foto = $identificacao['UrlFotoParlamentar'] ?? $identificacao['urlFotoParlamentar'] ?? null;

        $party = $this->findOrCreateParty($siglaPartido);

        $externalId = 'senado_'.($codigo ?? '');

        $politician = Politician::updateOrCreate(
            ['external_id' => $externalId],
            [
                'name' => $nomeCivil,
                'party_id' => $party->id,
                'photo_url' => $foto,
                'position' => 'Senador Federal',
            ]
        );

        if ($uf) {
            $politician->address()->updateOrCreate(
                ['addressable_type' => Politician::class, 'addressable_id' => $politician->id],
                ['uf' => $uf]
            );
        }

        return true;
    }

    private function importarMandatos(): int
    {
        $this->info('Importando mandatos...');

        $job = $this->criarJob('mandatos');

        try {
            $count = 0;

            $politicians = Politician::whereNotNull('external_id')
                ->where('external_id', 'NOT LIKE', 'senado_%')
                ->get();

            $this->info("Processando mandatos de {$politicians->count()} deputados...");

            foreach ($politicians as $politician) {
                $mandatos = $this->camara->getMandatosDeputado((int) $politician->external_id);

                foreach ($mandatos as $mandato) {
                    if (! is_array($mandato)) {
                        continue;
                    }

                    $startedAt = $mandato['dataInicio'] ?? $mandato['DataInicio'] ?? null;
                    $endedAt = $mandato['dataFim'] ?? $mandato['DataFim'] ?? null;

                    if ($startedAt) {
                        Mandate::updateOrCreate(
                            [
                                'politician_id' => $politician->id,
                                'position' => 'Deputado Federal',
                                'started_at' => $startedAt,
                            ],
                            [
                                'ended_at' => $endedAt,
                            ]
                        );
                        $count++;
                    }
                }
                $this->rateLimit();
            }

            $senadores = Politician::where('external_id', 'LIKE', 'senado_%')->get();

            foreach ($senadores as $senador) {
                $codigo = str_replace('senado_', '', $senador->external_id);
                $mandatos = $this->senado->getMandatosSenador((int) $codigo);

                foreach ($mandatos as $mandato) {
                    if (! is_array($mandato)) {
                        continue;
                    }

                    $startedAt = $mandato['DataInicio'] ?? $mandato['dataInicio'] ?? null;
                    $endedAt = $mandato['DataFim'] ?? $mandato['dataFim'] ?? null;

                    if ($startedAt) {
                        Mandate::updateOrCreate(
                            [
                                'politician_id' => $senador->id,
                                'position' => 'Senador Federal',
                                'started_at' => $startedAt,
                            ],
                            [
                                'ended_at' => $endedAt,
                            ]
                        );
                        $count++;
                    }
                }
                $this->rateLimit();
            }

            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} mandatos.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarProposicoes(int $anoInicio, int $anoFim): int
    {
        $this->info("Importando proposições de {$anoInicio} a {$anoFim}...");

        $job = $this->criarJob('proposicoes_camara');

        try {
            $count = 0;

            for ($ano = $anoInicio; $ano <= $anoFim; $ano++) {
                $this->info("  Processando ano {$ano}...");
                $pagina = 1;

                do {
                    $proposicoes = $this->camara->getProposicoes($ano, $pagina, 100);

                    foreach ($proposicoes as $prop) {
                        $this->importarProposicao($prop);
                        $count++;
                        $this->rateLimit();
                    }

                    $pagina++;
                } while (count($proposicoes) === 100 && $pagina <= self::MAX_PAGINATION_PAGES);
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} proposições.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarProposicao(array $prop): void
    {
        if (! $this->validateArrayData($prop, ['id'], 'proposição')) {
            return;
        }

        $externalId = (string) ($prop['id'] ?? '');
        $siglaTipo = $prop['siglaTipo'] ?? '';
        $numero = $prop['numero'] ?? '';
        $ano = $prop['ano'] ?? date('Y');
        $ementa = $prop['ementa'] ?? $prop['descricaoMateria'] ?? '';
        $status = $prop['statusProposicao']['descricaoSituacao'] ?? $prop['descricaoSituacao'] ?? '';
        $idAutor = $prop['idDeputadoAutor'] ?? $prop['ultimoStatus']['idDeputadoAutor'] ?? null;

        $author = null;
        if ($idAutor) {
            $author = Politician::where('external_id', (string) $idAutor)->first();
        }

        $title = trim($siglaTipo.' '.$numero.'/'.$ano);

        Bill::updateOrCreate(
            ['external_id' => $externalId],
            [
                'title' => mb_substr($title, 0, 300),
                'description' => $ementa,
                'author_id' => $author?->id,
                'status' => mb_substr($status, 0, 50),
                'year' => (int) $ano,
            ]
        );
    }

    private function importarVotacoes(int $anoInicio, int $anoFim): int
    {
        $this->info("Importando votações de {$anoInicio} a {$anoFim}...");

        $job = $this->criarJob('votacoes_camara');

        try {
            $count = 0;
            $meses = [
                ['01-01', '03-31'],
                ['04-01', '06-30'],
                ['07-01', '09-30'],
                ['10-01', '12-31'],
            ];

            for ($ano = $anoInicio; $ano <= $anoFim; $ano++) {
                foreach ($meses as [$inicio, $fim]) {
                    $dataInicio = "{$ano}-{$inicio}";
                    $dataFim = "{$ano}-{$fim}";
                    $this->info("  Processando votações de {$dataInicio} a {$dataFim}...");
                    $pagina = 1;

                    do {
                        $votacoes = $this->camara->getVotacoes($dataInicio, $dataFim, $pagina);

                        foreach ($votacoes as $votacao) {
                            $this->importarVotacao($votacao);
                            $count++;
                            $this->rateLimit();
                        }

                        $pagina++;
                    } while (count($votacoes) === 100 && $pagina <= self::MAX_PAGINATION_PAGES);
                }
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} votações.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarVotacao(array $votacao): void
    {
        if (! $this->validateArrayData($votacao, ['id'], 'votação')) {
            return;
        }

        $externalId = (string) ($votacao['id'] ?? '');
        $data = $votacao['data'] ?? $votacao['dataHoraRegistro'] ?? null;
        $descricao = $votacao['descricao'] ?? $votacao['descricaoVotacao'] ?? '';
        $placarSim = $votacao['placarSim'] ?? null;
        $placarNao = $votacao['placarNao'] ?? null;

        $bill = $this->extrairBill($votacao['uriProposicaoObjeto'] ?? null);

        $session = VotingSession::updateOrCreate(
            ['external_id' => $externalId],
            [
                'bill_id' => $bill?->id,
                'date' => $data,
                'description' => trim($descricao.' Sim: '.($placarSim ?? '?').' Não: '.($placarNao ?? '?')),
            ]
        );

        $votos = $this->camara->getVotosVotacao($externalId);

        foreach ($votos as $voto) {
            if (! is_array($voto)) {
                continue;
            }

            $idDep = $voto['idDeputado'] ?? null;
            $votoValor = $voto['voto'] ?? '';

            if (! $idDep) {
                continue;
            }

            $politician = Politician::where('external_id', (string) $idDep)->first();

            if (! $politician) {
                continue;
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
        }
    }

    private function importarVotacoesSessoes(int $anoInicio, int $anoFim): int
    {
        $this->info("Importando sessões de votação de {$anoInicio} a {$anoFim} (sem votos individuais)...");

        $job = $this->criarJob('votacoes_sessoes_camara');

        try {
            $count = 0;
            $meses = [
                ['01-01', '03-31'],
                ['04-01', '06-30'],
                ['07-01', '09-30'],
                ['10-01', '12-31'],
            ];

            for ($ano = $anoInicio; $ano <= $anoFim; $ano++) {
                foreach ($meses as [$inicio, $fim]) {
                    $dataInicio = "{$ano}-{$inicio}";
                    $dataFim = "{$ano}-{$fim}";
                    $this->info("  {$dataInicio} a {$dataFim}...");
                    $pagina = 1;

                    do {
                        $votacoes = $this->camara->getVotacoes($dataInicio, $dataFim, $pagina);

                        foreach ($votacoes as $votacao) {
                            $this->importarVotacao($votacao);
                            $count++;
                        }

                        $this->rateLimit();
                        $pagina++;
                    } while (count($votacoes) === 100 && $pagina <= self::MAX_PAGINATION_PAGES);
                }
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} sessões de votação.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarDespesas(int $anoInicio, int $anoFim): int
    {
        $this->info("Importando despesas (CEAP) de {$anoInicio} a {$anoFim}...");
        $this->warn('⚠️  Esta operação pode demorar bastante. Arquivos são grandes.');

        $job = $this->criarJob('despesas_camara');

        try {
            $count = 0;

            for ($ano = $anoInicio; $ano <= $anoFim; $ano++) {
                $this->info("  Baixando dados de {$ano}...");
                $jsonPath = $this->camara->downloadArquivoJsonToDisk('despesas', $ano);

                if (! $jsonPath || ! file_exists($jsonPath)) {
                    $this->warn("  Dados de {$ano} não disponíveis.");

                    continue;
                }

                $json = file_get_contents($jsonPath);
                @unlink($jsonPath);
                $data = json_decode($json, true);
                unset($json);

                if (! is_array($data)) {
                    continue;
                }

                $registros = $data['data'] ?? $data;

                if (! is_array($registros)) {
                    continue;
                }

                $this->info('  Processando '.count($registros)." registros de {$ano}...");

                foreach (array_chunk($registros, 500) as $chunk) {
                    DB::beginTransaction();

                    try {
                        foreach ($chunk as $despesa) {
                            if (! is_array($despesa)) {
                                continue;
                            }

                            $idDep = $despesa['coDeputado'] ?? $despesa['idDeputado'] ?? null;
                            $tipoDespesa = $despesa['tipoDespesa'] ?? '';
                            $valor = $despesa['vlrLiquido'] ?? $despesa['vlrDocumento'] ?? 0;
                            $cnpjCpf = $despesa['cnpjCpfFornecedor'] ?? '';

                            if (! $idDep) {
                                continue;
                            }

                            $politician = Politician::where('external_id', (string) $idDep)->first();

                            if (! $politician) {
                                continue;
                            }

                            Expense::updateOrCreate(
                                [
                                    'politician_id' => $politician->id,
                                    'year' => $ano,
                                    'type' => mb_substr($tipoDespesa, 0, 500),
                                    'document_number' => mb_substr($despesa['nuDocumento'] ?? '', 0, 100),
                                    'document_date' => $despesa['dataDocumento'] ?? null,
                                ],
                                [
                                    'description' => mb_substr($despesa['txtDescricao'] ?? '', 0, 500),
                                    'value' => $valor,
                                    'supplier_cnpj_cpf' => mb_substr($cnpjCpf, 0, 20),
                                ]
                            );

                            $count++;
                        }

                        DB::commit();
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        throw $e;
                    }
                }
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} despesas.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarDespesasPorDeputado(int $anoInicio, int $anoFim): int
    {
        $this->info("Importando despesas por deputado de {$anoInicio} a {$anoFim}...");
        $this->warn('⚠️  Esta operação pode demorar bastante (chamadas individuais por deputado).');

        $job = $this->criarJob('despesas_por_deputado');

        try {
            $deputados = Politician::whereNotNull('external_id')
                ->where('position', 'Deputado Federal')
                ->pluck('external_id', 'id');

            if ($deputados->isEmpty()) {
                $this->warn('Nenhum deputado encontrado. Execute primeiro: importar-dados deputados');

                return self::FAILURE;
            }

            $count = 0;
            $total = $deputados->count();

            foreach ($deputados as $politicianId => $externalId) {
                $this->info("  Deputado {$externalId} ({$count}/{$total})...");

                for ($ano = $anoInicio; $ano <= $anoFim; $ano++) {
                    $pagina = 1;

                    do {
                        $despesas = $this->camara->getDespesasDeputado((int) $externalId, $ano, $pagina);

                        foreach ($despesas as $despesa) {
                            $tipoDespesa = $despesa['tipoDespesa'] ?? '';
                            $valor = $despesa['valorLiquido'] ?? $despesa['valorDocumento'] ?? 0;
                            $cnpjCpf = $despesa['cnpjCpfFornecedor'] ?? '';

                            Expense::updateOrCreate(
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
                                ]
                            );

                            $count++;
                        }

                        $this->rateLimit();

                        $pagina++;
                    } while (count($despesas) === 100 && $pagina <= self::MAX_PAGINATION_PAGES);
                }
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} despesas por deputado.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarDespesasMes(int $anoFim): int
    {
        $mes = $this->option('mes') ? (int) $this->option('mes') : null;

        if ($mes === null || $mes < 1 || $mes > 12) {
            $this->error('A opção --mes= é obrigatória para despesas-mes (1-12).');

            return self::FAILURE;
        }

        $ano = $anoFim;
        $this->info("Importando despesas de {$mes}/{$ano} por deputado...");

        $job = $this->criarJob('despesas_mes');

        try {
            $deputados = Politician::whereNotNull('external_id')
                ->where('position', 'Deputado Federal')
                ->pluck('external_id', 'id');

            if ($deputados->isEmpty()) {
                $this->warn('Nenhum deputado encontrado. Execute primeiro: importar-dados deputados');

                return self::FAILURE;
            }

            $count = 0;
            $total = $deputados->count();

            foreach ($deputados as $politicianId => $externalId) {
                $this->info("  Deputado {$externalId} ({$count}/{$total})...");
                $pagina = 1;

                do {
                    $despesas = $this->camara->getDespesasDeputado((int) $externalId, $ano, $pagina, 100, $mes);

                    foreach ($despesas as $despesa) {
                        $tipoDespesa = $despesa['tipoDespesa'] ?? '';
                        $valor = $despesa['valorLiquido'] ?? $despesa['valorDocumento'] ?? 0;
                        $cnpjCpf = $despesa['cnpjCpfFornecedor'] ?? '';

                        Expense::updateOrCreate(
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
                            ]
                        );

                        $count++;
                    }

                    $this->rateLimit();
                    $pagina++;
                } while (count($despesas) === 100 && $pagina <= self::MAX_PAGINATION_PAGES);
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} despesas para {$mes}/{$ano}.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarVotosBulk(int $anoInicio, int $anoFim): int
    {
        $this->info("Importando votos individuais de {$anoInicio} a {$anoFim} (streaming)...");
        $this->warn('⚠️  Arquivos grandes (28-103 MB por ano). Processamento em streaming.');

        $job = $this->criarJob('votos_bulk_camara');

        try {
            $count = 0;

            for ($ano = $anoInicio; $ano <= $anoFim; $ano++) {
                $this->info("  Processando votos de {$ano}...");
                $anoCount = 0;

                $this->camara->streamVotosBulk($ano, function (array $voto) use (&$anoCount) {
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

                    $anoCount++;
                });

                $count += $anoCount;
                $this->info("  {$ano}: {$anoCount} votos importados.");
            }

            $this->finalizarJob($job, $count);
            $this->info("Total: {$count} votos importados via streaming.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarDespesasCeap(int $anoInicio, int $anoFim): int
    {
        $this->info("Importando despesas CEAP (streaming) de {$anoInicio} a {$anoFim}...");
        $this->warn('⚠️  Arquivos ZIP grandes. Streaming linha a linha.');

        $job = $this->criarJob('despesas_ceap_bulk');

        try {
            $count = 0;

            for ($ano = $anoInicio; $ano <= $anoFim; $ano++) {
                $this->info("  Baixando cotas CEAP de {$ano}...");
                $jsonPath = $this->camara->downloadCotasCeat($ano);

                if (! $jsonPath || ! file_exists($jsonPath)) {
                    $this->warn("  Cotas de {$ano} não disponíveis.");

                    continue;
                }

                $this->info('  Processando arquivo: '.basename($jsonPath));

                $batch = [];
                $batchCount = 0;

                $this->camara->streamCotasFromDisk($jsonPath, function ($despesa) use ($ano, &$batch, &$batchCount, &$count) {
                    $idDep = $despesa['numeroDeputadoID'] ?? $despesa['id'] ?? $despesa['coDeputado'] ?? null;
                    $tipoDespesa = $despesa['tipoDespesa'] ?? '';
                    $valor = $despesa['valorLiquido'] ?? $despesa['vlrLiquido'] ?? 0;
                    $cnpjCpf = $despesa['cnpjCpfFornecedor'] ?? '';
                    $numDoc = $despesa['numDocumento'] ?? $despesa['nuDocumento'] ?? '';
                    $dataDoc = $despesa['dataDocumento'] ?? null;
                    $descricao = $despesa['descricao'] ?? $despesa['txtDescricao'] ?? '';

                    if (! $idDep) {
                        return;
                    }

                    $politician = Politician::where('external_id', (string) $idDep)->first();

                    if (! $politician) {
                        return;
                    }

                    $batch[] = [
                        'politician_id' => $politician->id,
                        'year' => $ano,
                        'type' => mb_substr($tipoDespesa, 0, 500),
                        'document_number' => mb_substr($numDoc, 0, 100),
                        'document_date' => $dataDoc,
                        'description' => mb_substr($descricao, 0, 500),
                        'value' => $valor,
                        'supplier_cnpj_cpf' => mb_substr($cnpjCpf, 0, 20),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $batchCount++;

                    if ($batchCount >= 500) {
                        DB::table('expenses')->insertOrIgnore($batch);
                        $count += count($batch);
                        $batch = [];
                        $batchCount = 0;
                    }
                });

                if ($batchCount > 0) {
                    DB::table('expenses')->insertOrIgnore($batch);
                    $count += count($batch);
                }

                $this->camara->cleanupCotasExtract($ano);
                $this->info("  {$ano}: {$batchCount} registros inseridos.");
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} despesas CEAP via streaming.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarTodos(int $anoInicio, int $anoFim): int
    {
        $this->info('=== Importação completa de dados ===');
        $this->newLine();

        $etapas = [
            'partidos' => 'Partidos',
            'deputados' => 'Deputados',
            'senadores' => 'Senadores',
            'mandatos' => 'Mandatos',
            'proposicoes' => 'Proposições',
            'votacoes' => 'Votações',
            'despesas' => 'Despesas',
        ];

        foreach ($etapas as $subcomando => $label) {
            $this->info(">>> {$label}");
            $this->newLine();

            $this->call('importar-dados', array_merge(
                ['subcomando' => $subcomando],
                $this->option('ano-inicio') ? ['--ano-inicio' => $this->option('ano-inicio')] : [],
                $this->option('ano-fim') ? ['--ano-fim' => $this->option('ano-fim')] : [],
            ));

            $this->newLine();
        }

        $this->info('=== Importação concluída ===');

        return self::SUCCESS;
    }

    private function importarFinanciamentoCampanha(int $anoEleitoral): int
    {
        $this->info("Importando financiamento de campanha TSE (eleições {$anoEleitoral})...");
        $this->warn('⚠️  Arquivo grande (~200MB ZIP). Download + parse em streaming.');

        $job = $this->criarJob('financiamento_campanha_tse');

        try {
            $tempDir = storage_path('app/tse_temp');

            $this->info('  Baixando dados do TSE...');
            $zipPath = $this->tse->downloadCandidatosCsv($anoEleitoral, $tempDir);

            if (! $zipPath) {
                $this->error('Falha ao baixar dados do TSE.');

                return self::FAILURE;
            }

            $this->info('  Extraindo CSVs...');
            $csvPaths = $this->tse->extractReceitasCsvs($zipPath);

            if (empty($csvPaths)) {
                $this->error('Falha ao extrair CSVs do ZIP.');

                return self::FAILURE;
            }

            $this->info('  '.count($csvPaths).' CSVs de receitas encontrados.');

            $politiciansByName = Politician::select('id', 'name')
                ->pluck('name', 'id')
                ->mapWithKeys(fn ($name, $id) => [mb_strtolower($name) => $id]);

            $totals = [];
            $count = 0;

            foreach ($csvPaths as $csvPath) {
                $basename = basename($csvPath);
                $this->info("  Processando {$basename}...");

                $this->tse->streamReceitasCsv($csvPath, function (array $data) use (&$count, &$totals, $politiciansByName) {
                    $count++;

                    $nome = mb_strtolower(trim($data['NM_CANDIDATO'] ?? $data['nome_candidato'] ?? ''));

                    if (! $nome) {
                        return;
                    }

                    $politicianId = $politiciansByName->get($nome);

                    if (! $politicianId) {
                        return;
                    }

                    $receitaTotal = (float) ($data['VR_RECEITA'] ?? $data['valor_receita'] ?? 0);

                    if ($receitaTotal > 0) {
                        $totals[$politicianId] = ($totals[$politicianId] ?? 0) + $receitaTotal;
                    }

                    if ($count % 50000 === 0) {
                        $this->info("    Processados {$count} registros...");
                    }
                });
            }

            $this->info('  Salvando financiamentos...');
            $matched = 0;

            foreach ($totals as $politicianId => $total) {
                CampaignFinancing::updateOrCreate(
                    [
                        'politician_id' => $politicianId,
                        'election_year' => $anoEleitoral,
                        'source' => 'TSE - Receita',
                        'type' => 'receita',
                    ],
                    [
                        'value' => $total,
                    ]
                );
                $matched++;
            }

            $this->finalizarJob($job, $count);
            $this->info("Processados {$count} registros, {$matched} financiamentos importados.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarVotosSenadores(): int
    {
        $this->info('Importando votos de senadores via API do Senado...');

        $job = $this->criarJob('votos_senadores');

        try {
            $senadores = Politician::where('external_id', 'LIKE', 'senado_%')
                ->select('id', 'external_id')
                ->get();

            if ($senadores->isEmpty()) {
                $this->warn('Nenhum senador encontrado. Execute primeiro: importar-dados senadores');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($senadores as $senador) {
                $codigo = (int) str_replace('senado_', '', $senador->external_id);
                $votos = $this->senado->getVotosSenador($codigo);

                foreach ($votos as $voto) {
                    $siglaMateria = $voto['SiglaMateria'] ?? '';
                    $numeroMateria = $voto['NumeroMateria'] ?? '';
                    $anoMateria = $voto['AnoMateria'] ?? '';
                    $descricaoVoto = $voto['DescricaoVoto'] ?? '';

                    if (! $descricaoVoto) {
                        continue;
                    }

                    $externalId = "senado_{$siglaMateria}_{$numeroMateria}_{$anoMateria}";

                    $session = VotingSession::firstOrCreate(
                        ['external_id' => $externalId],
                        [
                            'date' => $voto['DataSessao'] ?? null,
                            'description' => "{$siglaMateria} {$numeroMateria}/{$anoMateria}",
                        ]
                    );

                    Vote::updateOrCreate(
                        [
                            'voting_session_id' => $session->id,
                            'politician_id' => $senador->id,
                        ],
                        ['vote' => $descricaoVoto]
                    );

                    $count++;
                }

                $this->rateLimit();
            }

            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} votos de senadores.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarAutoriasSenadores(): int
    {
        $this->info('Importando autorias de senadores via API do Senado...');

        $job = $this->criarJob('autorias_senadores');

        try {
            $senadores = Politician::where('external_id', 'LIKE', 'senado_%')
                ->select('id', 'external_id')
                ->get();

            if ($senadores->isEmpty()) {
                $this->warn('Nenhum senador encontrado. Execute primeiro: importar-dados senadores');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($senadores as $senador) {
                $codigo = (int) str_replace('senado_', '', $senador->external_id);
                $autorias = $this->senado->getAutoriasSenador($codigo);

                foreach ($autorias as $autoria) {
                    $sigla = $autoria['SiglaMateria'] ?? '';
                    $numero = $autoria['NumeroMateria'] ?? '';
                    $ano = $autoria['AnoMateria'] ?? '';
                    $descricao = $autoria['DescricaoMateria'] ?? '';

                    if (! $sigla || ! $numero) {
                        continue;
                    }

                    $externalId = "senado_{$sigla}_{$numero}_{$ano}";

                    Bill::updateOrCreate(
                        ['external_id' => $externalId],
                        [
                            'title' => mb_substr("{$sigla} {$numero}/{$ano}".($descricao ? ': '.$descricao : ''), 0, 500),
                            'author_id' => $senador->id,
                            'status' => 'Em tramitação',
                            'year' => (int) $ano ?: null,
                        ]
                    );

                    $count++;
                }

                $this->rateLimit();
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} autorias de senadores.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarDiscursosCamara(): int
    {
        $limite = $this->getLimite();
        $this->info('Importando discursos de deputados da Câmara'.($limite ? " (limite: {$limite})" : '').'...');

        $job = $this->criarJob('discursos_camara');

        try {
            $query = Politician::whereNotNull('external_id')
                ->where('position', 'Deputado Federal');

            if ($limite) {
                $query->whereDoesntHave('speeches');
            }

            $deputados = $query->pluck('external_id', 'id');

            if ($deputados->isEmpty()) {
                $this->warn('Nenhum deputado encontrado.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($deputados as $politicianId => $externalId) {
                $discursos = $this->camara->getDiscursosDeputado((int) $externalId, 1, 50);

                foreach ($discursos as $discurso) {
                    $dataHora = $discurso['dataHoraInicio'] ?? null;

                    if (! $dataHora) {
                        continue;
                    }

                    Speech::updateOrCreate(
                        [
                            'politician_id' => $politicianId,
                            'source' => 'camara',
                            'date' => $dataHora,
                        ],
                        [
                            'external_id' => (string) ($discurso['idDiscurso'] ?? ''),
                            'title' => mb_substr($discurso['resume'] ?? '', 0, 500),
                            'resume' => $discurso['IndexLocalidade'] ?? null,
                            'session_name' => $discurso['nomeOrgao'] ?? null,
                            'uri' => $discurso['uriEvento'] ?? null,
                        ]
                    );

                    $count++;
                }

                if ($count % 50 === 0 && $count > 0) {
                    $this->info("  {$count} discursos importados...");
                }

                $this->rateLimit();

                if ($this->shouldStop($count, $limite)) {
                    break;
                }
            }

            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} discursos de deputados.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarEventosCamara(): int
    {
        $this->info('Importando eventos de deputados da Câmara...');

        $job = $this->criarJob('eventos_camara');

        try {
            $deputados = Politician::whereNotNull('external_id')
                ->where('position', 'Deputado Federal')
                ->pluck('external_id', 'id');

            if ($deputados->isEmpty()) {
                $this->warn('Nenhum deputado encontrado.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($deputados as $politicianId => $externalId) {
                $eventos = $this->camara->getEventosDeputado((int) $externalId, 1, 50);

                foreach ($eventos as $evento) {
                    Event::updateOrCreate(
                        [
                            'politician_id' => $politicianId,
                            'source' => 'camara',
                            'external_id' => (string) ($evento['id'] ?? ''),
                        ],
                        [
                            'title' => mb_substr($evento['descricao'] ?? '', 0, 500),
                            'type' => $evento['descricaoTipo'] ?? null,
                            'start_date' => $evento['dataInicio'] ?? null,
                            'end_date' => $evento['dataFim'] ?? null,
                            'location' => $evento['local'] ?? null,
                            'description' => $evento['descricao'] ?? null,
                        ]
                    );

                    $count++;
                }

                $this->rateLimit();
            }

            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} eventos de deputados.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarFrentesCamara(): int
    {
        $limite = $this->getLimite();
        $this->info('Importando frentes parlamentares da Câmara'.($limite ? " (limite: {$limite})" : '').'...');

        $job = $this->criarJob('frentes_camara');

        try {
            $query = Politician::whereNotNull('external_id')
                ->where('position', 'Deputado Federal');

            if ($limite) {
                $query->whereDoesntHave('parliamentaryFronts');
            }

            $deputados = $query->pluck('external_id', 'id');

            if ($deputados->isEmpty()) {
                $this->warn('Nenhum deputado encontrado.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($deputados as $politicianId => $externalId) {
                $frentes = $this->camara->getFrentesDeputado((int) $externalId);

                foreach ($frentes as $frente) {
                    ParliamentaryFront::updateOrCreate(
                        [
                            'politician_id' => $politicianId,
                            'external_id' => (string) ($frente['id'] ?? ''),
                        ],
                        [
                            'title' => mb_substr($frente['titulo'] ?? '', 0, 500),
                            'legislature' => $frente['idLegislatura'] ?? null,
                        ]
                    );

                    $count++;
                }

                if ($count % 50 === 0 && $count > 0) {
                    $this->info("  {$count} frentes importadas...");
                }

                $this->rateLimit();

                if ($this->shouldStop($count, $limite)) {
                    break;
                }
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} frentes parlamentares.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarOrgaosCamara(): int
    {
        $this->info('Importando órgãos/comissões de deputados da Câmara...');

        $job = $this->criarJob('orgaos_camara');

        try {
            $deputados = Politician::whereNotNull('external_id')
                ->where('position', 'Deputado Federal')
                ->pluck('external_id', 'id');

            if ($deputados->isEmpty()) {
                $this->warn('Nenhum deputado encontrado.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($deputados as $politicianId => $externalId) {
                $orgaos = $this->camara->getOrgaosDeputado((int) $externalId);

                foreach ($orgaos as $orgao) {
                    CommitteeMembership::updateOrCreate(
                        [
                            'politician_id' => $politicianId,
                            'source' => 'camara',
                            'external_id' => (string) ($orgao['idOrgao'] ?? ''),
                        ],
                        [
                            'name' => mb_substr($orgao['nomeOrgao'] ?? '', 0, 500),
                            'acronym' => $orgao['siglaOrgao'] ?? null,
                            'role' => $orgao['titulo'] ?? null,
                            'start_date' => $orgao['dataInicio'] ?? null,
                            'end_date' => $orgao['dataFim'] ?? null,
                        ]
                    );

                    $count++;
                }

                $this->rateLimit();
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} membros de órgãos da Câmara.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarOrientacoesVotacao(): int
    {
        $limite = $this->getLimite();
        $this->info('Importando orientações partidárias das votações'.($limite ? " (limite: {$limite})" : '').'...');

        $job = $this->criarJob('orientacoes_votacao');

        try {
            $query = VotingSession::whereNotNull('external_id')
                ->where('external_id', 'NOT LIKE', 'senado_%');

            if ($limite) {
                $query->whereDoesntHave('partyOrientations');
            }

            $sessions = $query->pluck('external_id', 'id');

            if ($sessions->isEmpty()) {
                $this->warn('Nenhuma sessão de votação encontrada.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($sessions as $sessionId => $externalId) {
                $orientacoes = $this->camara->getOrientacoesVotacao($externalId);

                foreach ($orientacoes as $orientacao) {
                    PartyOrientation::updateOrCreate(
                        [
                            'voting_session_id' => $sessionId,
                            'party_acronym' => $orientacao['siglaBancada'] ?? '',
                        ],
                        [
                            'orientation' => $orientacao['orientacao'] ?? '',
                        ]
                    );

                    $count++;
                }

                if ($count % 100 === 0 && $count > 0) {
                    $this->info("  {$count} orientações importadas...");
                }

                $this->rateLimit();

                if ($this->shouldStop($count, $limite)) {
                    break;
                }
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} orientações partidárias.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarTemasProposicoes(): int
    {
        $limite = $this->getLimite();
        $this->info('Importando temas das proposições'.($limite ? " (limite: {$limite})" : '').'...');

        $job = $this->criarJob('temas_proposicoes');

        try {
            $query = Bill::whereNotNull('external_id')
                ->where('external_id', 'NOT LIKE', 'senado_%');

            if ($limite) {
                $query->whereDoesntHave('themes');
            }

            $bills = $query->pluck('external_id', 'id');

            if ($bills->isEmpty()) {
                $this->warn('Nenhuma proposição encontrada.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($bills as $billId => $externalId) {
                $temas = $this->camara->getTemasProposicao((int) $externalId);

                foreach ($temas as $tema) {
                    BillTheme::updateOrCreate(
                        [
                            'bill_id' => $billId,
                            'external_id' => (string) ($tema['codTema'] ?? ''),
                        ],
                        [
                            'theme_name' => mb_substr($tema['nomeTema'] ?? '', 0, 300),
                        ]
                    );

                    $count++;
                }

                if ($count % 100 === 0 && $count > 0) {
                    $this->info("  {$count} temas importados...");
                }

                $this->rateLimit();

                if ($this->shouldStop($count, $limite)) {
                    break;
                }
            }

            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} temas de proposições.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarTramitacaoProposicoes(): int
    {
        $limite = $this->getLimite();
        $this->info('Importando tramitação das proposições'.($limite ? " (limite: {$limite})" : '').'...');

        $job = $this->criarJob('tramitacao_proposicoes');

        try {
            $query = Bill::whereNotNull('external_id')
                ->where('external_id', 'NOT LIKE', 'senado_%');

            if ($limite) {
                $query->whereDoesntHave('progress');
            }

            $bills = $query->pluck('external_id', 'id');

            if ($bills->isEmpty()) {
                $this->warn('Nenhuma proposição encontrada.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($bills as $billId => $externalId) {
                $tramitacoes = $this->camara->getTramitacaoProposicao((int) $externalId);

                foreach ($tramitacoes as $tramitacao) {
                    BillProgress::updateOrCreate(
                        ['bill_id' => $billId, 'sequence_number' => $tramitacao['sequencia'] ?? null],
                        [
                            'external_id' => (string) ($tramitacao['sequencia'] ?? ''),
                            'description' => mb_substr($tramitacao['descricaoSituacao'] ?? $tramitacao['descricaoTramitacao'] ?? '', 0, 500),
                            'date' => $tramitacao['dataHora'] ?? null,
                        ]
                    );

                    $count++;
                }

                if ($count % 100 === 0 && $count > 0) {
                    $this->info("  {$count} registros de tramitação importados...");
                }

                $this->rateLimit();

                if ($this->shouldStop($count, $limite)) {
                    break;
                }
            }

            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} registros de tramitação.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarAutoresProposicoes(): int
    {
        $limite = $this->getLimite();
        $this->info('Importando co-autores das proposições'.($limite ? " (limite: {$limite})" : '').'...');

        $job = $this->criarJob('autores_proposicoes');

        try {
            $query = Bill::whereNotNull('external_id')
                ->where('external_id', 'NOT LIKE', 'senado_%');

            if ($limite) {
                $query->whereDoesntHave('coauthors');
            }

            $bills = $query->pluck('external_id', 'id');

            if ($bills->isEmpty()) {
                $this->warn('Nenhuma proposição encontrada.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($bills as $billId => $externalId) {
                $autores = $this->camara->getAutoresProposicao((int) $externalId);

                foreach ($autores as $autor) {
                    $uri = $autor['uri'] ?? '';
                    $nome = $autor['nome'] ?? $autor['nomeCivil'] ?? '';
                    $proponente = $autor['proponente'] ?? 0;

                    if (! $nome) {
                        continue;
                    }

                    $deputadoId = null;
                    if (preg_match('#/deputados/(\d+)#', $uri, $matches)) {
                        $deputadoId = $matches[1];
                    }

                    $politicianId = null;
                    if ($deputadoId) {
                        $politician = Politician::where('external_id', $deputadoId)->first();
                        $politicianId = $politician?->id;
                    }

                    BillCoauthor::updateOrCreate(
                        [
                            'bill_id' => $billId,
                            'author_external_id' => $deputadoId ?? '',
                        ],
                        [
                            'politician_id' => $politicianId,
                            'author_name' => mb_substr($nome, 0, 300),
                        ]
                    );

                    if ($proponente && $politicianId) {
                        Bill::where('id', $billId)->update(['author_id' => $politicianId]);
                    }

                    $count++;
                }

                if ($count % 100 === 0) {
                    $this->info("  {$count} co-autores importados...");
                }

                $this->rateLimit();

                if ($this->shouldStop($count, $limite)) {
                    break;
                }
            }

            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} co-autores de proposições.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarBlocos(): int
    {
        $this->info('Importando blocos parlamentares da Câmara...');

        $job = $this->criarJob('blocos_camara');

        try {
            $blocos = $this->camara->getBlocos();
            $count = 0;

            foreach ($blocos as $bloco) {
                $blocModel = ParliamentaryBloc::updateOrCreate(
                    ['external_id' => (string) ($bloco['id'] ?? '')],
                    [
                        'name' => mb_substr($bloco['nome'] ?? '', 0, 500),
                        'legislature' => $bloco['idLegislatura'] ?? null,
                        'is_federation' => ($bloco['federacao'] ?? false) === true,
                    ]
                );

                $membros = $this->camara->getMembrosBloco((int) ($bloco['id'] ?? 0));

                foreach ($membros as $membro) {
                    $idDep = $membro['id'] ?? null;

                    if (! $idDep) {
                        continue;
                    }

                    $politician = Politician::where('external_id', (string) $idDep)->first();

                    if ($politician) {
                        $blocModel->members()->syncWithoutDetaching([$politician->id]);
                    }
                }

                $count++;
                $this->rateLimit();
            }

            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} blocos parlamentares.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarComissoesSenado(): int
    {
        $this->info('Importando comissões de senadores...');

        $job = $this->criarJob('comissoes_senado');

        try {
            $senadores = Politician::where('external_id', 'LIKE', 'senado_%')
                ->select('id', 'external_id')
                ->get();

            if ($senadores->isEmpty()) {
                $this->warn('Nenhum senador encontrado.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($senadores as $senador) {
                $codigo = (int) str_replace('senado_', '', $senador->external_id);
                $comissoes = $this->senado->getComissoesSenador($codigo);

                foreach ($comissoes as $comissao) {
                    $ident = $comissao['IdentificacaoComissao'] ?? [];

                    CommitteeMembership::updateOrCreate(
                        [
                            'politician_id' => $senador->id,
                            'source' => 'senado',
                            'external_id' => (string) ($ident['CodigoComissao'] ?? ''),
                        ],
                        [
                            'name' => mb_substr($ident['NomeComissao'] ?? '', 0, 500),
                            'acronym' => $ident['SiglaComissao'] ?? null,
                            'role' => $comissao['DescricaoParticipacao'] ?? null,
                            'start_date' => $comissao['DataInicio'] ?? null,
                            'end_date' => $comissao['DataFim'] ?? null,
                        ]
                    );

                    $count++;
                }

                $this->rateLimit();
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} membros de comissões do Senado.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarDiscursosSenado(): int
    {
        $this->info('Importando discursos de senadores...');

        $job = $this->criarJob('discursos_senado');

        try {
            $senadores = Politician::where('external_id', 'LIKE', 'senado_%')
                ->select('id', 'external_id')
                ->get();

            if ($senadores->isEmpty()) {
                $this->warn('Nenhum senador encontrado.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($senadores as $senador) {
                $codigo = (int) str_replace('senado_', '', $senador->external_id);
                $discursos = $this->senado->getDiscursosSenador($codigo);

                foreach ($discursos as $discurso) {
                    $data = $discurso['Data'] ?? null;

                    if (! $data) {
                        continue;
                    }

                    Speech::updateOrCreate(
                        [
                            'politician_id' => $senador->id,
                            'source' => 'senado',
                            'date' => $data.' '.($discurso['HoraInicio'] ?? '00:00:00'),
                        ],
                        [
                            'external_id' => (string) ($discurso['CodigoPublicoReunião'] ?? $discurso['Codigo'] ?? ''),
                            'title' => mb_substr($discurso['NomeOrgao'] ?? '', 0, 500),
                            'resume' => $discurso['Resumo'] ?? null,
                            'session_name' => $discurso['NomeOrgao'] ?? null,
                        ]
                    );

                    $count++;
                }

                $this->rateLimit();
            }

            $this->finalizarJob($job, $count);
            $this->info("Importados {$count} discursos de senadores.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarRelatoriasSenado(): int
    {
        $this->info('Importando relatorias de senadores...');

        $job = $this->criarJob('relatorias_senado');

        try {
            $senadores = Politician::where('external_id', 'LIKE', 'senado_%')
                ->select('id', 'external_id')
                ->get();

            if ($senadores->isEmpty()) {
                $this->warn('Nenhum senador encontrado.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($senadores as $senador) {
                $codigo = (int) str_replace('senado_', '', $senador->external_id);
                $relatorias = $this->senado->getRelatoriasSenador($codigo);

                foreach ($relatorias as $relatoria) {
                    $materia = $relatoria['Materia'] ?? [];

                    Rapporteurship::updateOrCreate(
                        [
                            'politician_id' => $senador->id,
                            'bill_external_id' => (string) ($materia['Codigo'] ?? ''),
                        ],
                        [
                            'bill_description' => mb_substr($materia['DescricaoIdentificacao'] ?? '', 0, 500),
                            'bill_ementa' => $materia['Ementa'] ?? null,
                            'commission_name' => $relatoria['Comissao']['Nome'] ?? null,
                            'start_date' => $relatoria['DataDesignacao'] ?? null,
                            'end_date' => $relatoria['DataDestituicao'] ?? null,
                            'removal_reason' => $relatoria['DescricaoMotivoDestituicao'] ?? null,
                        ]
                    );

                    $count++;
                }

                $this->rateLimit();
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} relatorias de senadores.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }

    private function importarLiderancasSenado(): int
    {
        $this->info('Importando lideranças de senadores...');

        $job = $this->criarJob('liderancas_senado');

        try {
            $senadores = Politician::where('external_id', 'LIKE', 'senado_%')
                ->select('id', 'external_id')
                ->get();

            if ($senadores->isEmpty()) {
                $this->warn('Nenhum senador encontrado.');

                return self::FAILURE;
            }

            $count = 0;

            foreach ($senadores as $senador) {
                $codigo = (int) str_replace('senado_', '', $senador->external_id);
                $liderancas = $this->senado->getLiderancasSenador($codigo);

                foreach ($liderancas as $lideranca) {
                    LeadershipPosition::updateOrCreate(
                        [
                            'politician_id' => $senador->id,
                            'position' => mb_substr($lideranca['UnidadeLideranca'] ?? $lideranca['DescricaoTipoLideranca'] ?? '', 0, 300),
                        ],
                        [
                            'party_acronym' => $lideranca['Partido']['SiglaPartido'] ?? null,
                            'house' => $lideranca['SiglaCasaLideranca'] ?? null,
                            'start_date' => $lideranca['DataDesignacao'] ?? null,
                            'end_date' => $lideranca['DataFim'] ?? null,
                        ]
                    );

                    $count++;
                }

                $this->rateLimit();
            }

            $this->finalizarJob($job, $count);
            $this->info("Importadas {$count} lideranças de senadores.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
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

        Log::error('Falha na importação de dados', [
            'job_id' => $job->id,
            'error' => $e->getMessage(),
        ]);

        $this->error("Erro: {$e->getMessage()}");
    }

    private function vincularBillsAutores(): int
    {
        $limite = $this->getLimite();
        $this->info('Vinculando autores principais aos bills...'.($limite ? " (limite: {$limite})" : ''));

        $job = $this->criarJob('vincular_bills_autores');

        try {
            $bills = Bill::whereNull('author_id')
                ->whereNotNull('external_id')
                ->where('external_id', 'NOT LIKE', 'senado_%')
                ->pluck('id', 'external_id');

            if ($bills->isEmpty()) {
                $this->warn('Todos os bills já possuem author_id.');

                return self::FAILURE;
            }

            $this->info('  '.count($bills).' bills sem author_id. Buscando autores na API...');

            $count = 0;
            $linked = 0;

            foreach ($bills as $billId => $externalId) {
                $autores = $this->camara->getAutoresProposicao((int) $externalId);

                if (empty($autores)) {
                    $this->rateLimit();
                    if ($this->shouldStop($count, $limite)) {
                        break;
                    }

                    continue;
                }

                $author = $autores[0];
                $uri = $author['uri'] ?? '';
                $nome = $author['nome'] ?? '';

                $deputadoId = null;
                if (preg_match('#/deputados/(\d+)#', $uri, $matches)) {
                    $deputadoId = $matches[1];
                }

                $politicianId = null;
                if ($deputadoId) {
                    $politician = Politician::where('external_id', $deputadoId)->first();
                    $politicianId = $politician?->id;
                }

                if ($politicianId) {
                    Bill::where('id', $billId)->update(['author_id' => $politicianId]);
                    $linked++;
                }

                $count++;

                if ($count % 50 === 0) {
                    $this->info("  Processados {$count}/".count($bills)." bills (vinculados: {$linked})...");
                }

                $this->rateLimit();

                if ($this->shouldStop($count, $limite)) {
                    break;
                }
            }

            $this->finalizarJob($job, $count);
            $this->info("Processados {$count} bills. Vinculados {$linked} autores.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->falharJob($job, $e);

            return self::FAILURE;
        }
    }
}
