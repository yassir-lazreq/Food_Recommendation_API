<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Plate;
use App\Models\Recommendation;
use Illuminate\Http\JsonResponse;

class AdminStatsController extends Controller
{
    public function index(): JsonResponse
    {
        $top = Recommendation::query()
            ->where('status', 'ready')
            ->whereNotNull('score')
            ->selectRaw('plate_id, AVG(score) as avg_score')
            ->groupBy('plate_id')
            ->orderByDesc('avg_score')
            ->first();

        $bottom = Recommendation::query()
            ->where('status', 'ready')
            ->whereNotNull('score')
            ->selectRaw('plate_id, AVG(score) as avg_score')
            ->groupBy('plate_id')
            ->orderBy('avg_score')
            ->first();

        $categoryWithMostPlates = Category::query()
            ->withCount('plates')
            ->orderByDesc('plates_count')
            ->orderBy('name')
            ->first();

        return response()->json([
            'total_plates' => Plate::query()->count(),
            'total_categories' => Category::query()->count(),
            'total_ingredients' => Ingredient::query()->count(),
            'most_recommended_plate' => $top ? [
                'plate' => Plate::query()->find($top->plate_id),
                'average_score' => round((float) $top->avg_score, 2),
            ] : null,
            'least_recommended_plate' => $bottom ? [
                'plate' => Plate::query()->find($bottom->plate_id),
                'average_score' => round((float) $bottom->avg_score, 2),
            ] : null,
            'category_with_most_plates' => $categoryWithMostPlates ? [
                'category' => $categoryWithMostPlates,
                'plates_count' => $categoryWithMostPlates->plates_count,
            ] : null,
            'total_generated_recommendations' => Recommendation::query()->count(),
        ]);
    }
}
