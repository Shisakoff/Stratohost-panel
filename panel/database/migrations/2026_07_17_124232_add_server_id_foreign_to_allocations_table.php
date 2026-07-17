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
        Schema::table('allocations', function (Blueprint $table) {
            // A server keeps its allocation reserved even if it's deleted
            // from under it in the wrong order; deleting the server frees
            // the allocation back up (nullOnDelete) rather than cascading.
            $table->foreign('server_id')->references('id')->on('servers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropForeign(['server_id']);
        });
    }
};
