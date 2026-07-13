<?php

use App\Events\UserRegistered;
use App\Listeners\SendWelcomeEmail;
use App\Mail\WelcomeEmail;
use App\Models\User;

it('registers a new user', function () {
    $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@email.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertCreated();

    $this->assertDatabaseHas('users', [
        'email' => 'test@email.com'
    ]);
});

it('requires an email', function () {
   $this->postJson('/api/register', [
       'name' => 'Test User',
       'password' => 'password',
       'password_confirmation' => 'password',
   ])->assertValid(['name', 'password'])->assertInvalid([
       'email' => 'The email field is required.'
   ]);
});

it('email must be unique', function () {
    User::factory()->create([
        'email' => 'test@email.com'
    ]);

    $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@email.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertJsonMissingValidationErrors(['name', 'password'])->assertJsonValidationErrors([
        'email' => 'The email address is already in use.'
    ]);
});

it('password confirmation must match password', function () {
    $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@email.com',
        'password' => 'password',
        'password_confirmation' => 'wrong',
    ])->assertInvalid([
        'password' => 'The password confirmation does not match.'
    ]);
});

it('password is hashed', function () {
    $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@email.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::first();
    expect(Hash::check('password', $user->password))->toBeTrue();
});

it('verifies the data in the response', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@email.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertJson([
        'token' => $response->json('token'),
        'user' => [
            'name' => 'Test User',
        ],
    ]);

    $response->assertExactJson([
        'token' => $response->json('token'),
        'user' => [
            'name' => 'Test User',
            'email' => 'test@email.com',
        ],
    ]);

    $response->assertJsonFragment([
        'name' => 'Test User',
        'email' => 'test@email.com',
        'token' => $response->json('token'),
    ]);

    $response->assertJsonStructure([
        'token',
        'user' => [
            'name', 'email',
        ],
    ]);

    $response->assertJsonMissingPath('user.password');

    $response->assertJsonPath('token', $response->json('token'));

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

it('dispatches the UserRegistered event after registration', function () {
    Event::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@email.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertCreated();

    Event::assertDispatched(UserRegistered::class, function ($event) {
        return $event->user->email === 'test@email.com';
    });
});

it('sends a welcome email when a user registers', function () {
    Mail::fake();

    $user = User::factory()->create();

    $listener = new SendWelcomeEmail();
    $listener->handle(new UserRegistered($user));

    Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
       return $mail->hasTo($user->email);
    });
});

