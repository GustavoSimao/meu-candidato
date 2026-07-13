<?php

namespace Tests\Unit;

use MeuCandidato\Ingestion\Services\TseApiClient;
use Tests\TestCase;

class TseApiClientTest extends TestCase
{
    public function test_tse_api_client_can_be_instantiated(): void
    {
        $client = new TseApiClient;

        $this->assertInstanceOf(TseApiClient::class, $client);
    }

    public function test_extract_receitas_csvs_returns_empty_for_invalid_zip(): void
    {
        $client = new TseApiClient;

        $result = $client->extractReceitasCsvs('/nonexistent/file.zip');

        $this->assertEmpty($result);
    }

    public function test_stream_receitas_csv_returns_zero_for_nonexistent_file(): void
    {
        $client = new TseApiClient;

        $count = $client->streamReceitasCsv('/nonexistent/file.csv', function () {
            // Should not be called
        });

        $this->assertEquals(0, $count);
    }

    public function test_stream_receitas_csv_parses_semicolon_delimited_data(): void
    {
        $client = new TseApiClient;

        $tmpFile = tempnam(sys_get_temp_dir(), 'tse_test');
        file_put_contents($tmpFile, "SQ_CANDIDATO;NM_CANDIDATO;SG_PARTIDO;VR_RECEITA;VR_DESPESA\n12345;João da Silva;PT;50000.00;30000.00\n12346;Maria Santos;PSDB;75000.00;45000.00\n");

        $rows = [];
        $client->streamReceitasCsv($tmpFile, function (array $data) use (&$rows) {
            $rows[] = $data;
        });

        @unlink($tmpFile);

        $this->assertCount(2, $rows);
        $this->assertEquals('João da Silva', $rows[0]['NM_CANDIDATO']);
        $this->assertEquals('PT', $rows[0]['SG_PARTIDO']);
        $this->assertEquals('50000.00', $rows[0]['VR_RECEITA']);
        $this->assertEquals('75000.00', $rows[1]['VR_RECEITA']);
    }

    public function test_cleanup_removes_temp_files(): void
    {
        $client = new TseApiClient;

        $tmpDir = sys_get_temp_dir().'/tse_test_'.uniqid();
        mkdir($tmpDir);
        $zipFile = $tmpDir.'/test.zip';
        file_put_contents($zipFile, 'dummy');

        $extractDir = $tmpDir.'/extracted_test';
        mkdir($extractDir);
        $csvFile = $extractDir.'/receitas.csv';
        file_put_contents($csvFile, 'dummy');

        $client->cleanup($zipFile, $csvFile);

        $this->assertFileDoesNotExist($zipFile);
        $this->assertFileDoesNotExist($csvFile);
        $this->assertDirectoryDoesNotExist($extractDir);

        @rmdir($tmpDir);
    }
}
