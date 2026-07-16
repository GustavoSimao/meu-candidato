<?php

namespace MeuCandidato\Ingestion\Services;

use MeuCandidato\Ingestion\Support\WithRetry;

class SenadoApiClient
{
    use WithRetry;

    private const BASE_URL = 'https://legis.senado.leg.br/dadosabertos';

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_MS = 1000;

    protected function retryHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }

    private function normalizeToArray(mixed $data): array
    {
        if ($data === null) {
            return [];
        }

        return is_array($data) ? $data : [$data];
    }

    public function getSenadoresAtuais(): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/senador/lista/atual');

        if ($response === null || $response->failed()) {
            Log::error('Erro ao buscar senadores atuais', [
                'status' => $response?->status(),
            ]);

            return [];
        }

        $data = $response->json('ListaParlamentarEmExercicio.Parlamentares.Parlamentar');

        return $this->normalizeToArray($data);
    }

    public function getJson(string $path): ?array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/'.$path);

        if ($response === null || $response->failed()) {
            return null;
        }

        return $response->json();
    }

    public function getSenadoresPorLegislatura(int $legislatura): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/senador/lista/legislatura/'.$legislatura);

        if ($response === null || $response->failed()) {
            Log::error('Erro ao buscar senadores da legislatura', [
                'legislatura' => $legislatura,
                'status' => $response?->status(),
            ]);

            return [];
        }

        $data = $response->json('ListaParlamentaresLegislatura.Parlamentares.Parlamentar');

        return $this->normalizeToArray($data);
    }

    public function getDetalhesSenador(int $codigo): ?array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/senador/'.$codigo);

        if ($response === null || $response->failed()) {
            return null;
        }

        return $response->json('DetalhesParlamentar.Parlamentar');
    }

    public function getAutoriasSenador(int $codigo): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/senador/'.$codigo.'/autorias');

        if ($response === null || $response->failed()) {
            return [];
        }

        $data = $response->json('AutoriasParlamentar.Materias.Materia');

        return $this->normalizeToArray($data);
    }

    public function getVotosSenador(int $codigo): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/senador/'.$codigo.'/votos');

        if ($response === null || $response->failed()) {
            return [];
        }

        $data = $response->json('VotosParlamentar.Votacoes.Votacao');

        return $this->normalizeToArray($data);
    }

    public function getMandatosSenador(int $codigo): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/senador/'.$codigo.'/mandatos');

        if ($response === null || $response->failed()) {
            return [];
        }

        $mandatos = $response->json('MandatoParlamentar.Parlamentar.Mandatos.Mandato');

        $result = [];
        foreach ($this->normalizeToArray($mandatos) as $mandato) {
            $legInicio = $mandato['PrimeiraLegislaturaDoMandato'] ?? [];
            $result[] = [
                'DataInicio' => $legInicio['DataInicio'] ?? null,
                'DataFim' => $legInicio['DataFim'] ?? null,
            ];
        }

        return $result;
    }

    public function getComissoesSenador(int $codigo): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/senador/'.$codigo.'/comissoes');

        if ($response === null || $response->failed()) {
            return [];
        }

        $data = $response->json('MembroComissaoParlamentar.Parlamentar.MembroComissoes.Comissao');

        return $this->normalizeToArray($data);
    }

    public function getDiscursosSenador(int $codigo): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/senador/'.$codigo.'/discursos');

        if ($response === null || $response->failed()) {
            return [];
        }

        $data = $response->json('DiscursosParlamentar.Parlamentar.Pronunciamentos.Pronunciamento');

        return $this->normalizeToArray($data);
    }

    public function getLiderancasSenador(int $codigo): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/senador/'.$codigo.'/liderancas');

        if ($response === null || $response->failed()) {
            return [];
        }

        $data = $response->json('LiderancaParlamentar.Parlamentar.Liderancas.Lideranca');

        return $this->normalizeToArray($data);
    }

    public function getRelatoriasSenador(int $codigo): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/senador/'.$codigo.'/relatorias');

        if ($response === null || $response->failed()) {
            return [];
        }

        $data = $response->json('MateriasRelatoriaParlamentar.Parlamentar.Relatorias.Relatoria');

        return $this->normalizeToArray($data);
    }

    public function getComissoes(): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/comissao/lista/colegiados');

        if ($response === null || $response->failed()) {
            return [];
        }

        $data = $response->json('ListaColegiados.Colegiados.Colegiado');

        return $this->normalizeToArray($data);
    }

    public function getPartidos(): array
    {
        $response = $this->requestWithRetry('get', self::BASE_URL.'/senador/lista/atual');

        if ($response === null || $response->failed()) {
            return [];
        }

        $senadores = $response->json('ListaParlamentarEmExercicio.Parlamentares.Parlamentar');
        $partidos = [];

        $senadores = $this->normalizeToArray($senadores);

        foreach ($senadores as $senador) {
            $sigla = $senador['SiglaPartidoParlamentar'] ?? null;
            if ($sigla && ! in_array($sigla, $partidos)) {
                $partidos[] = $sigla;
            }
        }

        return $partidos;
    }
}
