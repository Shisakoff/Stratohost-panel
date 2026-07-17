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
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->string('ip');
            $table->string('ip_alias')->nullable();
            $table->unsignedInteger('port');
            // References servers.id once the servers table exists (Phase 1).
            // Left as a plain nullable column for now, FK added later.
            $table->unsignedBigInteger('server_id')->nullable();
            $table->timestamps();

            $table->unique(['node_id', 'ip', 'port']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
