<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plate_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('label', 64)->nullable();
            $table->text('warning_message')->nullable();
            $table->json('conflicting_tags')->nullable();
            $table->enum('status', ['processing', 'ready'])->default('processing');
            $table->timestamps();

            $table->index(['user_id', 'plate_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
