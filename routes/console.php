<?php

use App\Models\Plate;
use App\Services\AI\GeminiRecommendationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('plates:analyze-ai {--plate_id=* : Optional plate IDs to analyze}', function (GeminiRecommendationService $service): void {
    $plateIds = array_values(array_filter((array) $this->option('plate_id')));

    $query = Plate::query()
        ->with(['ingredients'])
        ->where('is_available', true)
        ->orderBy('id');

    if ($plateIds !== []) {
        $query->whereIn('id', array_map('intval', $plateIds));
    }

    $plates = $query->get()->all();

    if ($plates === []) {
        $this->warn('No matching available plates found.');

        return;
    }

    $count = $service->analyzeAndStorePlates($plates);

    $this->info("AI analysis completed for {$count} plate(s).");
})->purpose('Analyze available plates with AI and store metadata for deterministic recommendations');
