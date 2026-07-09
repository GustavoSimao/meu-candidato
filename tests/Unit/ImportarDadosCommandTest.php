<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Ingestion\Services\SenadoApiClient;
use MeuCandidato\Ingestion\Services\TseApiClient;
use MeuCandidato\Party\Models\Party;
use Tests\TestCase;

class ImportarDadosCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_importar_dados_command_exists(): void
    {
        $this->artisan('importar-dados', ['subcomando' => 'invalido'])
            ->assertExitCode(1);
    }

    public function test_importar_partidos_subcommand_runs(): void
    {
        $this->artisan('importar-dados', ['subcomando' => 'partidos'])
            ->assertExitCode(0);
    }

    public function test_financiamento_campanha_subcommand_shows_error_on_download_failure(): void
    {
        $this->artisan('importar-dados', ['subcomando' => 'financiamento-campanha', '--ano-fim' => '2099'])
            ->assertExitCode(1);
    }

    public function test_financiamento_campanha_imports_data_from_matching_politicians(): void
    {
        $party = Party::create(['name' => 'Partido dos Trabalhadores', 'acronym' => 'PT']);
        $politician = Politician::create([
            'name' => 'Maria Santos',
            'party_id' => $party->id,
            'external_id' => '88880',
            'position' => 'Deputado Federal',
        ]);

        $tmpDir = storage_path('app/tse_temp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $csvDir = $tmpDir.'/extracted_tse_test';
        if (! is_dir($csvDir)) {
            mkdir($csvDir, 0755, true);
        }

        $csvPath = $csvDir.'/receitas_candidatos_2022.csv';
        file_put_contents($csvPath, "SQ_CANDIDATO;NR_CANDIDATO;NM_CANDIDATO;SG_PARTIDO;DS_CARGO;VR_RECEITA;VR_DESPESA\n12345;12345;Maria Santos;PT;DEPUTADO FEDERAL;150000.00;95000.00\n12346;12346;Outro Candidato;PSDB;SENADOR;200000.00;180000.00\n");

        $mockTse = \Mockery::mock(TseApiClient::class);
        $mockTse->shouldReceive('downloadCandidatosCsv')->andReturn($tmpDir.'/fake.zip');
        $mockTse->shouldReceive('extractAndFindReceitasCsv')->andReturn($csvPath);
        $mockTse->shouldReceive('streamReceitasCsv')->once()->andReturnUsing(function ($path, $callback) {
            $handle = fopen($path, 'r');
            fgetcsv($handle, 0, ';');
            $count = 0;
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $data = array_combine(['SQ_CANDIDATO', 'NR_CANDIDATO', 'NM_CANDIDATO', 'SG_PARTIDO', 'DS_CARGO', 'VR_RECEITA', 'VR_DESPESA'], $row);
                $callback($data);
                $count++;
            }
            fclose($handle);

            return $count;
        });
        $mockTse->shouldReceive('cleanup')->once();

        $this->app->instance(TseApiClient::class, $mockTse);

        $this->artisan('importar-dados', ['subcomando' => 'financiamento-campanha', '--ano-fim' => '2022'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('campaign_financings', [
            'politician_id' => $politician->id,
            'election_year' => 2022,
            'type' => 'receita',
            'value' => 150000.00,
        ]);

        $this->assertDatabaseHas('campaign_financings', [
            'politician_id' => $politician->id,
            'election_year' => 2022,
            'type' => 'despesa',
            'value' => 95000.00,
        ]);
    }

    public function test_senador_votos_subcommand_requires_senadores(): void
    {
        $this->artisan('importar-dados', ['subcomando' => 'senador-votos'])
            ->assertExitCode(1);
    }

    public function test_senador_autorias_subcommand_requires_senadores(): void
    {
        $this->artisan('importar-dados', ['subcomando' => 'senador-autorias'])
            ->assertExitCode(1);
    }

    public function test_senador_votos_imports_votes_from_api(): void
    {
        $party = Party::create(['name' => 'Partido Teste', 'acronym' => 'PT']);
        $senador = Politician::create([
            'name' => 'Senador Teste',
            'party_id' => $party->id,
            'external_id' => 'senado_1234',
            'position' => 'Senador Federal',
        ]);

        $mockSenado = \Mockery::mock(SenadoApiClient::class);
        $mockSenado->shouldReceive('getVotosSenador')->once()->with(1234)->andReturn([
            [
                'SiglaMateria' => 'PL',
                'NumeroMateria' => '1234',
                'AnoMateria' => '2023',
                'DescricaoVoto' => 'Sim',
                'DataSessao' => '2023-06-15',
            ],
            [
                'SiglaMateria' => 'PEC',
                'NumeroMateria' => '5678',
                'AnoMateria' => '2024',
                'DescricaoVoto' => 'Não',
                'DataSessao' => '2024-03-20',
            ],
        ]);

        $this->app->instance(SenadoApiClient::class, $mockSenado);

        $this->artisan('importar-dados', ['subcomando' => 'senador-votos'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('votes', [
            'politician_id' => $senador->id,
            'vote' => 'Sim',
        ]);

        $this->assertDatabaseHas('votes', [
            'politician_id' => $senador->id,
            'vote' => 'Não',
        ]);
    }

    public function test_senador_autorias_imports_bills_from_api(): void
    {
        $party = Party::create(['name' => 'Partido Teste', 'acronym' => 'PT']);
        $senador = Politician::create([
            'name' => 'Senador Autor',
            'party_id' => $party->id,
            'external_id' => 'senado_5678',
            'position' => 'Senador Federal',
        ]);

        $mockSenado = \Mockery::mock(SenadoApiClient::class);
        $mockSenado->shouldReceive('getAutoriasSenador')->once()->with(5678)->andReturn([
            [
                'SiglaMateria' => 'PL',
                'NumeroMateria' => '9999',
                'AnoMateria' => '2024',
                'DescricaoMateria' => 'Lei de transparência',
            ],
        ]);

        $this->app->instance(SenadoApiClient::class, $mockSenado);

        $this->artisan('importar-dados', ['subcomando' => 'senador-autorias'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('bills', [
            'author_id' => $senador->id,
            'external_id' => 'senado_PL_9999_2024',
        ]);
    }
}
