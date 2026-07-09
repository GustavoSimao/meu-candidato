<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Identity\Models\Follow;
use MeuCandidato\Party\Models\Party;
use Tests\TestCase;

class FollowHttpTest extends TestCase
{
    use RefreshDatabase;

    private function createPolitician(string $externalId = '77777'): Politician
    {
        $party = Party::create(['name' => 'Partido Teste', 'acronym' => 'PT']);

        return Politician::create([
            'name' => 'Político Teste',
            'party_id' => $party->id,
            'external_id' => $externalId,
        ]);
    }

    public function test_follow_requires_authentication(): void
    {
        $politician = $this->createPolitician();

        $response = $this->postJson(route('politicos.follow', $politician->id));

        $response->assertRedirect();
    }

    public function test_unfollow_requires_authentication(): void
    {
        $politician = $this->createPolitician();

        $response = $this->deleteJson(route('politicos.unfollow', $politician->id));

        $response->assertRedirect();
    }

    public function test_authenticated_user_can_follow_politician(): void
    {
        $user = User::factory()->create();
        $politician = $this->createPolitician('77776');

        $response = $this->actingAs($user)->postJson(route('politicos.follow', $politician->id));

        $response->assertOk();
        $response->assertJson(['following' => true, 'message' => 'Agora você segue este político']);

        $this->assertDatabaseHas('follows', [
            'user_id' => $user->id,
            'politician_id' => $politician->id,
        ]);
    }

    public function test_following_same_politician_twice_returns_already_following(): void
    {
        $user = User::factory()->create();
        $politician = $this->createPolitician('77775');

        Follow::create([
            'user_id' => $user->id,
            'politician_id' => $politician->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('politicos.follow', $politician->id));

        $response->assertOk();
        $response->assertJson(['following' => true, 'message' => 'Já está seguindo']);

        $this->assertDatabaseCount('follows', 1);
    }

    public function test_authenticated_user_can_unfollow_politician(): void
    {
        $user = User::factory()->create();
        $politician = $this->createPolitician('77774');

        Follow::create([
            'user_id' => $user->id,
            'politician_id' => $politician->id,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('politicos.unfollow', $politician->id));

        $response->assertOk();
        $response->assertJson(['following' => false, 'message' => 'Deixou de seguir este político']);

        $this->assertDatabaseMissing('follows', [
            'user_id' => $user->id,
            'politician_id' => $politician->id,
        ]);
    }

    public function test_unfollow_non_followed_politician_still_returns_success(): void
    {
        $user = User::factory()->create();
        $politician = $this->createPolitician('77773');

        $response = $this->actingAs($user)->deleteJson(route('politicos.unfollow', $politician->id));

        $response->assertOk();
        $response->assertJson(['following' => false]);
    }

    public function test_follow_nonexistent_politician_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('politicos.follow', '00000000-0000-0000-0000-000000000000'));

        $response->assertNotFound();
        $response->assertJson(['error' => 'Político não encontrado']);
    }

    public function test_unfollow_nonexistent_politician_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson(route('politicos.unfollow', '00000000-0000-0000-0000-000000000000'));

        $response->assertNotFound();
        $response->assertJson(['error' => 'Político não encontrado']);
    }

    public function test_follow_then_unfollow_full_cycle(): void
    {
        $user = User::factory()->create();
        $politician = $this->createPolitician('77772');

        $this->actingAs($user)->postJson(route('politicos.follow', $politician->id))->assertOk();

        $this->assertDatabaseHas('follows', [
            'user_id' => $user->id,
            'politician_id' => $politician->id,
        ]);

        $this->actingAs($user)->deleteJson(route('politicos.unfollow', $politician->id))->assertOk();

        $this->assertDatabaseMissing('follows', [
            'user_id' => $user->id,
            'politician_id' => $politician->id,
        ]);
    }

    public function test_different_users_can_follow_same_politician(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $politician = $this->createPolitician('77771');

        $this->actingAs($user1)->postJson(route('politicos.follow', $politician->id))->assertOk();
        $this->actingAs($user2)->postJson(route('politicos.follow', $politician->id))->assertOk();

        $this->assertDatabaseCount('follows', 2);
    }

    public function test_follow_does_not_create_duplicate_records(): void
    {
        $user = User::factory()->create();
        $politician = $this->createPolitician('77770');

        $this->actingAs($user)->postJson(route('politicos.follow', $politician->id))->assertOk();
        $this->actingAs($user)->postJson(route('politicos.follow', $politician->id))->assertOk();

        $this->assertDatabaseCount('follows', 1);
    }
}
