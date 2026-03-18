<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IngredientController extends Controller
{
    private const TAGS = [
        'contains_meat',
        'contains_sugar',
        'contains_cholesterol',
        'contains_gluten',
        'contains_lactose',
    ];

    public function index(): JsonResponse
    {
        $ingredients = Ingredient::query()->orderBy('name')->get();

        return response()->json($ingredients);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:ingredients,name'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', Rule::in(self::TAGS)],
        ]);

        $validated['tags'] = array_values(array_unique($validated['tags'] ?? []));

        $ingredient = Ingredient::create($validated);

        return response()->json($ingredient, 201);
    }

    public function update(Request $request, Ingredient $ingredient): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('ingredients', 'name')->ignore($ingredient->id)],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', Rule::in(self::TAGS)],
        ]);

        if (array_key_exists('tags', $validated)) {
            $validated['tags'] = array_values(array_unique($validated['tags']));
        }

        $ingredient->update($validated);

        return response()->json($ingredient);
    }

    public function destroy(Ingredient $ingredient): JsonResponse
    {
        $ingredient->delete();

        return response()->json([
            'message' => 'Ingredient deleted successfully.',
        ]);
    }
}
