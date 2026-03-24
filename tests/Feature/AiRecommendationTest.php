<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Plate;
use App\Models\User;
use App\Services\AI\GeminiRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class AiRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $response = $this->postJson('/api/ai/recommendations', []);

        $response->assertStatus(401);
    }

    public function test_returns_recommendations_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'dietary_tags' => ['no_sugar'],
        ]);

        $category = Category::create([
            'name' => 'Healthy',
            'description' => 'Healthy dishes',
            'color' => '#22AA88',
            'is_active' => true,
        ]);

        $ingredient = Ingredient::create([
            'name' => 'Chicken Breast',
            'tags' => [],
        ]);

        $plate = Plate::create([
            'name' => 'Grilled Chicken Bowl',
            'description' => 'Lean protein with vegetables',
            'price' => 12.5,
            'image' => null,
            'is_available' => true,
            'category_id' => $category->id,
        ]);

        $plate->ingredients()->attach([$ingredient->id]);

        $serviceMock = Mockery::mock(GeminiRecommendationService::class);
        $serviceMock
            ->shouldReceive('recommend')
            ->once()
            ->andReturn([
                'source' => 'ai',
                'recommendations' => [
                    [
                        'plate_id' => $plate->id,
                        'score' => 93,
                        'reason' => 'High protein and compatible with your profile.',
                    ],
                ],
            ]);

        $this->app->instance(GeminiRecommendationService::class, $serviceMock);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/recommendations', [
            'prompt' => 'Need a high protein lunch',
            'limit' => 3,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('source', 'ai')
            ->assertJsonPath('count', 1)
            ->assertJsonPath('recommendations.0.score', 93)
            ->assertJsonPath('recommendations.0.plate.id', $plate->id);
    }
}
