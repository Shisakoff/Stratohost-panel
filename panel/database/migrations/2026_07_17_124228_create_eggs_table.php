<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eggs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nest_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();

            // Runtime image the server container runs, and the (templated,
            // {{VARIABLE}}-style) command used to start it.
            $table->string('docker_image');
            $table->text('startup');
            $table->string('stop_command')->default('stop');

            // Install script run once in a throwaway container, with the
            // server's volume mounted, to fetch/build the game server files.
            $table->string('install_image')->default('alpine:3.19');
            $table->string('install_entrypoint')->default('bash');
            $table->text('install_script')->nullable();

            $table->timestamps();

            $table->unique(['nest_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eggs');
    }
};
