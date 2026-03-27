<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plate;
use App\Services\AI\GeminiRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiRecommendationController extends Controller
{
    public function __construct(
        private readonly GeminiRecommendationService $geminiRecommendationService,
    ) {
    }

    public function recommend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['prohibited'],
            'limit' => ['prohibited'],
            'plate_ids' => ['sometimes', 'array'],
            'plate_ids.*' => ['integer', 'exists:plates,id'],
        ], [], [
            'plate_ids' => 'comma-separated plate IDs',
        ]);

        $limit = (int) config('services.gemini.recommendation_limit', 5);

        $plates = Plate::query()
            ->select(['id', 'name', 'description', 'price', 'image', 'is_available', 'category_id', 'ai_health_score', 'ai_conflict_tags', 'ai_warning_fr'])
            ->where('is_available', true)
            ->when(
                array_key_exists('plate_ids', $validated),
                fn ($query) => $query->whereIn('id', $validated['plate_ids'])
            )
            ->orderBy('name')
            ->get();

        $serviceResult = $this->geminiRecommendationService->recommend(
            plates: $plates->all(),
            dietaryTags: $request->user()->dietary_tags ?? [],
            limit: $limit,
        );

        $recommendedPlateIds = collect($serviceResult['recommendations'])
            ->pluck('plate_id')
            ->values()
            ->all();

        $platesById = Plate::query()
            ->with(['category', 'ingredients'])
            ->whereIn('id', $recommendedPlateIds)
            ->get()
            ->keyBy('id');

        $recommendations = collect($serviceResult['recommendations'])
            ->map(function (array $recommendation) use ($platesById): ?array {
                $plate = $platesById->get($recommendation['plate_id']);

                if (! $plate) {
                    return null;
                }

                return [
                    'plate' => $plate,
                    'score' => $recommendation['score'],
                    'warning_message' => $recommendation['warning_message'] ?? '',
                ];
            })
            ->filter(fn (?array $item) => $item !== null)
            ->values()
            ->all();

        return response()->json([
            'source' => $serviceResult['source'],
            'count' => count($recommendations),
            'recommendations' => $recommendations,
        ]);
    }
}
