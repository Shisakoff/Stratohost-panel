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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->foreignId('allocation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('egg_id')->constrained()->restrictOnDelete();

            // Copied from the egg at creation time so an admin can tweak a
            // single server's startup without editing the shared egg.
            $table->text('startup');

            $table->unsignedInteger('memory');
            $table->unsignedInteger('swap')->default(0);
            $table->unsignedInteger('disk');
            // 100 = 1 full core, 0 = unlimited (Pterodactyl convention).
            $table->unsignedInteger('cpu')->default(100);

            // installing -> offline|install_failed, then offline <-> running
            // as the agent starts/stops the container. Cached here so the
            // server list doesn't have to call every node's agent live.
            $table->string('status')->default('installing');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
