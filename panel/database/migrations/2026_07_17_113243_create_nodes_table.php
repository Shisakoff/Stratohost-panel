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
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('maintenance_mode')->default(false);

            // Connection details for the agent running on this node.
            $table->string('fqdn');
            $table->string('scheme')->default('https');
            $table->unsignedInteger('daemon_port')->default(8080);

            // Panel -> Agent auth: token_id is public (used to look up the node),
            // daemon_token is the secret, stored hashed. The plaintext secret is
            // shown to the admin exactly once at node creation time and must be
            // copied into the agent's config (or passed to the install script).
            $table->string('daemon_token_id', 16)->unique();
            $table->string('daemon_token');

            // Resource allocation for scheduling servers onto this node (MB).
            $table->unsignedInteger('memory');
            $table->unsignedInteger('memory_overallocate')->default(0);
            $table->unsignedInteger('disk');
            $table->unsignedInteger('disk_overallocate')->default(0);
            $table->unsignedInteger('upload_size')->default(100);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
