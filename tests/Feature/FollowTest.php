<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Identity\Models\Follow;
use MeuCandidato\Party\Models\Party;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_button_requires_authentication(): void
    {
        $party = Party::create(['name' => 'Partido Teste', 'acronym' => 'PT']);
        $politician = Politician::create([
            'name' => 'Teste Politician',
            'party_id' => $party->id,
            'external_id' => '88888',
        ]);

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
        $response->assertSee('Seguir');
    }

    public function test_authenticated_user_sees_follow_button(): void
    {
        $user = User::factory()->create();
        $party = Party::create(['name' => 'Partido Teste', 'acronym' => 'PT']);
        $politician = Politician::create([
            'name' => 'Teste Politician',
            'party_id' => $party->id,
            'external_id' => '88887',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('politicos.show', $politician->id));

        $response->assertOk();
        $response->assertSee('Seguir');
    }

    public function test_follow_creates_record_in_database(): void
    {
        $user = User::factory()->create();
        $party = Party::create(['name' => 'Partido Teste', 'acronym' => 'PT']);
        $politician = Politician::create([
            'name' => 'Teste Politician',
            'party_id' => $party->id,
            'external_id' => '88886',
        ]);

        $this->actingAs($user);

        Follow::create([
            'user_id' => $user->id,
            'politician_id' => $politician->id,
        ]);

        $this->assertDatabaseHas('follows', [
            'user_id' => $user->id,
            'politician_id' => $politician->id,
        ]);
    }

    public function test_unfollow_removes_record_from_database(): void
    {
        $user = User::factory()->create();
        $party = Party::create(['name' => 'Partido Teste', 'acronym' => 'PT']);
        $politician = Politician::create([
            'name' => 'Teste Politician',
            'party_id' => $party->id,
            'external_id' => '88885',
        ]);

        $this->actingAs($user);

        $follow = Follow::create([
            'user_id' => $user->id,
            'politician_id' => $politician->id,
        ]);

        $follow->delete();

        $this->assertDatabaseMissing('follows', [
            'user_id' => $user->id,
            'politician_id' => $politician->id,
        ]);
    }
}
