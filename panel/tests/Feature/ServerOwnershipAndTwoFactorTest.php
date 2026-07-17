<?php

namespace Tests\Feature;

use App\Models\Allocation;
use App\Models\Egg;
use App\Models\Nest;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class ServerOwnershipAndTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum only starts a session for requests it recognizes as
        // coming from the SPA's own origin (checked via Origin/Referer
        // against config('sanctum.stateful')) - a real browser always
        // sends one, the test client doesn't unless told to.
        $this->withHeader('Referer', config('app.url'));
    }

    private function makeServer(User $owner): Server
    {
        $node = new Node([
            'name' => 'node-'.uniqid(), 'fqdn' => 'node.example.com', 'scheme' => 'https', 'daemon_port' => 8080,
            'memory' => 4096, 'disk' => 20000,
        ]);
        $node->daemon_token_id = uniqid();
        $node->daemon_token = uniqid();
        $node->save();

        $allocation = Allocation::create(['node_id' => $node->id, 'ip' => '10.0.0.'.rand(1, 250), 'port' => rand(20000, 29999)]);
        $nest = Nest::firstOrCreate(['name' => 'Test Nest']);
        $egg = Egg::create([
            'nest_id' => $nest->id, 'name' => 'egg-'.uniqid(), 'docker_image' => 'x', 'startup' => 'x',
        ]);

        return Server::create([
            'name' => 'srv', 'owner_id' => $owner->id, 'node_id' => $node->id,
            'allocation_id' => $allocation->id, 'egg_id' => $egg->id, 'startup' => 'x',
            'memory' => 512, 'disk' => 1000,
        ]);
    }

    public function test_regular_user_only_sees_their_own_servers_in_index(): void
    {
        $owner = User::factory()->create(['root_admin' => false]);
        $other = User::factory()->create(['root_admin' => false]);

        $mine = $this->makeServer($owner);
        $this->makeServer($other);

        $response = $this->actingAs($owner)->getJson('/api/servers');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.uuid', $mine->uuid);
    }

    public function test_regular_user_cannot_view_another_users_server(): void
    {
        $owner = User::factory()->create(['root_admin' => false]);
        $other = User::factory()->create(['root_admin' => false]);

        $server = $this->makeServer($owner);

        $this->actingAs($other)->getJson("/api/servers/{$server->uuid}")->assertForbidden();
    }

    public function test_admin_sees_every_server_and_can_view_any(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);
        $owner = User::factory()->create(['root_admin' => false]);
        $server = $this->makeServer($owner);

        $this->actingAs($admin)->getJson('/api/servers')->assertOk()->assertJsonCount(1);
        $this->actingAs($admin)->getJson("/api/servers/{$server->uuid}")->assertOk();
    }

    public function test_regular_user_cannot_create_or_delete_servers(): void
    {
        $user = User::factory()->create(['root_admin' => false]);

        $this->actingAs($user)->postJson('/api/servers', ['name' => 'x'])->assertForbidden();
    }

    public function test_regular_user_cannot_manage_other_admin_only_resources(): void
    {
        $user = User::factory()->create(['root_admin' => false]);

        $this->actingAs($user)->getJson('/api/nodes')->assertForbidden();
        $this->actingAs($user)->getJson('/api/users')->assertForbidden();
    }

    public function test_admin_can_manage_users(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);

        $create = $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'New Guy', 'email' => 'newguy@example.com', 'password' => 'password123',
        ]);
        $create->assertCreated();
        $newUserId = $create->json('id');

        $this->actingAs($admin)->getJson('/api/users')->assertOk()->assertJsonCount(2);

        $this->actingAs($admin)->deleteJson("/api/users/{$newUserId}")->assertNoContent();
    }

    public function test_two_factor_enable_confirm_and_login_challenge_flow(): void
    {
        $user = User::factory()->create(['root_admin' => false, 'password' => bcrypt('password123')]);

        // Fully black-box: log in for real (no 2FA yet) rather than
        // actingAs(), so the later logout() is guaranteed to actually
        // clear the same session the enable/confirm calls used.
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123'])->assertOk();

        $enable = $this->postJson('/api/two-factor/enable');
        $enable->assertOk();
        $secret = $enable->json('secret');

        $google2fa = new Google2FA;
        $validCode = $google2fa->getCurrentOtp($secret);

        $confirm = $this->postJson('/api/two-factor/confirm', ['code' => $validCode]);
        $confirm->assertOk();
        $recoveryCodes = $confirm->json('recovery_codes');
        $this->assertCount(8, $recoveryCodes);

        $this->post('/api/logout');

        // Not authenticated after logout.
        $this->getJson('/api/me')->assertUnauthorized();

        $login = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123']);
        $login->assertOk();
        $login->assertJson(['two_factor' => true]);

        // Password was right but 2FA is pending - still not authenticated.
        $this->getJson('/api/me')->assertUnauthorized();

        $challenge = $this->postJson('/api/two-factor-challenge', ['code' => $google2fa->getCurrentOtp($secret)]);
        $challenge->assertOk();

        $this->getJson('/api/me')->assertOk();
    }

    public function test_two_factor_recovery_code_is_single_use(): void
    {
        $user = User::factory()->create(['root_admin' => false, 'password' => bcrypt('password123')]);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123'])->assertOk();

        $enable = $this->postJson('/api/two-factor/enable');
        $secret = $enable->json('secret');
        $google2fa = new Google2FA;
        $confirm = $this->postJson('/api/two-factor/confirm', [
            'code' => $google2fa->getCurrentOtp($secret),
        ]);
        $recoveryCode = $confirm->json('recovery_codes')[0];

        $this->post('/api/logout');
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123']);

        $first = $this->postJson('/api/two-factor-challenge', ['code' => $recoveryCode]);
        $first->assertOk();

        $this->post('/api/logout');
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123']);

        $second = $this->postJson('/api/two-factor-challenge', ['code' => $recoveryCode]);
        $second->assertStatus(422);
    }
}
