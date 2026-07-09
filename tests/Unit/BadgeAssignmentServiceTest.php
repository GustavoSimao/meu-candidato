<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MeuCandidato\Candidate\Models\BadgeDefinition;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Candidate\Services\BadgeAssignmentService;
use MeuCandidato\Mandate\Models\Mandate;
use MeuCandidato\Party\Models\Party;
use Tests\TestCase;

class BadgeAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private BadgeAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BadgeAssignmentService;
    }

    public function test_newcomer_badge_assigned_for_single_mandate(): void
    {
        $party = Party::create(['name' => 'Test', 'acronym' => 'T']);
        $politician = Politician::create([
            'name' => 'Novato',
            'party_id' => $party->id,
            'external_id' => '77777',
        ]);

        $badge = BadgeDefinition::create([
            'badge_type' => 'newcomer',
            'label' => 'Estreante',
            'color' => '#059669',
            'rules' => ['max_mandates' => 1],
            'is_active' => true,
        ]);

        Mandate::create([
            'politician_id' => $politician->id,
            'position' => 'Deputado Federal',
            'started_at' => now()->subYear(),
        ]);

        $assigned = $this->service->evaluatePolitician($politician);

        $this->assertContains($badge->id, $assigned);
        $this->assertDatabaseHas('politician_badges', [
            'politician_id' => $politician->id,
            'badge_definition_id' => $badge->id,
        ]);
    }

    public function test_veteran_badge_not_assigned_for_single_mandate(): void
    {
        $party = Party::create(['name' => 'Test', 'acronym' => 'T']);
        $politician = Politician::create([
            'name' => 'Novato',
            'party_id' => $party->id,
            'external_id' => '77776',
        ]);

        $badge = BadgeDefinition::create([
            'badge_type' => 'veteran',
            'label' => 'Veterano',
            'color' => '#7c3aed',
            'rules' => ['min_mandates' => 4],
            'is_active' => true,
        ]);

        Mandate::create([
            'politician_id' => $politician->id,
            'position' => 'Deputado Federal',
            'started_at' => now()->subYear(),
        ]);

        $assigned = $this->service->evaluatePolitician($politician);

        $this->assertNotContains($badge->id, $assigned);
    }

    public function test_deputy_badge_assigned_for_deputies(): void
    {
        $party = Party::create(['name' => 'Test', 'acronym' => 'T']);
        $politician = Politician::create([
            'name' => 'Deputado',
            'party_id' => $party->id,
            'position' => 'Deputado Federal',
            'external_id' => '77775',
        ]);

        $badge = BadgeDefinition::create([
            'badge_type' => 'deputy',
            'label' => 'Deputado Federal',
            'color' => '#ea580c',
            'rules' => ['position' => 'Deputado Federal'],
            'is_active' => true,
        ]);

        $assigned = $this->service->evaluatePolitician($politician);

        $this->assertContains($badge->id, $assigned);
    }

    public function test_inactive_badges_are_not_assigned(): void
    {
        $party = Party::create(['name' => 'Test', 'acronym' => 'T']);
        $politician = Politician::create([
            'name' => 'Deputado',
            'party_id' => $party->id,
            'position' => 'Deputado Federal',
            'external_id' => '77774',
        ]);

        BadgeDefinition::create([
            'badge_type' => 'deputy',
            'label' => 'Deputado Federal',
            'color' => '#ea580c',
            'rules' => ['position' => 'Deputado Federal'],
            'is_active' => false,
        ]);

        $assigned = $this->service->evaluatePolitician($politician);

        $this->assertEmpty($assigned);
    }

    public function test_evaluate_all_processes_all_politicians(): void
    {
        $party = Party::create(['name' => 'Test', 'acronym' => 'T']);

        $p1 = Politician::create(['name' => 'A', 'party_id' => $party->id, 'position' => 'Deputado Federal', 'external_id' => '77770']);
        $p2 = Politician::create(['name' => 'B', 'party_id' => $party->id, 'position' => 'Senador', 'external_id' => '77771']);

        BadgeDefinition::create([
            'badge_type' => 'deputy',
            'label' => 'Deputado Federal',
            'color' => '#ea580c',
            'rules' => ['position' => 'Deputado Federal'],
            'is_active' => true,
        ]);

        $this->service->evaluateAll();

        $this->assertDatabaseHas('politician_badges', [
            'politician_id' => $p1->id,
        ]);

        $this->assertDatabaseMissing('politician_badges', [
            'politician_id' => $p2->id,
        ]);
    }
}
