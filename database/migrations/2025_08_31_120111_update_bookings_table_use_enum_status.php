<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing records where is_active = false to cancelled status
        DB::table('bookings')
            ->where('is_active', false)
            ->update(['status' => 'cancelled']);

        // Remove the is_active column
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the is_active column
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('status');
        });

        // Update cancelled bookings to have is_active = false
        DB::table('bookings')
            ->where('status', 'cancelled')
            ->update(['is_active' => false]);
    }
};
