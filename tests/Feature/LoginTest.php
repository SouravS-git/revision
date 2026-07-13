<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows users to login with valid credentials', function () {
    User::factory()->create([
        'email' => 'test@email.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@email.com',
        'password' => 'password',
    ])->assertOk();

    $response->assertJsonStructure([
        'token',
        'user' => [
            'name', 'email',
        ],
    ]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

it('requires an email', function () {
    $this->postJson('/api/login', [
        'password' => 'wrong_password',
    ])->assertInvalid([
        'email' => 'The email field is required.'
    ]);
});

it('requires password', function () {
    $this->postJson('/api/login', [
        'email' => 'test@email.com',
    ])->assertInvalid([
        'password' => 'The password field is required.'
    ]);
});

it('does not allow users to login with non-existing email', function () {
    User::factory()->create([
        'email' => 'test@email.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/login', [
        'email' => 'wrong@email.com',
        'password' => 'password',
    ])->assertInvalid([
        'email' => 'The email address does not exist.'
    ]);
});

it('does not allow users to login with invalid password', function () {
    User::factory()->create([
        'email' => 'test@email.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/login', [
        'email' => 'test@email.com',
        'password' => 'wrong_password',
    ])->assertInvalid([
        'password' => 'The provided credentials are incorrect.'
    ]);
});

it('does not return password in the response', function () {
    User::factory()->create([
        'email' => 'test@email.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@email.com',
        'password' => 'password',
    ]);

    $response->assertJsonMissingPath('user.password');
});

it('allows authenticated users to access protected routes', function () {
    $user = User::factory()->create([
        'email' => 'test@email.com',
        'password' => 'password',
    ]);

    /*$response = $this->postJson('/api/login', [
        'email' => 'test@email.com',
        'password' => 'password',
    ]);

    $this->withHeaders([
        'Authorization' => 'Bearer ' . $response->json('token'),
    ])->getJson('/api/articles')->assertOk();*/

    Sanctum::actingAs($user);
    $this->getJson('/api/articles')->assertOk();
});

it('does not allow unauthenticated users to access protected routes', function () {
   $this->getJson('/api/articles')->assertUnauthorized();
});





