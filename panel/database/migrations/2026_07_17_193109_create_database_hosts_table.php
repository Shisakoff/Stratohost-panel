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
        Schema::create('database_hosts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // MySQL server the panel provisions per-server databases on -
            // credentials need CREATE DATABASE / CREATE USER / GRANT
            // privileges, not just access to one database.
            $table->string('host');
            $table->unsignedInteger('port')->default(3306);
            $table->string('username');
            $table->text('password');
            $table->unsignedInteger('max_databases')->nullable();
            $table->timestamps();

            $table->unique(['host', 'port']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_hosts');
    }
};
