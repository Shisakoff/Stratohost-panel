<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Runs on every install (see
     * installer/panel-install.sh) - keep this limited to data that's safe
     * to have in production, not throwaway test fixtures.
     */
    public function run(): void
    {
        $this->call(MinecraftEggSeeder::class);
    }
}
