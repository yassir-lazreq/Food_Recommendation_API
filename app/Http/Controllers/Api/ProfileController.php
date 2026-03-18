<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private const DIETARY_TAGS = [
        'vegan',
        'no_sugar',
        'no_cholesterol',
        'gluten_free',
        'no_lactose',
    ];

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'dietary_tags' => $user->dietary_tags ?? [],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dietary_tags' => ['required', 'array'],
            'dietary_tags.*' => ['string', Rule::in(self::DIETARY_TAGS)],
        ]);

        $user = $request->user();
        $user->dietary_tags = array_values(array_unique($validated['dietary_tags']));
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'dietary_tags' => $user->dietary_tags,
        ]);
    }
}
