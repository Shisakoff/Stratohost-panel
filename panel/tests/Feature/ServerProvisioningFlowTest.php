<?php

namespace Tests\Feature;

use App\Models\Allocation;
use App\Models\Egg;
use App\Models\EggVariable;
use App\Models\Nest;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServerProvisioningFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_server_and_send_it_a_power_action(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);

        $node = new Node([
            'name' => 'node-1', 'fqdn' => 'node1.example.com', 'scheme' => 'https', 'daemon_port' => 8080,
            'memory' => 4096, 'disk' => 20000,
        ]);
        $node->daemon_token_id = 'tokid1234567890';
        $node->daemon_token = 'secret';
        $node->save();

        $allocation = Allocation::create(['node_id' => $node->id, 'ip' => '10.0.0.5', 'port' => 25565]);

        $nest = Nest::create(['name' => 'Minecraft']);
        $egg = Egg::create([
            'nest_id' => $nest->id,
            'name' => 'Vanilla',
            'docker_image' => 'itzg/minecraft-server',
            'startup' => 'java -Xmx{{SERVER_MEMORY}}M -jar server.jar',
        ]);
        $variable = EggVariable::create([
            'egg_id' => $egg->id,
            'name' => 'Version',
            'env_variable' => 'VERSION',
            'default_value' => 'latest',
            'rules' => 'required|string',
        ]);

        Http::fake([
            $node->baseUri().'/*' => Http::response(['status' => 'provisioning'], 202),
        ]);

        $response = $this->actingAs($admin)->postJson('/api/servers', [
            'name' => 'my-server',
            'node_id' => $node->id,
            'egg_id' => $egg->id,
            'allocation_id' => $allocation->id,
            'memory' => 1024,
            'disk' => 5000,
            'variables' => [
                ['egg_variable_id' => $variable->id, 'value' => '1.20.4'],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('status', 'installing');

        $server = Server::firstOrFail();
        $this->assertSame('1.20.4', $server->variables()->first()->value);
        $this->assertSame($allocation->id, $server->allocation_id);
        // The reverse side of the relationship must be kept in sync too,
        // or the allocation would look "free" to future server creation.
        $this->assertSame($server->id, $allocation->fresh()->server_id);

        Http::fake([
            $node->baseUri().'/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $powerResponse = $this->actingAs($admin)
            ->postJson("/api/servers/{$server->uuid}/power", ['action' => 'start']);
        $powerResponse->assertOk();

        Http::assertSent(fn ($request) => $request->url() === $node->baseUri()."/api/servers/{$server->uuid}/power"
            && $request['action'] === 'start'
            && $request->hasHeader('Authorization', 'Bearer '.$node->daemonAuthorizationToken())
        );
    }

    public function test_creating_a_server_on_an_already_used_allocation_is_rejected(): void
    {
        $admin = User::factory()->create(['root_admin' => true]);

        $node = new Node([
            'name' => 'node-1', 'fqdn' => 'node1.example.com', 'scheme' => 'https', 'daemon_port' => 8080,
            'memory' => 4096, 'disk' => 20000,
        ]);
        $node->daemon_token_id = 'tokid1234567890';
        $node->daemon_token = 'secret';
        $node->save();

        $allocation = Allocation::create(['node_id' => $node->id, 'ip' => '10.0.0.5', 'port' => 25565]);
        $nest = Nest::create(['name' => 'Minecraft']);
        $egg = Egg::create([
            'nest_id' => $nest->id, 'name' => 'Vanilla', 'docker_image' => 'itzg/minecraft-server', 'startup' => 'run',
        ]);

        Http::fake([$node->baseUri().'/*' => Http::response(['status' => 'provisioning'], 202)]);

        $this->actingAs($admin)->postJson('/api/servers', [
            'name' => 'first', 'node_id' => $node->id, 'egg_id' => $egg->id,
            'allocation_id' => $allocation->id, 'memory' => 512, 'disk' => 1000,
        ])->assertCreated();

        $this->actingAs($admin)->postJson('/api/servers', [
            'name' => 'second', 'node_id' => $node->id, 'egg_id' => $egg->id,
            'allocation_id' => $allocation->id, 'memory' => 512, 'disk' => 1000,
        ])->assertStatus(409);
    }
}
