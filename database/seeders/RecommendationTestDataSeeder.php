<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Plate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RecommendationTestDataSeeder extends Seeder
{
    /**
     * Seed rich API test data for recommendations.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Breakfast', 'description' => 'Morning dishes', 'color' => '#F59E0B', 'is_active' => true],
            ['name' => 'Lunch', 'description' => 'Midday meals', 'color' => '#10B981', 'is_active' => true],
            ['name' => 'Dinner', 'description' => 'Evening meals', 'color' => '#3B82F6', 'is_active' => true],
            ['name' => 'Desserts', 'description' => 'Sweet dishes', 'color' => '#EF4444', 'is_active' => true],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['name' => $categoryData['name']],
                $categoryData
            );
        }

        $ingredients = [
            ['name' => 'Chicken Breast', 'tags' => []],
            ['name' => 'Salmon', 'tags' => []],
            ['name' => 'Tofu', 'tags' => []],
            ['name' => 'Broccoli', 'tags' => []],
            ['name' => 'Spinach', 'tags' => []],
            ['name' => 'Brown Rice', 'tags' => ['contains_gluten']],
            ['name' => 'Whole Wheat Pasta', 'tags' => ['contains_gluten']],
            ['name' => 'Yogurt', 'tags' => ['contains_lactose']],
            ['name' => 'Cheddar Cheese', 'tags' => ['contains_lactose', 'contains_cholesterol']],
            ['name' => 'Egg', 'tags' => ['contains_cholesterol']],
            ['name' => 'Olive Oil', 'tags' => []],
            ['name' => 'Honey', 'tags' => ['contains_sugar']],
            ['name' => 'Dark Chocolate', 'tags' => ['contains_sugar']],
            ['name' => 'Almond Milk', 'tags' => []],
            ['name' => 'Banana', 'tags' => []],
        ];

        $ingredientModels = [];
        foreach ($ingredients as $ingredientData) {
            $ingredientModels[$ingredientData['name']] = Ingredient::updateOrCreate(
                ['name' => $ingredientData['name']],
                ['tags' => $ingredientData['tags']]
            );
        }

        $plates = [
            [
                'name' => 'Grilled Chicken Bowl',
                'description' => 'Lean chicken with greens and olive oil.',
                'price' => 12.50,
                'image' => null,
                'is_available' => true,
                'category' => 'Lunch',
                'ingredients' => ['Chicken Breast', 'Broccoli', 'Spinach', 'Olive Oil'],
            ],
            [
                'name' => 'Vegan Tofu Plate',
                'description' => 'Tofu with spinach and broccoli.',
                'price' => 11.00,
                'image' => null,
                'is_available' => true,
                'category' => 'Dinner',
                'ingredients' => ['Tofu', 'Spinach', 'Broccoli', 'Olive Oil'],
            ],
            [
                'name' => 'Creamy Pasta',
                'description' => 'Whole wheat pasta with cheese sauce.',
                'price' => 13.00,
                'image' => null,
                'is_available' => true,
                'category' => 'Dinner',
                'ingredients' => ['Whole Wheat Pasta', 'Cheddar Cheese', 'Olive Oil'],
            ],
            [
                'name' => 'Yogurt Power Bowl',
                'description' => 'Yogurt with banana and honey.',
                'price' => 8.75,
                'image' => null,
                'is_available' => true,
                'category' => 'Breakfast',
                'ingredients' => ['Yogurt', 'Banana', 'Honey'],
            ],
            [
                'name' => 'Salmon Green Plate',
                'description' => 'Salmon with steamed broccoli and spinach.',
                'price' => 15.25,
                'image' => null,
                'is_available' => true,
                'category' => 'Lunch',
                'ingredients' => ['Salmon', 'Broccoli', 'Spinach', 'Olive Oil'],
            ],
            [
                'name' => 'Chocolate Delight',
                'description' => 'Dessert with dark chocolate and banana.',
                'price' => 7.50,
                'image' => null,
                'is_available' => true,
                'category' => 'Desserts',
                'ingredients' => ['Dark Chocolate', 'Banana'],
            ],
        ];

        foreach ($plates as $plateData) {
            $category = Category::query()->where('name', $plateData['category'])->firstOrFail();

            $plate = Plate::updateOrCreate(
                ['name' => $plateData['name']],
                [
                    'description' => $plateData['description'],
                    'price' => $plateData['price'],
                    'image' => $plateData['image'],
                    'is_available' => $plateData['is_available'],
                    'category_id' => $category->id,
                ]
            );

            $ingredientIds = array_map(
                fn (string $ingredientName) => $ingredientModels[$ingredientName]->id,
                $plateData['ingredients']
            );

            $plate->ingredients()->sync($ingredientIds);
        }

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'is_admin' => true,
                'dietary_tags' => [],
            ]
        );

        User::updateOrCreate(
            ['email' => 'vegan@example.com'],
            [
                'name' => 'Vegan User',
                'password' => Hash::make('password123'),
                'is_admin' => false,
                'dietary_tags' => ['vegan'],
            ]
        );

        User::updateOrCreate(
            ['email' => 'nosugar@example.com'],
            [
                'name' => 'No Sugar User',
                'password' => Hash::make('password123'),
                'is_admin' => false,
                'dietary_tags' => ['no_sugar'],
            ]
        );

        User::updateOrCreate(
            ['email' => 'glutenfree@example.com'],
            [
                'name' => 'Gluten Free User',
                'password' => Hash::make('password123'),
                'is_admin' => false,
                'dietary_tags' => ['gluten_free'],
            ]
        );
    }
}
