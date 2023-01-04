<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TokenController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|email|min:5',
            'password' => 'required|min:8',
        ]);

        /**
         * @var User|null
         */
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'authentication' => ['The provided credentials are incorrect.'],
            ], 422);
        }

        return response()->json([
            'token' => $user->createToken('authenticate')->plainTextToken,
        ]);
    }
}
