<?php

use App\Models\Article;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('does not allow guests to access article endpoints', function () {
    $this->getJson(route('articles.index'))->assertUnauthorized();
});

it('returns all the articles', function () {
    Cache::flush();
    $user = User::factory()->create();
    $articles = Article::factory(3)->for($user, 'author')->create();

    Sanctum::actingAs($user);

    $response = $this->getJson(route('articles.index'))->assertOk();
    $response->assertJsonCount(3, 'data');

    $response->assertJsonStructure([
        'data' => [
            "*" => [
                'id',
                'title',
                'slug',
                'description',
                'published',
                'author' => [
                    'name', 'email',
                ]
            ]
        ]
    ]);

    $response->assertJson([
        'data' => [
            [
                'id' => $articles->find(1)->id,
                'title' => $articles->find(1)->title,
            ],
            [
                'id' => $articles->find(2)->id,
                'title' => $articles->find(2)->title,
            ],
            [
                'id' => $articles->find(3)->id,
                'title' => $articles->find(3)->title,
            ]
        ]
    ]);

    $response->assertJsonPath('data.0.author.name', $user->name);
});

it('creates a new article', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson(route('articles.store'), [
        'title' => 'Test Article',
        'description' => 'This is a test article.',
    ])->assertCreated();

    $this->assertDatabaseHas('articles', [
        'user_id' => $user->id,
        'title' => 'Test Article',
        'description' => 'This is a test article.',
    ]);
});

it('returns a single article', function () {
    $user = User::factory()->create();
    $article = Article::factory()->for($user, 'author')->create();

    Sanctum::actingAs($user);
    $response = $this->getJson('articles/' . $article->id)->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'title',
            'slug',
            'description',
            'published',
            'author' => [
                'name', 'email',
            ]
        ]
    ]);
});
