<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Food Recommendation API',
    version: '1.0.0',
    description: 'API for personalized food recommendations based on user dietary preferences and AI analysis',
    contact: new OA\Contact(
        name: 'Food Recommendation API Support'
    )
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Development Server'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Local Server'
)]
#[OA\SecurityScheme(
    type: 'http',
    description: 'Login with username and password to get the authentication token',
    name: 'Bearer Token',
    in: 'header',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    securityScheme: 'bearerAuth'
)]
class ApiDocumentation
{
    // Auth Endpoints Documentation

    #[OA\Post(
        path: '/api/register',
        operationId: 'register',
        description: 'Register a new user account',
        summary: 'User Registration',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'User registration data',
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User created successfully'),
                        new OA\Property(property: 'user', properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'email', type: 'string'),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register() {}

    #[OA\Post(
        path: '/api/login',
        operationId: 'login',
        description: 'Login with email and password',
        summary: 'User Login',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Login credentials',
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                        new OA\Property(property: 'token', type: 'string', example: 'token_here'),
                        new OA\Property(property: 'user', properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'email', type: 'string'),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function login() {}

    #[OA\Post(
        path: '/api/logout',
        operationId: 'logout',
        description: 'Logout current user and invalidate token',
        summary: 'User Logout',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout() {}

    #[OA\Get(
        path: '/api/me',
        operationId: 'getCurrentUser',
        description: 'Get current authenticated user data',
        summary: 'Get Current User',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current user data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'is_admin', type: 'boolean'),
                        new OA\Property(property: 'dietary_tags', type: 'array', items: new OA\Items(type: 'string')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function me() {}

    // Profile Endpoints

    #[OA\Get(
        path: '/api/profile',
        operationId: 'getProfile',
        description: 'Get authenticated user profile',
        summary: 'Get Profile',
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User profile',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'is_admin', type: 'boolean'),
                        new OA\Property(property: 'dietary_tags', type: 'array', items: new OA\Items(type: 'string')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function getProfile() {}

    #[OA\Put(
        path: '/api/profile',
        operationId: 'updateProfile',
        description: 'Update authenticated user profile',
        summary: 'Update Profile',
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'dietary_tags', type: 'array', items: new OA\Items(type: 'string')),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'user', type: 'object'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateProfile() {}

    // Category Endpoints

    #[OA\Get(
        path: '/api/categories',
        operationId: 'listCategories',
        description: 'Get all food categories',
        summary: 'List Categories',
        tags: ['Catalog'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of categories',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'description', type: 'string'),
                        ]
                    )
                )
            ),
        ]
    )]
    public function listCategories() {}

    #[OA\Post(
        path: '/api/categories',
        operationId: 'createCategory',
        description: 'Create a new food category (Admin only)',
        summary: 'Create Category',
        tags: ['Catalog'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Category created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Admin access required'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function createCategory() {}

    #[OA\Get(
        path: '/api/categories/{category}',
        operationId: 'showCategory',
        description: 'Get a specific category with its plates',
        summary: 'Show Category',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'category', description: 'Category ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category details'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function showCategory() {}

    #[OA\Put(
        path: '/api/categories/{category}',
        operationId: 'updateCategory',
        description: 'Update a food category (Admin only)',
        summary: 'Update Category',
        tags: ['Catalog'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category', description: 'Category ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Category updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Admin access required'),
            new OA\Response(response: 404, description: 'Category not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateCategory() {}

    #[OA\Delete(
        path: '/api/categories/{category}',
        operationId: 'deleteCategory',
        description: 'Delete a food category (Admin only)',
        summary: 'Delete Category',
        tags: ['Catalog'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category', description: 'Category ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Admin access required'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function deleteCategory() {}

    #[OA\Get(
        path: '/api/categories/{category}/plates',
        operationId: 'getCategoryPlates',
        description: 'Get all plates in a category',
        summary: 'Get Category Plates',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'category', description: 'Category ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category plates'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function getCategoryPlates() {}

    // Plate Endpoints

    #[OA\Get(
        path: '/api/plates',
        operationId: 'listPlates',
        description: 'Get all food plates',
        summary: 'List Plates',
        tags: ['Catalog'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of plates',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'category_id', type: 'integer'),
                    ])
                )
            ),
        ]
    )]
    public function listPlates() {}

    #[OA\Post(
        path: '/api/plates',
        operationId: 'createPlate',
        description: 'Create a new plate (Admin only)',
        summary: 'Create Plate',
        tags: ['Catalog'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'category_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'category_id', type: 'integer'),
                    new OA\Property(property: 'ingredients', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Plate created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Admin access required'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function createPlate() {}

    #[OA\Get(
        path: '/api/plates/{plate}',
        operationId: 'showPlate',
        description: 'Get a specific plate with ingredients',
        summary: 'Show Plate',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'plate', description: 'Plate ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Plate details'),
            new OA\Response(response: 404, description: 'Plate not found'),
        ]
    )]
    public function showPlate() {}

    #[OA\Put(
        path: '/api/plates/{plate}',
        operationId: 'updatePlate',
        description: 'Update a plate (Admin only)',
        summary: 'Update Plate',
        tags: ['Catalog'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'plate', description: 'Plate ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'category_id', type: 'integer'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Plate updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Admin access required'),
            new OA\Response(response: 404, description: 'Plate not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updatePlate() {}

    #[OA\Delete(
        path: '/api/plates/{plate}',
        operationId: 'deletePlate',
        description: 'Delete a plate (Admin only)',
        summary: 'Delete Plate',
        tags: ['Catalog'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'plate', description: 'Plate ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Plate deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Admin access required'),
            new OA\Response(response: 404, description: 'Plate not found'),
        ]
    )]
    public function deletePlate() {}

    // Ingredient Endpoints

    #[OA\Get(
        path: '/api/ingredients',
        operationId: 'listIngredients',
        description: 'Get all ingredients',
        summary: 'List Ingredients',
        tags: ['Catalog'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of ingredients',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                    ])
                )
            ),
        ]
    )]
    public function listIngredients() {}

    #[OA\Post(
        path: '/api/ingredients',
        operationId: 'createIngredient',
        description: 'Create a new ingredient (Admin only)',
        summary: 'Create Ingredient',
        tags: ['Catalog'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ingredient created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Admin access required'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function createIngredient() {}

    #[OA\Put(
        path: '/api/ingredients/{ingredient}',
        operationId: 'updateIngredient',
        description: 'Update an ingredient (Admin only)',
        summary: 'Update Ingredient',
        tags: ['Catalog'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'ingredient', description: 'Ingredient ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Ingredient updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Admin access required'),
            new OA\Response(response: 404, description: 'Ingredient not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateIngredient() {}

    #[OA\Delete(
        path: '/api/ingredients/{ingredient}',
        operationId: 'deleteIngredient',
        description: 'Delete an ingredient (Admin only)',
        summary: 'Delete Ingredient',
        tags: ['Catalog'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'ingredient', description: 'Ingredient ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ingredient deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Admin access required'),
            new OA\Response(response: 404, description: 'Ingredient not found'),
        ]
    )]
    public function deleteIngredient() {}

    // Recommendation Endpoints

    #[OA\Get(
        path: '/api/recommendations',
        operationId: 'listRecommendations',
        description: 'Get all recommendations for the authenticated user',
        summary: 'List Recommendations',
        tags: ['AI/Recommendations'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of recommendations',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'plate_id', type: 'integer'),
                        new OA\Property(property: 'score', type: 'number', format: 'float'),
                        new OA\Property(property: 'label', type: 'string'),
                        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'processing', 'ready']),
                    ])
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function listRecommendations() {}

    #[OA\Post(
        path: '/api/recommendations/analyze/{plate_id}',
        operationId: 'analyzeRecommendation',
        description: 'Analyze a plate and generate AI recommendation (async job)',
        summary: 'Analyze Plate for Recommendation',
        tags: ['AI/Recommendations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'plate_id', description: 'Plate ID to analyze', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 202,
                description: 'Recommendation analysis queued',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'recommendation_id', type: 'integer'),
                    new OA\Property(property: 'status', type: 'string', example: 'processing'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Plate not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function analyzeRecommendation() {}

    #[OA\Get(
        path: '/api/recommendations/{plate_id}',
        operationId: 'showRecommendation',
        description: 'Get recommendation for a specific plate',
        summary: 'Get Plate Recommendation',
        tags: ['AI/Recommendations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'plate_id', description: 'Plate ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Recommendation details',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'plate_id', type: 'integer'),
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'score', type: 'number', format: 'float'),
                    new OA\Property(property: 'label', type: 'string'),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'processing', 'ready']),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Recommendation not found'),
        ]
    )]
    public function showRecommendation() {}

    #[OA\Get(
        path: '/api/ai/recommendations',
        operationId: 'getAiRecommendations',
        description: 'Get AI-generated recommendations based on user dietary preferences',
        summary: 'Get AI Recommendations',
        tags: ['AI/Recommendations'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'AI recommendations',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'plate_id', type: 'integer'),
                        new OA\Property(property: 'score', type: 'number', format: 'float'),
                        new OA\Property(property: 'status', type: 'string'),
                    ])
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function getAiRecommendations() {}

    // Admin Endpoints

    #[OA\Get(
        path: '/api/admin/stats',
        operationId: 'getAdminStats',
        description: 'Get system statistics (Admin only)',
        summary: 'Get Admin Statistics',
        tags: ['Admin'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'System statistics',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'total_users', type: 'integer'),
                    new OA\Property(property: 'total_plates', type: 'integer'),
                    new OA\Property(property: 'total_recommendations', type: 'integer'),
                    new OA\Property(property: 'pending_recommendations', type: 'integer'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Admin access required'),
        ]
    )]
    public function getAdminStats() {}
}
