<?php

namespace App\Http\Controllers\Api;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

class RegisteredUserController extends Controller
{
    public function __invoke(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password')
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        UserRegistered::dispatch($user);

        return response()->json([
            'user' => UserResource::make($user),
            'token' => $token
        ], 201);
    }
}
