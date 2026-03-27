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
        Schema::table('plates', function (Blueprint $table) {
            $table->unsignedTinyInteger('ai_health_score')->nullable()->after('is_available');
            $table->json('ai_conflict_tags')->nullable()->after('ai_health_score');
            $table->text('ai_warning_fr')->nullable()->after('ai_conflict_tags');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_warning_fr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plates', function (Blueprint $table) {
            $table->dropColumn([
                'ai_health_score',
                'ai_conflict_tags',
                'ai_warning_fr',
                'ai_analyzed_at',
            ]);
        });
    }
};
