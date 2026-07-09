<?php

namespace MeuCandidato\Ingestion\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SenadoApiClient
{
    private const BASE_URL = 'https://legis.senado.leg.br/dadosabertos';

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_MS = 1000;

    private function requestWithRetry(string $method, string $url, array $options = []): ?Response
    {
        $attempts = 0;

        while ($attempts < self::MAX_RETRIES) {
            try {
                $response = Http::timeout(60)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->$method($url, $options);

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
