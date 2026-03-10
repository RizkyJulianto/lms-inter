<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_projects', function (Blueprint $table) {

            $table->id();

            // relasi user dan event (UUID)
            $table->char('user_id', 36);
            $table->char('event_id', 36);

            // data project
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('project_link');

            // status review admin
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // admin yang review
            $table->char('reviewed_by', 36)->nullable();

            $table->timestamps();

            // foreign key
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->cascadeOnDelete();

            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_projects');
    }
};
