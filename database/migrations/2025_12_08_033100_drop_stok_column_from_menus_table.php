<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration removes the 'stok' (stock) column from the menus table
     * as part of pivoting the application strategy to reserve Inventory Management
     * as a premium/paid feature.
     */
    public function up(): void
    {
        // Only drop if column exists (for existing databases)
        // Fresh installs won't have this column since it was removed from the create migration
        if (Schema::hasColumn('menus', 'stok')) {
            Schema::table('menus', function (Blueprint $table) {
                $table->dropColumn('stok');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->integer('stok')->default(0)->after('harga');
        });
    }
};
