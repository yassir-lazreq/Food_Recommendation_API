<?php

namespace App\Services\AI;

use App\Models\Plate;
use Gemini;
use Illuminate\Support\Carbon;
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

    private const ANALYZABLE_TAGS = [
        'contains_meat',
        'contains_sugar',
        'contains_cholesterol',
        'contains_gluten',
        'contains_lactose',
    ];

    /**
     * Analyze one plate with AI (or fallback) and persist analysis metadata.
     */
    public function analyzeAndStorePlate(Plate $plate): Plate
    {
        $analysis = $this->analyzePlate($plate);

        $plate->update([
            'ai_health_score' => $analysis['health_score'],
            'ai_conflict_tags' => $analysis['conflict_tags'],
            'ai_warning_fr' => $analysis['warning_message'],
            'ai_analyzed_at' => Carbon::now(),
        ]);

        return $plate->refresh();
    }

    /**
     * Analyze and persist many plates.
     *
     * @param array<int, Plate> $plates
     */
    public function analyzeAndStorePlates(array $plates): int
    {
        $count = 0;

        foreach ($plates as $plate) {
            $this->analyzeAndStorePlate($plate);
            $count++;
        }

        return $count;
    }

    /**
     * @param array<int, Plate> $plates
     * @param array<int, string> $dietaryTags
        * @return array{source:string,recommendations:array<int,array{plate_id:int,score:int,warning_message:string}>}
     */
    public function recommend(array $plates, array $dietaryTags, int $limit): array
    {
        if ($plates === []) {
            return [
                'source' => 'none',
                'recommendations' => [],
            ];
        }
        return [
            'source' => 'stored-analysis',
            'recommendations' => $this->sortedRecommendationsFromStoredAnalysis($plates, $dietaryTags, $limit),
        ];
    }

    /**
     * @param array<int, string> $dietaryTags
     * @return array{score:int,label:string,warning_message:string,conflicting_tags:array<int,string>}
     */
    public function buildStoredRecommendation(Plate $plate, array $dietaryTags): array
    {
        $requiredAbsentTags = $this->resolveUserConflictTags($dietaryTags);
        $plateConflictTags = is_array($plate->ai_conflict_tags) && $plate->ai_conflict_tags !== []
            ? $plate->ai_conflict_tags
            : [];

        $conflictingTags = array_values(array_intersect($requiredAbsentTags, $plateConflictTags));
        $baseHealthScore = (int) ($plate->ai_health_score ?? 70);
        $score = max(0, min(100, $baseHealthScore - (count($conflictingTags) * 25)));

        return [
            'score' => $score,
            'label' => $this->resolveLabel($score),
            'warning_message' => $score < 50
                ? ((string) $plate->ai_warning_fr !== ''
                    ? (string) $plate->ai_warning_fr
                    : 'Ce plat n\'est pas compatible avec vos restrictions alimentaires.')
                : '',
            'conflicting_tags' => $conflictingTags,
        ];
    }

    /**
     * @return array{health_score:int,conflict_tags:array<int,string>,warning_message:string}
     */
    private function analyzePlate(Plate $plate): array
    {
        $ingredientTags = $this->extractIngredientTags($plate);

        try {
            $apiKey = (string) config('services.gemini.api_key');

            if ($apiKey === '') {
                throw new \RuntimeException('GEMINI_API_KEY is not configured.');
            }

            $client = Gemini::client($apiKey);
            $result = $client
                ->generativeModel(model: 'gemini-2.0-flash')
                ->generateContent($this->buildPlateAnalysisPrompt($plate, $ingredientTags));

            $parsed = $this->parsePlateAnalysisResponse((string) $result->text());

            if ($parsed !== null) {
                return [
                    'health_score' => $parsed['score'],
                    'conflict_tags' => $this->normalizeConflictTags($ingredientTags),
                    'warning_message' => $parsed['warning_message'],
                ];
            }
        } catch (Throwable) {
            // Fall back to deterministic analysis if AI is unavailable.
        }

        return $this->fallbackPlateAnalysis($ingredientTags);
    }

    /**
     * @param array<int, string> $ingredientTags
     */
    private function buildPlateAnalysisPrompt(Plate $plate, array $ingredientTags): string
    {
        $ingredients = implode(', ', $ingredientTags);

        return "Analyze the nutritional compatibility between this dish and the user's dietary restrictions.\n\n"
            . "DISH: {$plate->name}\n"
            . "INGREDIENT TAGS: {$ingredients}\n"
            . "USER RESTRICTIONS: [vegan, no_sugar, no_cholesterol, gluten_free, no_lactose]\n\n"
            . "Tag mapping rules:\n"
            . "\"vegan\" restriction conflicts with: contains_meat, contains_lactose\n"
            . "\"no_sugar\" restriction conflicts with: contains_sugar\n"
            . "\"no_cholesterol\" restriction conflicts with: contains_cholesterol\n"
            . "\"gluten_free\" restriction conflicts with: contains_gluten\n"
            . "\"no_lactose\" restriction conflicts with: contains_lactose\n\n"
            . "Calculate score: start at 100, subtract 25 for each conflict found.\n\n"
            . "Respond ONLY with this JSON (no markdown, no explanation):\n"
            . '{"score": <0-100>, "warning_message": "<in French if score < 50, else empty string>"}';
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
     * @return array{score:int,warning_message:string}|null
     */
    private function parsePlateAnalysisResponse(string $rawText): ?array
    {
        $decoded = $this->decodeJsonObject($rawText);

        if (! is_array($decoded) || ! isset($decoded['score'])) {
            return null;
        }

        return [
            'score' => max(0, min(100, (int) $decoded['score'])),
            'warning_message' => isset($decoded['warning_message']) ? trim((string) $decoded['warning_message']) : '',
        ];
    }

    /**
     * @param array<int, string> $ingredientTags
     * @return array{health_score:int,conflict_tags:array<int,string>,warning_message:string}
     */
    private function fallbackPlateAnalysis(array $ingredientTags): array
    {
        $conflictTags = $this->normalizeConflictTags($ingredientTags);
        $healthScore = max(0, 100 - (count($conflictTags) * 15));

        return [
            'health_score' => $healthScore,
            'conflict_tags' => $conflictTags,
            'warning_message' => $healthScore < 50
                ? 'Ce plat semble peu adapte a plusieurs restrictions alimentaires.'
                : '',
        ];
    }

    /**
     * @param array<int, string> $ingredientTags
     * @return array<int, string>
     */
    private function normalizeConflictTags(array $ingredientTags): array
    {
        return collect($ingredientTags)
            ->filter(fn (string $tag) => in_array($tag, self::ANALYZABLE_TAGS, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function extractIngredientTags(Plate $plate): array
    {
        return $plate->ingredients
            ->flatMap(fn ($ingredient) => $ingredient->tags ?? [])
            ->filter(fn ($tag) => is_string($tag))
            ->values()
            ->all();
    }

    /**
     * @param array<int, Plate> $plates
     * @param array<int, string> $dietaryTags
     * @return array<int, array{plate_id:int,score:int,warning_message:string}>
     */
    private function sortedRecommendationsFromStoredAnalysis(array $plates, array $dietaryTags, int $limit): array
    {
        return collect($plates)
            ->map(function (Plate $plate) use ($dietaryTags): array {
                $analysis = $this->buildStoredRecommendation($plate, $dietaryTags);

                return [
                    'plate_id' => $plate->id,
                    'score' => $analysis['score'],
                    'warning_message' => $analysis['warning_message'],
                ];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    private function resolveLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Highly Recommended',
            $score >= 50 => 'Recommended with notes',
            default => 'Not Recommended',
        };
    }

    /**
     * @param array<int, string> $dietaryTags
     * @return array<int, string>
     */
    private function resolveUserConflictTags(array $dietaryTags): array
    {
        return collect($dietaryTags)
            ->map(function (string $dietTag): ?array {
                return match ($dietTag) {
                    'vegan' => ['contains_meat', 'contains_lactose'],
                    default => isset(self::CONFLICT_MAP[$dietTag]) ? [self::CONFLICT_MAP[$dietTag]] : null,
                };
            })
            ->filter(fn (?array $tags) => $tags !== null)
            ->flatMap(fn (array $tags) => $tags)
            ->unique()
            ->values()
            ->all();
    }
}
