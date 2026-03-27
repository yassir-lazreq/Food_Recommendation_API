<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plate;
use App\Models\Recommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $plates = Plate::query()
            ->with(['category', 'ingredients'])
            ->when(
                ! $request->has('is_available'),
                fn ($query) => $query->where('is_available', true)
            )
            ->when(
                $request->has('is_available'),
                fn ($query) => $query->where('is_available', filter_var($request->query('is_available'), FILTER_VALIDATE_BOOL))
            )
            ->orderBy('name')
            ->get();

        $latestByPlate = Recommendation::query()
            ->where('user_id', $user->id)
            ->whereIn('plate_id', $plates->pluck('id')->all())
            ->latest()
            ->get()
            ->unique('plate_id')
            ->keyBy('plate_id');

        $response = $plates->map(function (Plate $plate) use ($latestByPlate): array {
            $recommendation = $latestByPlate->get($plate->id);

            return [
                ...$plate->toArray(),
                'recommendation' => [
                    'score' => $recommendation?->score,
                    'label' => $recommendation?->label,
                    'warning_message' => $recommendation?->warning_message ?? '',
                    'status' => $recommendation?->status ?? 'processing',
                ],
            ];
        });

        return response()->json($response);
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

        $recommendation = Recommendation::query()
            ->where('user_id', $request->user()->id)
            ->where('plate_id', $plate->id)
            ->latest()
            ->first();

        return response()->json([
            'plate' => $plate,
            'recommendation' => [
                'score' => $recommendation?->score,
                'label' => $recommendation?->label,
                'warning_message' => $recommendation?->warning_message ?? '',
                'status' => $recommendation?->status ?? 'processing',
            ],
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

}
