<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('final_projects');
    }

    public function down(): void
    {
        // kosongkan saja supaya tidak recreate tabel
    }
};
