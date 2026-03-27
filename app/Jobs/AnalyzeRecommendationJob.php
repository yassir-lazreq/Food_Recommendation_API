<?php

namespace App\Jobs;

use App\Models\Recommendation;
use App\Services\AI\GeminiRecommendationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyzeRecommendationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $recommendationId)
    {
    }

    public function handle(GeminiRecommendationService $service): void
    {
        $recommendation = Recommendation::query()
            ->with(['user', 'plate.ingredients'])
            ->find($this->recommendationId);

        if (! $recommendation || ! $recommendation->user || ! $recommendation->plate) {
            return;
        }

        try {
            if ($recommendation->plate->ai_analyzed_at === null) {
                $service->analyzeAndStorePlate($recommendation->plate);
                $recommendation->refresh();
                $recommendation->load(['user', 'plate']);
            }

            $analysis = $service->buildStoredRecommendation(
                $recommendation->plate,
                $recommendation->user->dietary_tags ?? [],
            );

            $recommendation->update([
                'score' => $analysis['score'],
                'label' => $analysis['label'],
                'warning_message' => $analysis['warning_message'],
                'conflicting_tags' => $analysis['conflicting_tags'],
                'status' => 'ready',
            ]);
        } catch (Throwable $e) {
            Log::error('Recommendation analysis job failed.', [
                'recommendation_id' => $this->recommendationId,
                'message' => $e->getMessage(),
            ]);

            $recommendation->update([
                'score' => 0,
                'label' => 'Not Recommended',
                'warning_message' => 'Recommendation analysis failed. Please retry.',
                'conflicting_tags' => [],
                'status' => 'ready',
            ]);
        }
    }
}
