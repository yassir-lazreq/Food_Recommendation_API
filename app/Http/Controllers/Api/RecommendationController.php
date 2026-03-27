<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeRecommendationJob;
use App\Models\Plate;
use App\Models\Recommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function analyze(Request $request, int $plate_id): JsonResponse
    {
        $plate = Plate::query()->findOrFail($plate_id);

        $recommendation = Recommendation::create([
            'user_id' => $request->user()->id,
            'plate_id' => $plate->id,
            'status' => 'processing',
            'score' => null,
            'label' => null,
            'warning_message' => null,
            'conflicting_tags' => [],
        ]);

        AnalyzeRecommendationJob::dispatch($recommendation->id);

        return response()->json([
            'recommendation_id' => $recommendation->id,
            'plate_id' => $plate->id,
            'status' => 'processing',
        ], 202);
    }

    public function index(Request $request): JsonResponse
    {
        $history = Recommendation::query()
            ->where('user_id', $request->user()->id)
            ->with(['plate.category'])
            ->latest()
            ->get()
            ->map(fn (Recommendation $recommendation): array => $this->transform($recommendation))
            ->values()
            ->all();

        return response()->json([
            'count' => count($history),
            'recommendations' => $history,
        ]);
    }

    public function show(Request $request, int $plate_id): JsonResponse
    {
        $plate = Plate::query()->findOrFail($plate_id);

        $recommendation = Recommendation::query()
            ->where('user_id', $request->user()->id)
            ->where('plate_id', $plate->id)
            ->with(['plate.category'])
            ->latest()
            ->first();

        if (! $recommendation) {
            return response()->json([
                'message' => 'No recommendation found for this plate.',
            ], 404);
        }

        return response()->json($this->transform($recommendation));
    }

    private function transform(Recommendation $recommendation): array
    {
        return [
            'id' => $recommendation->id,
            'plate_id' => $recommendation->plate_id,
            'score' => $recommendation->score,
            'label' => $recommendation->label,
            'warning_message' => $recommendation->warning_message ?? '',
            'conflicting_tags' => $recommendation->conflicting_tags ?? [],
            'status' => $recommendation->status,
            'created_at' => $recommendation->created_at,
            'updated_at' => $recommendation->updated_at,
            'plate' => $recommendation->plate,
        ];
    }
}
