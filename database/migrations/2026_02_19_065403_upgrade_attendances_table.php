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
        Schema::table('attendances', function (Blueprint $table) {

            // === KEHADIRAN ===
            $table->boolean('is_present')
                ->default(false)
                ->after('user_id');

            $table->timestamp('validated_at')
                ->nullable()
                ->after('is_present');

            $table->enum('presence_method', ['qr', 'admin', 'manual'])
                ->nullable()
                ->after('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'is_present',
                'validated_at',
                'presence_method',
            ]);
        });
    }
};
