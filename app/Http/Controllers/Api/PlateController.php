<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlateController extends Controller
{
    private const CONFLICT_MAP = [
        'vegan' => 'contains_meat',
        'no_sugar' => 'contains_sugar',
        'no_cholesterol' => 'contains_cholesterol',
        'gluten_free' => 'contains_gluten',
        'no_lactose' => 'contains_lactose',
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $plates = Plate::query()
            ->with(['category', 'ingredients'])
            ->orderBy('name')
            ->get()
            ->map(function (Plate $plate) use ($user): array {
                return [
                    ...$plate->toArray(),
                    'recommendation' => $this->buildRecommendation($user->dietary_tags ?? [], $plate),
                ];
            });

        return response()->json($plates);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'is_available' => ['sometimes', 'boolean'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'ingredient_ids' => ['sometimes', 'array'],
            'ingredient_ids.*' => ['integer', 'exists:ingredients,id'],
        ]);

        $plate = Plate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'image' => $validated['image'] ?? null,
            'is_available' => $validated['is_available'] ?? true,
            'category_id' => $validated['category_id'],
        ]);

        $plate->ingredients()->sync($validated['ingredient_ids'] ?? []);

        return response()->json($plate->load(['category', 'ingredients']), 201);
    }

    public function show(Request $request, Plate $plate): JsonResponse
    {
        $plate->load(['category', 'ingredients']);

        return response()->json([
            'plate' => $plate,
            'recommendation' => $this->buildRecommendation($request->user()->dietary_tags ?? [], $plate),
        ]);
    }

    public function update(Request $request, Plate $plate): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'is_available' => ['sometimes', 'boolean'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'ingredient_ids' => ['sometimes', 'array'],
            'ingredient_ids.*' => ['integer', 'exists:ingredients,id'],
        ]);

        $plate->update([
            'name' => $validated['name'] ?? $plate->name,
            'description' => $validated['description'] ?? $plate->description,
            'price' => $validated['price'] ?? $plate->price,
            'image' => $validated['image'] ?? $plate->image,
            'is_available' => $validated['is_available'] ?? $plate->is_available,
            'category_id' => $validated['category_id'] ?? $plate->category_id,
        ]);

        if (array_key_exists('ingredient_ids', $validated)) {
            $plate->ingredients()->sync($validated['ingredient_ids']);
        }

        return response()->json($plate->load(['category', 'ingredients']));
    }

    public function destroy(Plate $plate): JsonResponse
    {
        $plate->delete();

        return response()->json([
            'message' => 'Plate deleted successfully.',
        ]);
    }

    private function buildRecommendation(array $dietaryTags, Plate $plate): array
    {
        $ingredientTags = $plate->ingredients
            ->flatMap(fn ($ingredient) => $ingredient->tags ?? [])
            ->filter(fn ($tag) => is_string($tag))
            ->values()
            ->all();

        $conflicts = collect($dietaryTags)
            ->map(fn (string $dietTag) => self::CONFLICT_MAP[$dietTag] ?? null)
            ->filter(fn ($requiredAbsentTag) => $requiredAbsentTag !== null && in_array($requiredAbsentTag, $ingredientTags, true))
            ->values()
            ->all();

        $score = max(0, 100 - (count($conflicts) * 20));

        return [
            'score' => $score,
            'is_compatible' => count($conflicts) === 0,
            'conflicting_tags' => $conflicts,
        ];
    }
}
