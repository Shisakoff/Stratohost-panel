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
        Schema::create('egg_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('egg_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // The actual environment variable name injected into the
            // container and usable as {{ENV_VARIABLE}} in the egg's startup.
            $table->string('env_variable');
            $table->text('description')->nullable();
            $table->string('default_value')->nullable();
            // Laravel validation rule string, e.g. "required|string|max:20".
            $table->string('rules')->default('nullable|string');
            $table->boolean('user_viewable')->default(true);
            $table->boolean('user_editable')->default(true);
            $table->timestamps();

            $table->unique(['egg_id', 'env_variable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egg_variables');
    }
};
