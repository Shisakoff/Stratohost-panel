<?php

namespace Database\Seeders;

use App\Models\Egg;
use App\Models\EggVariable;
use App\Models\Nest;
use Illuminate\Database\Seeder;

/**
 * Ships one working egg out of the box, so a fresh install can create and
 * boot a real Minecraft server without first hand-building an egg in the
 * UI. Idempotent - safe to run again on an existing install.
 */
class MinecraftEggSeeder extends Seeder
{
    public function run(): void
    {
        $nest = Nest::firstOrCreate(
            ['name' => 'Minecraft'],
            ['description' => 'Serveurs Minecraft: Java Edition.']
        );

        $egg = Egg::firstOrCreate(
            ['nest_id' => $nest->id, 'name' => 'Vanilla'],
            [
                'description' => 'Serveur Minecraft vanilla officiel (Mojang), sans mods.',
                'docker_image' => 'eclipse-temurin:21-jre-noble',
                'startup' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar server.jar nogui',
                'stop_command' => 'stop',
                'install_image' => 'alpine:3.19',
                'install_entrypoint' => 'sh',
                'install_script' => file_get_contents(
                    database_path('seeders/scripts/minecraft-vanilla-install.sh')
                ),
            ]
        );

        EggVariable::firstOrCreate(
            ['egg_id' => $egg->id, 'env_variable' => 'MINECRAFT_VERSION'],
            [
                'name' => 'Version de Minecraft',
                'description' => '"latest" pour la dernière version stable, ou un numéro de version précis (ex: 1.20.4).',
                'default_value' => 'latest',
                'rules' => 'nullable|string|max:20',
                'user_viewable' => true,
                'user_editable' => true,
            ]
        );
    }
}
