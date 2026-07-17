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
        Schema::create('server_databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('database_host_id')->constrained()->restrictOnDelete();
            // Actual MySQL database/user names on the host - not the
            // server's own name, since those must be globally unique on
            // the host and MySQL identifiers have their own charset limits.
            $table->string('database');
            $table->string('username');
            $table->text('password');
            // Host pattern MySQL grants the user access from, e.g. '%' for
            // any host or a specific node IP.
            $table->string('remote')->default('%');
            $table->timestamps();

            $table->unique(['database_host_id', 'database']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_databases');
    }
};
