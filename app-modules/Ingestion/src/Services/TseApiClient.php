<?php

namespace MeuCandidato\Ingestion\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TseApiClient
{
    private const CDN_BASE = 'https://cdn.tse.jus.br/estatistica/sead/odsele/prestacao_contas';

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_MS = 2000;

    private function requestWithRetry(string $url, string $destPath): bool
    {
        $attempts = 0;

        while ($attempts < self::MAX_RETRIES) {
            try {
                $response = Http::timeout(300)
                    ->withHeaders(['Accept' => '*/*'])
                    ->sink($destPath)
                    ->get($url);

                if ($response->successful()) {
                    return true;
                }

                if ($response->status() === 429) {
                    $attempts++;
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempts);

                    continue;
                }

                Log::warning('TSE download failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return false;
            } catch (ConnectionException $e) {
                $attempts++;

                if ($attempts >= self::MAX_RETRIES) {
                    Log::error('TSE download failed after retries', [
                        'url' => $url,
                        'error' => $e->getMessage(),
                    ]);

                    return false;
                }

                usleep(self::RETRY_DELAY_MS * 1000 * $attempts);
            }
        }

        return false;
    }

    public function downloadCandidatosCsv(int $ano, string $destDir): ?string
    {
        $filename = "prestacao_de_contas_eleitorais_candidatos_{$ano}.zip";
        $url = self::CDN_BASE.'/'.$filename;
        $destPath = $destDir.'/'.$filename;

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if ($this->requestWithRetry($url, $destPath)) {
            return $destPath;
        }

        return null;
    }

    public function extractReceitasCsvs(string $zipPath): array
    {
        return $this->extractCsvsByPattern($zipPath, 'receitas_candidatos_');
    }

    public function extractBensCsvs(string $zipPath): array
    {
        return $this->extractCsvsByPattern($zipPath, 'bens_candidatos_');
    }

    public function extractCandidatosCsvs(string $zipPath): array
    {
        return $this->extractCsvsByPattern($zipPath, 'candidatos_');
    }

    private function extractCsvsByPattern(string $zipPath, string $prefix): array
    {
        $extractDir = dirname($zipPath).'/extracted_'.basename($zipPath, '.zip');

        if (! is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath) !== true) {
            Log::error('Falha ao abrir ZIP TSE', ['path' => $zipPath]);

            return [];
        }

        $zip->extractTo($extractDir);
        $zip->close();

        $csvFiles = glob($extractDir.'/'.$prefix.'*.csv');

        usort($csvFiles, function ($a, $b) {
            $aBras = str_contains($a, 'BRASIL');
            $bBras = str_contains($b, 'BRASIL');
            if ($aBras && ! $bBras) {
                return -1;
            }
            if (! $aBras && $bBras) {
                return 1;
            }

            return strcmp($a, $b);
        });

        return $csvFiles;
    }

    public function streamReceitasCsv(string $csvPath, callable $callback): int
    {
        return $this->streamCsv($csvPath, $callback);
    }

    public function streamCsv(string $csvPath, callable $callback): int
    {
        if (! file_exists($csvPath)) {
            return 0;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            return 0;
        }

        $rawHeader = fgets($handle);
        if ($rawHeader === false) {
            fclose($handle);

            return 0;
        }

        $detected = mb_detect_encoding($rawHeader, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        $encoding = $detected ?: 'ISO-8859-1';
        $needsConvert = $encoding !== 'UTF-8';

        $headerStr = $needsConvert ? mb_convert_encoding($rawHeader, 'UTF-8', $encoding) : $rawHeader;
        $header = str_getcsv($headerStr, ';');
        $count = 0;

        while (($rawLine = fgets($handle)) !== false) {
            $line = $needsConvert ? mb_convert_encoding($rawLine, 'UTF-8', $encoding) : $rawLine;
            $row = str_getcsv($line, ';');

            if (count($row) !== count($header)) {
                continue;
            }

            $data = array_combine($header, $row);

            $callback($data);
            $count++;
        }

        fclose($handle);

        return $count;
    }

    public function cleanup(string $zipPath, ?string $csvPath): void
    {
        if ($zipPath && file_exists($zipPath)) {
            @unlink($zipPath);
        }

        if ($csvPath) {
            $extractDir = dirname($csvPath);
            if (is_dir($extractDir)) {
                $files = array_merge(
                    glob($extractDir.'/*') ?: [],
                    glob($extractDir.'/**/*') ?: []
                );
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
                @rmdir($extractDir);
            }
        }
    }
}
