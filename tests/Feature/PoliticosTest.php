<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MeuCandidato\Candidate\Models\BadgeDefinition;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Geography\Models\Address;
use MeuCandidato\Ingestion\Commands\ImportarDadosCommand;
use MeuCandidato\Ingestion\Services\CamaraApiClient;
use MeuCandidato\Ingestion\Services\SenadoApiClient;
use MeuCandidato\Ingestion\Services\TseApiClient;
use MeuCandidato\Legislative\Models\Bill;
use MeuCandidato\Legislative\Models\Vote;
use MeuCandidato\Legislative\Models\VotingSession;
use MeuCandidato\Mandate\Models\Mandate;
use MeuCandidato\Party\Models\Party;
use MeuCandidato\Transparency\Models\Expense;
use Tests\TestCase;

class PoliticosTest extends TestCase
{
    use RefreshDatabase;

    private function createPolitician(string $externalId = '99999'): Politician
    {
        $party = Party::create(['name' => 'Partido Teste', 'acronym' => 'PT']);

        return Politician::create([
            'name' => 'João da Silva',
            'party_id' => $party->id,
            'position' => 'Deputado Federal',
            'external_id' => $externalId,
        ]);
    }

    public function test_guests_can_view_politicos_listing(): void
    {
        $response = $this->get(route('politicos'));

        $response->assertOk();
    }

    public function test_politicos_listing_displays_politicians(): void
    {
        $politician = $this->createPolitician();

        $response = $this->get(route('politicos'));

        $response->assertOk();
        $response->assertSee('João da Silva');
    }

    public function test_politicos_listing_can_search(): void
    {
        $party = Party::create(['name' => 'Partido Teste', 'acronym' => 'PT']);
        Politician::create([
            'name' => 'Maria Santos',
            'party_id' => $party->id,
            'position' => 'Senador',
            'external_id' => '99998',
        ]);

        $response = $this->get(route('politicos', ['search' => 'Maria']));

        $response->assertOk();
    }

    public function test_guests_can_view_politician_profile(): void
    {
        $politician = $this->createPolitician('99997');

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
    }

    public function test_nonexistent_politician_returns_404_component(): void
    {
        $response = $this->get(route('politicos.show', '00000000-0000-0000-0000-000000000000'));

        $response->assertOk();
    }

    public function test_profile_shows_mandates_sorted_by_date(): void
    {
        $politician = $this->createPolitician('99996');

        Mandate::create([
            'politician_id' => $politician->id,
            'position' => 'Deputado Federal',
            'started_at' => '2021-02-01',
            'ended_at' => '2025-01-31',
        ]);
        Mandate::create([
            'politician_id' => $politician->id,
            'position' => 'Deputado Federal',
            'started_at' => '2025-02-01',
            'ended_at' => null,
        ]);

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
        $response->assertSee('Em exercício');
    }

    public function test_profile_shows_expense_breakdown_from_all_data(): void
    {
        $politician = $this->createPolitician('99995');

        for ($i = 0; $i < 30; $i++) {
            Expense::create([
                'politician_id' => $politician->id,
                'year' => 2024,
                'type' => 'Aluguel',
                'description' => 'Aluguel escritório '.$i,
                'value' => 1000,
                'document_date' => '2024-01-15',
            ]);
        }

        for ($i = 0; $i < 5; $i++) {
            Expense::create([
                'politician_id' => $politician->id,
                'year' => 2024,
                'type' => 'Combustível',
                'description' => 'Abastecimento '.$i,
                'value' => 200,
                'document_date' => '2024-02-10',
            ]);
        }

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
        $response->assertSee('R$ 31.000,00');
        $response->assertSee('30 documentos');
        $response->assertSee('5 documentos');
    }

    public function test_profile_shows_bills_and_votes(): void
    {
        $politician = $this->createPolitician('99994');

        $bill = Bill::create([
            'external_id' => 'BILL-001',
            'title' => 'Projeto de Transparência',
            'description' => 'Dados abertos',
            'author_id' => $politician->id,
            'status' => 'Em tramitação',
            'year' => 2025,
        ]);

        $session = VotingSession::create([
            'external_id' => 'SESS-001',
            'bill_id' => $bill->id,
            'date' => '2025-06-01',
        ]);

        Vote::create([
            'voting_session_id' => $session->id,
            'politician_id' => $politician->id,
            'vote' => 'Sim',
        ]);

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
        $response->assertSee('Projeto de Transparência');
        $response->assertSee('Sim');
        $response->assertSee('01/06/2025');
    }

    public function test_profile_shows_badges(): void
    {
        $politician = $this->createPolitician('99993');

        $badge = BadgeDefinition::create([
            'badge_type' => 'transparency',
            'label' => 'Político Transparente',
            'description' => 'Dados completos',
            'color' => '#10b981',
            'rules' => ['min_badges' => 1],
        ]);

        $politician->badges()->attach($badge->id);

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
        $response->assertSee('Político Transparente');
    }

    public function test_profile_shows_party_and_state(): void
    {
        $politician = $this->createPolitician('99992');

        Address::create([
            'addressable_type' => Politician::class,
            'addressable_id' => $politician->id,
            'uf' => 'SP',
        ]);

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
        $response->assertSee('PT');
        $response->assertSee('SP');
    }

    public function test_profile_renders_modal_components(): void
    {
        $politician = $this->createPolitician('99993');

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
        $response->assertSee('ver-votacoes-modal');
        $response->assertSee('ver-proposicoes-modal');
        $response->assertSee('ver-despesas-modal');
    }

    public function test_bills_show_ver_todas_button_when_more_than_3(): void
    {
        $politician = $this->createPolitician('99994');

        for ($i = 0; $i < 4; $i++) {
            Bill::create([
                'external_id' => (string) (30000 + $i),
                'title' => "PL {$i}/2024",
                'status' => 'Em tramitação',
                'year' => 2024,
                'author_id' => $politician->id,
            ]);
        }

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
        $response->assertSee('Ver todas');
        $response->assertSee('openProposicoesModal');
    }

    public function test_expenses_show_ver_todas_button_when_more_than_3(): void
    {
        $politician = $this->createPolitician('99995');

        for ($i = 0; $i < 4; $i++) {
            Expense::create([
                'politician_id' => $politician->id,
                'year' => 2024,
                'type' => 'Tipo '.$i,
                'description' => 'Fornecedor '.$i,
                'value' => 100.00 * ($i + 1),
                'document_date' => "2024-01-0{$i}",
            ]);
        }

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
        $response->assertSee('openDespesasModal');
    }

    public function test_votes_link_to_camara_when_session_has_external_id(): void
    {
        $politician = $this->createPolitician('99996');

        $session = VotingSession::create([
            'external_id' => '12345',
            'date' => '2024-01-15',
            'description' => 'Votação teste',
        ]);

        Vote::create([
            'voting_session_id' => $session->id,
            'politician_id' => $politician->id,
            'vote' => 'Sim',
        ]);

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
        $response->assertSee('camara.leg.br/plenario/votacao/12345');
    }

    public function test_bill_linking_via_extrair_bill_uri(): void
    {
        $uri = 'https://www.camara.leg.br/proposicoesWeb/fichadetramitacao?idProposicao=65432';

        $command = new ImportarDadosCommand(
            new CamaraApiClient,
            new SenadoApiClient,
            new TseApiClient,
        );

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('extrairBill');
        $method->setAccessible(true);

        $bill = $method->invoke($command, $uri);

        $this->assertNotNull($bill);
        $this->assertEquals('65432', $bill->external_id);
        $this->assertEquals('Proposição #65432', $bill->title);
    }

    public function test_extrair_bill_returns_null_for_invalid_uri(): void
    {
        $command = new ImportarDadosCommand(
            new CamaraApiClient,
            new SenadoApiClient,
            new TseApiClient,
        );

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('extrairBill');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($command, null));
        $this->assertNull($method->invoke($command, 'https://example.com'));
    }
}
