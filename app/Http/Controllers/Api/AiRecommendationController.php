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
            'prompt' => ['nullable', 'string', 'max:500'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'plate_ids' => ['sometimes', 'array'],
            'plate_ids.*' => ['integer', 'exists:plates,id'],
        ]);

        $limit = $validated['limit'] ?? 5;

        $plates = Plate::query()
            ->with(['category', 'ingredients'])
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
            prompt: $validated['prompt'] ?? null,
            limit: $limit,
        );

        $platesById = $plates->keyBy('id');
        $recommendations = collect($serviceResult['recommendations'])
            ->map(function (array $recommendation) use ($platesById): ?array {
                $plate = $platesById->get($recommendation['plate_id']);

                if (! $plate) {
                    return null;
                }

                return [
                    'plate' => $plate,
                    'score' => $recommendation['score'],
                    'reason' => $recommendation['reason'],
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
