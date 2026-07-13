<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if(! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))){
            return response()->json([
                'message' => 'Invalid verification link'
            ], 401);
        }

        if(! $user->hasVerifiedEmail()){
            $user->markEmailAsVerified();
        }

        return response()->json([
            'message' => 'Email verified'
        ], 200);
    }
}
