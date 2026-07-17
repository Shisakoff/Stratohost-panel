<?php

namespace App\Console\Commands\Node;

use App\Models\Node;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('stratohost:node:create
    {--name= : Display name for the node}
    {--fqdn= : Fully qualified domain name the agent will be reachable on}
    {--scheme=https : http or https}
    {--port=8080 : Port the agent listens on}
    {--memory=2048 : Total memory (MB) available for servers on this node}
    {--disk=10240 : Total disk (MB) available for servers on this node}
    {--json : Print a single JSON line instead of human-readable output, for scripts (e.g. panel-install.sh)}'
)]
#[Description('Create a node and print the one-time daemon token + agent install command')]
class CreateNodeCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $json = (bool) $this->option('json');

        $name = $this->option('name') ?: $this->ask('Node name');
        $fqdn = $this->option('fqdn') ?: $this->ask('Node FQDN (e.g. node1.example.com)');
        $scheme = $this->option('scheme');
        $port = (int) $this->option('port');
        $memory = (int) $this->option('memory');
        $disk = (int) $this->option('disk');

        $token = Node::generateDaemonToken();

        $node = new Node([
            'name' => $name,
            'fqdn' => $fqdn,
            'scheme' => $scheme,
            'daemon_port' => $port,
            'memory' => $memory,
            'disk' => $disk,
        ]);
        // Not mass-assignable on purpose: set directly so a stray fillable
        // array from elsewhere can never overwrite a node's secret token.
        $node->daemon_token_id = $token['id'];
        $node->daemon_token = $token['token'];
        $node->save();

        $installCommand = $node->installCommand($token['id'], $token['token']);

        if ($json) {
            $this->line(json_encode([
                'node_id' => $node->id,
                'node_uuid' => $node->uuid,
                'token_id' => $token['id'],
                'token' => $token['token'],
                'install_command' => $installCommand,
            ]));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Node #{$node->id} ({$node->name}) created.");
        $this->warn('Daemon token (shown once, copy it now):');
        $this->line("  token id: {$token['id']}");
        $this->line("  token:    {$token['token']}");
        $this->newLine();
        $this->info('Run this on the node (as root) to install and register the agent:');
        $this->line('  '.$installCommand);

        return self::SUCCESS;
    }
}
