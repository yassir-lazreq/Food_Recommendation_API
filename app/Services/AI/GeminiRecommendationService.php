<?php

namespace App\Services\AI;

use App\Models\Plate;
use Gemini;
use Illuminate\Support\Collection;
use Throwable;

class GeminiRecommendationService
{
    private const CONFLICT_MAP = [
        'vegan' => 'contains_meat',
        'no_sugar' => 'contains_sugar',
        'no_cholesterol' => 'contains_cholesterol',
        'gluten_free' => 'contains_gluten',
        'no_lactose' => 'contains_lactose',
    ];

    /**
     * @param array<int, Plate> $plates
     * @param array<int, string> $dietaryTags
     * @return array{source:string,recommendations:array<int,array{plate_id:int,score:int,reason:string}>}
     */
    public function recommend(array $plates, array $dietaryTags, ?string $prompt, int $limit): array
    {
        if ($plates === []) {
            return [
                'source' => 'none',
                'recommendations' => [],
            ];
        }

        try {
            $apiKey = (string) config('services.gemini.api_key');

            if ($apiKey === '') {
                throw new \RuntimeException('GEMINI_API_KEY is not configured.');
            }

            $client = Gemini::client($apiKey);
            $result = $client
                ->generativeModel(model: 'gemini-2.0-flash')
                ->generateContent($this->buildPrompt($plates, $dietaryTags, $prompt, $limit));

            $recommendations = $this->parseModelResponse((string) $result->text(), $plates, $limit);

            if ($recommendations !== []) {
                return [
                    'source' => 'ai',
                    'recommendations' => $recommendations,
                ];
            }
        } catch (Throwable) {
            // Fall back to deterministic scoring if the provider is unavailable.
        }

        return [
            'source' => 'fallback',
            'recommendations' => $this->heuristicRecommendations($plates, $dietaryTags, $limit),
        ];
    }

    /**
     * @param array<int, Plate> $plates
     * @param array<int, string> $dietaryTags
     */
    private function buildPrompt(array $plates, array $dietaryTags, ?string $prompt, int $limit): string
    {
        $candidatePlates = array_map(function (Plate $plate): array {
            $ingredientTags = $plate->ingredients
                ->flatMap(fn ($ingredient) => $ingredient->tags ?? [])
                ->filter(fn ($tag) => is_string($tag))
                ->values()
                ->all();

            return [
                'id' => $plate->id,
                'name' => $plate->name,
                'description' => $plate->description,
                'price' => (float) $plate->price,
                'category' => $plate->category?->name,
                'ingredient_tags' => $ingredientTags,
            ];
        }, $plates);

        $payload = [
            'dietary_tags' => array_values(array_unique($dietaryTags)),
            'user_prompt' => $prompt,
            'limit' => $limit,
            'plates' => $candidatePlates,
        ];

        return "You are a food recommendation engine.\n"
            . "Return ONLY valid JSON with this shape:"
            . '{"recommendations":[{"plate_id":1,"score":0,"reason":"..."}]}'
            . "\nRules:"
            . "\n- score must be an integer from 0 to 100"
            . "\n- reason must be concise"
            . "\n- only use plate_id values from the input"
            . "\n- return at most the requested limit"
            . "\nInput:\n"
            . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<int, Plate> $plates
     * @return array<int, array{plate_id:int,score:int,reason:string}>
     */
    private function parseModelResponse(string $rawText, array $plates, int $limit): array
    {
        $decoded = $this->decodeJsonObject($rawText);

        if (! is_array($decoded) || ! isset($decoded['recommendations']) || ! is_array($decoded['recommendations'])) {
            return [];
        }

        $allowedPlateIds = array_values(array_map(fn (Plate $plate) => $plate->id, $plates));

        return collect($decoded['recommendations'])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): ?array {
                if (! isset($row['plate_id'], $row['score'])) {
                    return null;
                }

                return [
                    'plate_id' => (int) $row['plate_id'],
                    'score' => max(0, min(100, (int) $row['score'])),
                    'reason' => isset($row['reason']) ? trim((string) $row['reason']) : 'Recommended by AI.',
                ];
            })
            ->filter(fn (?array $row) => $row !== null)
            ->filter(fn (array $row) => in_array($row['plate_id'], $allowedPlateIds, true))
            ->unique('plate_id')
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonObject(string $rawText): ?array
    {
        $rawText = trim($rawText);

        $decoded = json_decode($rawText, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $rawText, $matches) !== 1) {
            return null;
        }

        $decoded = json_decode($matches[0], true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<int, Plate> $plates
     * @param array<int, string> $dietaryTags
     * @return array<int, array{plate_id:int,score:int,reason:string}>
     */
    private function heuristicRecommendations(array $plates, array $dietaryTags, int $limit): array
    {
        return collect($plates)
            ->map(function (Plate $plate) use ($dietaryTags): array {
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
                    'plate_id' => $plate->id,
                    'score' => $score,
                    'reason' => count($conflicts) === 0
                        ? 'Matches your dietary profile.'
                        : 'Some dietary conflicts were detected.',
                ];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }
}
