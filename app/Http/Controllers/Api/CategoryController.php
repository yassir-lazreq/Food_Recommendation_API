<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Plate;
use App\Models\Recommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->when(
                $request->has('active'),
                fn ($query) => $query->where('is_active', filter_var($request->query('active'), FILTER_VALIDATE_BOOL))
            )
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($category);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy(Category $category): JsonResponse
    {
        $activePlatesCount = $category->plates()->where('is_available', true)->count();

        if ($activePlatesCount > 0) {
            return response()->json([
                'message' => 'Category cannot be deleted while it has active plates.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }

    public function plates(Request $request, Category $category): JsonResponse
    {
        $user = $request->user();

        $plates = $category->plates()
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
}
