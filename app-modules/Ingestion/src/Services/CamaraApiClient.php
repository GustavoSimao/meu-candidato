<?php

namespace MeuCandidato\Ingestion\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CamaraApiClient
{
    private const BASE_URL = 'https://dadosabertos.camara.leg.br/api/v2';

    private const ARQUIVOS_URL = 'https://dadosabertos.camara.leg.br/arquivos';

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_MS = 1000;

    private function requestWithRetry(string $method, string $url, array $options = []): ?Response
    {
        $attempts = 0;

        while ($attempts < self::MAX_RETRIES) {
            try {
                $response = Http::timeout(60)->$method($url, $options);

                if ($response->successful()) {
                    return $response;
                }

                if ($response->status() === 429) {
                    $attempts++;
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempts);

                    continue;
                }

                return $response;
            } catch (ConnectionException $e) {
                $attempts++;

                if ($attempts >= self::MAX_RETRIES) {
                    throw $e;
                }

                usleep(self::RETRY_DELAY_MS * 1000 * $attempts);
            }
        }

        return null;
    }

    public function getDeputados(int $pagina = 1, int $itensPorPagina = 100): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/deputados', [
            'itens' => $itensPorPagina,
            'pagina' => $pagina,
            'ordem' => 'ASC',
            'ordenarPor' => 'nome',
        ]);

        if ($response === null || $response->failed()) {
            Log::error('Erro ao buscar deputados da Câmara', [
                'status' => $response?->status(),
            ]);

            return [];
        }

        return $response->json('dados', []);
    }

    public function getDeputadoById(int $id): ?array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/deputados/'.$id);

        if ($response === null || $response->failed()) {
            return null;
        }

        return $response->json('dados');
    }

    public function getPartidos(): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/partidos', [
            'itens' => 100,
            'pagina' => 1,
            'ordem' => 'ASC',
            'ordenarPor' => 'sigla',
        ]);

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getProposicoes(int $ano, int $pagina = 1, int $itensPorPagina = 100): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/proposicoes', [
            'ano' => $ano,
            'itens' => $itensPorPagina,
            'pagina' => $pagina,
            'ordem' => 'ASC',
            'ordenarPor' => 'id',
        ]);

        if ($response === null || $response->failed()) {
            Log::error('Erro ao buscar proposições da Câmara', [
                'ano' => $ano,
                'status' => $response?->status(),
            ]);

            return [];
        }

        return $response->json('dados', []);
    }

    public function getVotacoes(string $dataInicio, string $dataFim, int $pagina = 1, int $itensPorPagina = 100): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/votacoes', [
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'itens' => $itensPorPagina,
            'pagina' => $pagina,
            'ordem' => 'ASC',
            'ordenarPor' => 'id',
        ]);

        if ($response === null || $response->failed()) {
            Log::error('Erro ao buscar votações da Câmara', [
                'dataInicio' => $dataInicio,
                'dataFim' => $dataFim,
                'status' => $response?->status(),
            ]);

            return [];
        }

        return $response->json('dados', []);
    }

    public function getVotacao(string $idVotacao): ?array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/votacoes/'.$idVotacao);

        if ($response === null || $response->failed()) {
            return null;
        }

        return $response->json('dados', []);
    }

    public function getVotosVotacao(string $idVotacao): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/votacoes/'.$idVotacao.'/votos');

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getDespesasDeputado(int $idDeputado, int $ano, int $pagina = 1, int $itensPorPagina = 100, ?int $mes = null): array
    {
        $params = [
            'ano' => $ano,
            'itens' => $itensPorPagina,
            'pagina' => $pagina,
            'ordem' => 'ASC',
            'ordenarPor' => 'ano',
        ];

        if ($mes !== null) {
            $params['mes'] = $mes;
        }

        $response = $this->requestWithRetry('get', self::BASE_URL.'/deputados/'.$idDeputado.'/despesas', $params);

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function downloadArquivoJson(string $tipo, int $ano): ?string
    {
        $url = self::ARQUIVOS_URL.'/'.$tipo.'/json/'.$tipo.'-'.$ano.'.json';

        $response = $this->requestWithRetry('get', $url);

        if ($response === null || $response->failed()) {
            Log::error('Erro ao baixar arquivo da Câmara', [
                'url' => $url,
                'status' => $response?->status(),
            ]);

            return null;
        }

        return $response->body();
    }

    public function streamVotosBulk(int $ano, callable $callback): void
    {
        $url = self::ARQUIVOS_URL.'/votacoesVotos/json/votacoesVotos-'.$ano.'.json';
        $tempFile = storage_path('app/temp-votos-'.$ano.'.json');

        try {
            $response = Http::timeout(120)->withOptions(['stream' => true])->get($url);

            if ($response->failed()) {
                Log::error('Erro ao baixar bulk de votos', ['url' => $url, 'status' => $response->status()]);

                return;
            }

            $body = $response->getBody();
            $handle = fopen($tempFile, 'w');

            while (! $body->eof()) {
                fwrite($handle, $body->read(65536));
            }

            fclose($handle);
            unset($body, $response);

            $this->processarVotosBulk($tempFile, $callback);
        } catch (\Throwable $e) {
            Log::error('Erro no streaming de votos', ['ano' => $ano, 'error' => $e->getMessage()]);
        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    private function processarVotosBulk(string $file, callable $callback): void
    {
        $handle = fopen($file, 'r');
        if (! $handle) {
            return;
        }

        $state = '寻找 dados';
        $braceDepth = 0;
        $objectBuffer = '';
        $inString = false;
        $escaped = false;

        while (($line = fgets($handle)) !== false) {
            $len = strlen($line);

            for ($i = 0; $i < $len; $i++) {
                $char = $line[$i];

                if ($state === '寻找 dados') {
                    if ($char === '"' && substr($line, $i, 7) === '"dados"') {
                        $state = '在 dados 中';
                        $i += 6;
                    }

                    continue;
                }

                if ($state === '在 dados 中') {
                    if ($char === '{') {
                        $state = '在对象中';
                        $objectBuffer = '{';
                        $braceDepth = 1;
                    }

                    continue;
                }

                if ($state === '在对象中') {
                    if ($escaped) {
                        $escaped = false;
                        $objectBuffer .= $char;

                        continue;
                    }

                    if ($char === '\\' && $inString) {
                        $escaped = true;
                        $objectBuffer .= $char;

                        continue;
                    }

                    if ($char === '"') {
                        $inString = ! $inString;
                        $objectBuffer .= $char;

                        continue;
                    }

                    $objectBuffer .= $char;

                    if (! $inString) {
                        if ($char === '{') {
                            $braceDepth++;
                        } elseif ($char === '}') {
                            $braceDepth--;
                            if ($braceDepth === 0) {
                                $obj = json_decode($objectBuffer, true);
                                if (is_array($obj)) {
                                    $callback($obj);
                                }
                                $state = '在 dados 中';
                                $objectBuffer = '';
                                $inString = false;
                            }
                        }
                    }
                }
            }
        }

        fclose($handle);
    }

    public function getMandatosDeputado(int $idDeputado): array
    {
        $deputado = $this->getDeputadoById($idDeputado);

        if ($deputado === null) {
            return [];
        }

        $status = $deputado['ultimoStatus'] ?? null;

        if (! is_array($status)) {
            return [];
        }

        $dataInicio = $status['data'] ?? null;

        if (! $dataInicio) {
            return [];
        }

        return [[
            'dataInicio' => $dataInicio,
            'dataFim' => null,
        ]];
    }

    public function getDiscursosDeputado(int $idDeputado, int $pagina = 1, int $itensPorPagina = 20): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/deputados/'.$idDeputado.'/discursos', [
            'itens' => $itensPorPagina,
            'pagina' => $pagina,
            'ordem' => 'DESC',
            'ordenarPor' => 'dataHoraInicio',
        ]);

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getEventosDeputado(int $idDeputado, int $pagina = 1, int $itensPorPagina = 20): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/deputados/'.$idDeputado.'/eventos', [
            'itens' => $itensPorPagina,
            'pagina' => $pagina,
            'ordem' => 'DESC',
            'ordenarPor' => 'dataInicio',
        ]);

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getFrentesDeputado(int $idDeputado): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/deputados/'.$idDeputado.'/frentes');

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getOrgaosDeputado(int $idDeputado): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/deputados/'.$idDeputado.'/orgaos');

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getOrientacoesVotacao(string $idVotacao): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/votacoes/'.$idVotacao.'/orientacoes');

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getTemasProposicao(int $idProposicao): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/proposicoes/'.$idProposicao.'/temas');

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getTramitacaoProposicao(int $idProposicao): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/proposicoes/'.$idProposicao.'/tramitacoes');

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getAutoresProposicao(int $idProposicao): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/proposicoes/'.$idProposicao.'/autores');

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getBlocos(int $pagina = 1, int $itensPorPagina = 100): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/blocos', [
            'itens' => $itensPorPagina,
            'pagina' => $pagina,
            'ordem' => 'ASC',
            'ordenarPor' => 'nome',
        ]);

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getMembrosBloco(int $idBloco): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/blocos/'.$idBloco.'/membros');

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json('dados', []);
    }

    public function getTotalDeputados(): int
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/deputados', [
            'itens' => 1,
            'pagina' => 1,
        ]);

        if ($response === null || $response->failed()) {
            return 0;
        }

        $links = $response->json('links', []);

        if (! is_array($links)) {
            return 0;
        }

        foreach ($links as $link) {
            if (($link['rel'] ?? '') === 'last') {
                $href = $link['href'] ?? '';
                if (preg_match('/pagina=(\d+)/', $href, $matches)) {
                    return (int) $matches[1];
                }
            }
        }

        return 0;
    }
}
